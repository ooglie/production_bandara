<?php

namespace App\Services;

use App\Models\InventoryPack;
use App\Models\InventoryPiece;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ProductLabelBatchService
{
    public const MAX_INVENTORY_OPTIONS = 100;

    public function __construct(private readonly ProductLabelService $labels) {}

    public function defaults(Product $product): array
    {
        $defaults = $this->labels->defaults($product);
        $defaults['price_per_kg'] = $this->labels->retailMrp($product);
        $defaults['manual_weights'] = '';

        unset($defaults['price'], $defaults['unit_label'], $defaults['copies']);

        return $defaults;
    }

    public function supports(Product $product): bool
    {
        return $this->labels->supportsVariableWeight($product);
    }

    /**
     * @return array{
     *   pieces: array<int, array<string, mixed>>,
     *   packs: array<int, array<string, mixed>>
     * }
     */
    public function availableInventory(Product $product): array
    {
        $pieces = $this->availablePieceQuery($product)
            ->with('inventoryLot:id,lot_code,batch_code,expiry_date')
            ->orderBy('weight_kg')
            ->orderBy('id')
            ->limit(self::MAX_INVENTORY_OPTIONS)
            ->get()
            ->map(fn (InventoryPiece $piece) => $this->pieceItem($piece))
            ->filter(fn (array $item) => $item['weight_kg'] > 0)
            ->values()
            ->all();

        $packs = $this->availablePackQuery($product)
            ->orderBy('actual_weight_kg')
            ->orderBy('id')
            ->limit(self::MAX_INVENTORY_OPTIONS)
            ->get()
            ->map(fn (InventoryPack $pack) => $this->packItem($pack))
            ->filter(fn (array $item) => $item['weight_kg'] > 0)
            ->values()
            ->all();

        return compact('pieces', 'packs');
    }

    /**
     * @param  array<int, float>  $manualWeights
     * @return array<int, array<string, mixed>>
     */
    public function resolveItems(Product $product, array $values, array $manualWeights): array
    {
        $pieceIds = array_values(array_unique(array_map('intval', $values['inventory_piece_ids'] ?? [])));
        $packIds = array_values(array_unique(array_map('intval', $values['inventory_pack_ids'] ?? [])));
        $items = [];

        if ($pieceIds !== []) {
            $pieces = $this->availablePieceQuery($product)
                ->with('inventoryLot:id,lot_code,batch_code,expiry_date')
                ->whereIn('id', $pieceIds)
                ->get()
                ->keyBy('id');

            if ($pieces->count() !== count($pieceIds)) {
                throw ValidationException::withMessages([
                    'inventory_piece_ids' => 'One or more selected pieces are no longer available for this product.',
                ]);
            }

            foreach ($pieceIds as $pieceId) {
                $items[] = $this->pieceItem($pieces->get($pieceId));
            }
        }

        if ($packIds !== []) {
            $packs = $this->availablePackQuery($product)
                ->whereIn('id', $packIds)
                ->get()
                ->keyBy('id');

            if ($packs->count() !== count($packIds)) {
                throw ValidationException::withMessages([
                    'inventory_pack_ids' => 'One or more selected packs are no longer available for this product.',
                ]);
            }

            foreach ($packIds as $packId) {
                $items[] = $this->packItem($packs->get($packId));
            }
        }

        foreach ($manualWeights as $index => $weight) {
            $items[] = [
                'source_type' => 'manual',
                'source_id' => null,
                'reference' => 'Manual weight '.($index + 1),
                'weight_kg' => round((float) $weight, 3),
                'best_before' => null,
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function buildLabels(Product $product, array $values, array $items): array
    {
        $defaults = $this->labels->defaults($product);
        $common = array_merge($defaults, $values);
        $pricePerKg = (float) $values['price_per_kg'];

        unset(
            $common['price_per_kg'],
            $common['manual_weights'],
            $common['inventory_piece_ids'],
            $common['inventory_pack_ids'],
            $common['disposition'],
        );

        return collect($items)
            ->map(function (array $item) use ($common, $pricePerKg): array {
                $weightKg = round((float) $item['weight_kg'], 3);
                $data = $this->labels->formatLabelData(array_merge($common, [
                    'price' => round($pricePerKg * $weightKg, 2),
                    'unit_label' => $this->labels->formatWeightKg($weightKg),
                    'best_before' => $item['best_before'] ?: $common['best_before'],
                    'copies' => 1,
                ]));

                $data['weight_kg'] = $weightKg;
                $data['price_per_kg'] = round($pricePerKg, 2);
                $data['inventory_reference'] = $item['reference'] ?? null;

                return $data;
            })
            ->values()
            ->all();
    }

    /** @param array<int, array<string, mixed>> $items */
    public function makePdf(Product $product, array $values, array $items): PDF
    {
        $labelData = $this->buildLabels($product, $values, $items);

        return PdfFacade::loadView('labels.batch', array_merge([
            'labels' => $labelData,
            'product' => $product,
        ], $this->labels->pdfAssets()))->setPaper([
            0,
            0,
            ProductLabelService::PAGE_WIDTH_POINTS,
            ProductLabelService::PAGE_HEIGHT_POINTS,
        ]);
    }

    private function availablePieceQuery(Product $product): Builder
    {
        return InventoryPiece::query()
            ->where('status', 'available')
            ->where(function (Builder $weights) {
                $weights->where('available_weight_kg', '>', 0)
                    ->orWhere(function (Builder $originalWeight) {
                        $originalWeight->whereNull('available_weight_kg')
                            ->where('weight_kg', '>', 0);
                    });
            })
            ->whereHas('inventoryLot', function (Builder $lots) use ($product) {
                $lots->where('product_id', $product->id)
                    ->where('is_saleable', true)
                    ->where('lot_status', 'available')
                    ->where(function (Builder $expiry) {
                        $expiry->whereNull('expiry_date')
                            ->orWhereDate('expiry_date', '>=', now()->startOfMonth()->toDateString());
                    });
            });
    }

    private function availablePackQuery(Product $product): Builder
    {
        return InventoryPack::query()
            ->where('product_id', $product->id)
            ->where('status', 'available')
            ->where(function (Builder $available) {
                $available->whereNull('available_pack_quantity')
                    ->orWhere('available_pack_quantity', '>', 0);
            })
            ->where(function (Builder $expiry) {
                $expiry->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', now()->startOfMonth()->toDateString());
            })
            ->where(function (Builder $weights) {
                $weights->where('actual_weight_kg', '>', 0)
                    ->orWhere('unit_weight_kg', '>', 0)
                    ->orWhere('total_weight_kg', '>', 0);
            });
    }

    /** @return array<string, mixed> */
    private function pieceItem(InventoryPiece $piece): array
    {
        $lot = $piece->inventoryLot;
        $weight = $piece->available_weight_kg !== null
            ? (float) $piece->available_weight_kg
            : (float) $piece->weight_kg;
        $pieceName = $piece->label ?: 'Piece '.$piece->piece_no;
        $lotName = $lot?->lot_code ?: 'Lot #'.$piece->inventory_lot_id;

        return [
            'source_type' => 'piece',
            'source_id' => (int) $piece->id,
            'reference' => $lotName.' / '.$pieceName,
            'batch_code' => (string) ($lot?->batch_code ?? ''),
            'weight_kg' => round($weight, 3),
            'weight_label' => $this->labels->formatWeightKg($weight),
            'best_before' => $this->expiryMonth($lot?->expiry_date),
            'expiry_label' => $lot?->expiry_date?->format('M Y'),
        ];
    }

    /** @return array<string, mixed> */
    private function packItem(InventoryPack $pack): array
    {
        $weight = (float) ($pack->actual_weight_kg ?: $pack->unit_weight_kg ?: $pack->total_weight_kg ?: 0);

        return [
            'source_type' => 'pack',
            'source_id' => (int) $pack->id,
            'reference' => $pack->pack_code ?: 'Pack #'.$pack->id,
            'batch_code' => (string) ($pack->batch_code ?? ''),
            'weight_kg' => round($weight, 3),
            'weight_label' => $this->labels->formatWeightKg($weight),
            'best_before' => $this->expiryMonth($pack->expiry_date),
            'expiry_label' => $pack->expiry_date?->format('M Y'),
        ];
    }

    private function expiryMonth(mixed $date): ?string
    {
        return $date ? Carbon::parse($date)->format('Y-m') : null;
    }
}

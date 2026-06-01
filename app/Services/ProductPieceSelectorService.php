<?php

namespace App\Services;

use App\Models\InventoryPiece;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductPieceSelectorService
{
    public function buildForProduct(Product $product): array
    {
        return $this->buildForProducts(collect([$product]))[$product->id] ?? ['enabled' => false];
    }

    public function buildForProducts($products): array
    {
        $collection = $products instanceof Collection ? $products : collect($products);

        if ($collection->isEmpty()) {
            return [];
        }

        $eligibleProductIds = $collection
            ->filter(fn ($product) => (string) ($product->type ?? 'simple') === 'simple')
            ->pluck('id')
            ->values();

        if ($eligibleProductIds->isEmpty()) {
            return $collection->mapWithKeys(fn ($product) => [
                $product->id => ['enabled' => false],
            ])->all();
        }

        $rows = InventoryPiece::query()
            ->select([
                'inventory_pieces.id as piece_id',
                'inventory_pieces.inventory_lot_id',
                'inventory_pieces.piece_no',
                'inventory_pieces.weight_kg',
                'inventory_lots.product_id',
                'inventory_lots.lot_code',
                'inventory_lots.batch_code',
                'inventory_lots.expiry_date',
            ])
            ->join('inventory_lots', 'inventory_lots.id', '=', 'inventory_pieces.inventory_lot_id')
            ->whereIn('inventory_lots.product_id', $eligibleProductIds)
            ->where('inventory_lots.is_saleable', true)
            ->where('inventory_lots.lot_status', 'available')
            ->where('inventory_lots.inward_mode', 'pieces')
            ->where(function ($q) {
                $q->whereNull('inventory_lots.available_piece_count')
                  ->orWhere('inventory_lots.available_piece_count', '>', 0);
            })
            ->where('inventory_pieces.status', 'available')
            ->orderBy('inventory_lots.product_id')
            ->orderBy('inventory_pieces.weight_kg')
            ->orderBy('inventory_pieces.id')
            ->get()
            ->groupBy('product_id');

        $result = [];

        foreach ($collection as $product) {
            $productRows = $rows->get($product->id, collect());

            if ($productRows->isEmpty()) {
                $result[$product->id] = ['enabled' => false];
                continue;
            }

            $pricePerBaseUnit = (float) ($product->effective_price ?? 0);
            $sellUnit = (string) ($product->sell_unit ?? 'piece');

            $pieces = $productRows->map(function ($row) use ($pricePerBaseUnit, $sellUnit) {
                $weightKg = round((float) $row->weight_kg, 3);

                $exactPrice = $sellUnit === 'kg'
                    ? round($pricePerBaseUnit * $weightKg, 2)
                    : round($pricePerBaseUnit, 2);

                return [
                    'id' => (int) $row->piece_id,
                    'inventory_lot_id' => (int) $row->inventory_lot_id,
                    'piece_no' => (int) $row->piece_no,
                    'weight_kg' => $weightKg,
                    'weight_label' => $this->formatPieceWeightLabel($weightKg),
                    'price' => $exactPrice,
                    'lot_code' => (string) ($row->lot_code ?: ('LOT-' . $row->inventory_lot_id)),
                    'batch_code' => (string) ($row->batch_code ?? ''),
                    'expiry_label' => $row->expiry_date
                        ? \Illuminate\Support\Carbon::parse($row->expiry_date)->format('d M')
                        : null,
                ];
            })->values();

            $bands = $this->buildFixed100gBands($pieces);

            $result[$product->id] = [
                'enabled' => true,
                'count' => $pieces->count(),
                'price_min' => round((float) $pieces->min('price'), 2),
                'price_max' => round((float) $pieces->max('price'), 2),
                'bands' => $bands,
            ];
        }

        return $result;
    }

    /**
     * Build fixed 100 g buckets:
     * 500–600, 601–700, 701–800, ...
     * Only buckets with at least one piece are returned.
     * Inside each band, exact equal weights are grouped together.
     */
    protected function buildFixed100gBands(Collection $pieces): array
    {
        if ($pieces->isEmpty()) {
            return [];
        }

        $sorted = $pieces->sortBy('weight_kg')->values();

        $gramsList = $sorted->map(function ($piece) {
            return (int) round(((float) $piece['weight_kg']) * 1000);
        });

        $minGrams = (int) $gramsList->min();
        $baseStart = intdiv($minGrams, 100) * 100;

        $grouped = [];

        foreach ($sorted as $piece) {
            $grams = (int) round(((float) $piece['weight_kg']) * 1000);

            $index = intdiv(max($grams - $baseStart - 1, 0), 100);
            $bucketStart = $baseStart + ($index * 100);
            $bucketEnd = $bucketStart + 100;
            $key = 'band_' . ($index + 1);

            if (!isset($grouped[$key])) {
                $displayStart = $index === 0 ? $bucketStart : ($bucketStart + 1);

                $grouped[$key] = [
                    'key' => $key,
                    'bucket_start' => $bucketStart,
                    'label' => $displayStart . '–' . $bucketEnd . ' g',
                    'count' => 0,
                    'price_min' => null,
                    'price_max' => null,
                    'choices' => [],
                ];
            }

            $grouped[$key]['count']++;

            $price = (float) ($piece['price'] ?? 0);

            if ($grouped[$key]['price_min'] === null || $price < $grouped[$key]['price_min']) {
                $grouped[$key]['price_min'] = round($price, 2);
            }

            if ($grouped[$key]['price_max'] === null || $price > $grouped[$key]['price_max']) {
                $grouped[$key]['price_max'] = round($price, 2);
            }

            $weightKey = number_format((float) $piece['weight_kg'], 3, '.', '');

            if (!isset($grouped[$key]['choices'][$weightKey])) {
                $grouped[$key]['choices'][$weightKey] = [
                    'key' => $weightKey,
                    'weight_kg' => (float) $piece['weight_kg'],
                    'weight_label' => $piece['weight_label'],
                    'price' => round((float) $piece['price'], 2),
                    'count' => 0,
                ];
            }

            $grouped[$key]['choices'][$weightKey]['count']++;
        }

        return collect($grouped)
            ->sortBy('bucket_start')
            ->map(function ($band) {
                $band['choices'] = collect($band['choices'])
                    ->sortBy('weight_kg')
                    ->values()
                    ->all();

                unset($band['bucket_start']);
                return $band;
            })
            ->values()
            ->all();
    }

    protected function formatPieceWeightLabel(float $kg): string
    {
        return $kg < 2
            ? round($kg * 1000) . ' g'
            : number_format($kg, 3) . ' kg';
    }
}
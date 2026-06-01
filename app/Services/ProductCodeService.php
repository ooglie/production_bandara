<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductCodeService
{
    // Change if you want a different brand prefix
    private string $skuPrefix = 'BA';   // Bandara
    private string $eanPrefix = '890';  // 3 digits (internal prefix; ok for internal barcodes)

    public function assignMissingCodes(Product $product): void
    {
        $updates = [];

        // SKU: BA + (W or P) + 6-digit ID
        if (empty($product->sku)) {
            $updates['sku'] = $this->uniqueSkuFor($product);
        }

        // Barcode: EAN-13 based on product id (12 digits + checksum)
        if (empty($product->barcode)) {
            $updates['barcode'] = $this->uniqueEan13For($product);
        }

        if (!empty($updates)) {
            $product->forceFill($updates)->saveQuietly();
        }
    }

    private function uniqueSkuFor(Product $product): string
    {
        $unit = strtolower((string)($product->sell_unit ?? 'pack'));
        $unitLetter = ($unit === 'kg') ? 'W' : 'P'; // W = weight-sold, P = pack/piece
        $base = $this->skuPrefix . $unitLetter . str_pad((string)$product->id, 6, '0', STR_PAD_LEFT);

        $candidate = $base;
        $i = 0;

        while ($this->skuExists($candidate, $product->id)) {
            $i++;
            $candidate = $base . '-' . $i;
        }

        return $candidate;
    }

    private function skuExists(string $sku, int $ignoreId): bool
    {
        return DB::table('products')
            ->whereNull('deleted_at')
            ->where('sku', $sku)
            ->where('id', '!=', $ignoreId)
            ->exists();
    }

    private function uniqueEan13For(Product $product): string
    {
        // Body = 12 digits
        // 3-digit prefix + 9-digit product id padded
        $body = $this->eanPrefix . str_pad((string)$product->id, 9, '0', STR_PAD_LEFT);
        $candidate = $body . $this->ean13CheckDigit($body);

        // If collision (usually only if someone manually used same barcode), fall back to random body
        while ($this->barcodeExists($candidate, $product->id)) {
            $rand9 = str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $body = $this->eanPrefix . $rand9;
            $candidate = $body . $this->ean13CheckDigit($body);
        }

        return $candidate;
    }

    private function barcodeExists(string $barcode, int $ignoreId): bool
    {
        return DB::table('products')
            ->whereNull('deleted_at')
            ->where('barcode', $barcode)
            ->where('id', '!=', $ignoreId)
            ->exists();
    }

    /**
     * @param string $digits12 exactly 12 digits
     */
    private function ean13CheckDigit(string $digits12): int
    {
        $digits12 = preg_replace('/\D/', '', $digits12);

        if (strlen($digits12) !== 12) {
            throw new \InvalidArgumentException('EAN-13 body must be exactly 12 digits.');
        }

        $sumOdd = 0;  // positions 1,3,5...
        $sumEven = 0; // positions 2,4,6...

        for ($i = 0; $i < 12; $i++) {
            $n = (int)$digits12[$i];
            if ((($i + 1) % 2) === 0) $sumEven += $n;
            else $sumOdd += $n;
        }

        $total = $sumOdd + ($sumEven * 3);
        $mod = $total % 10;

        return $mod === 0 ? 0 : (10 - $mod);
    }
}

<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;
use Carbon\CarbonImmutable;

class ProductLabelService
{
    public const PAGE_WIDTH_POINTS = 288;

    public const PAGE_HEIGHT_POINTS = 216;

    public function defaults(Product $product): array
    {
        $product->loadMissing('categories');

        $company = (array) config('bandara_content.company', []);
        $countryCode = strtoupper(trim((string) ($product->country_of_origin ?? '')));
        $country = $countryCode !== ''
            ? Country::query()->whereKey($countryCode)->value('name')
            : null;

        return [
            'category' => (string) ($product->categories
                ->sortBy(fn ($category) => sprintf('%08d-%s', (int) ($category->position ?? 0), $category->name))
                ->first()?->name ?? 'Product'),
            'country' => (string) ($country ?: ($countryCode ?: 'India')),
            'product_name' => (string) $product->name,
            'price' => $this->retailMrp($product),
            'unit_label' => $this->unitLabel($product),
            'company_name' => (string) ($company['legal_name'] ?? 'Bandara LLP'),
            'fssai' => (string) ($company['fssai'] ?? config('store.invoice.seller.fssai_no', '21526079001348')),
            'website' => $this->websiteUrl((string) ($company['corporate_domain'] ?? 'bandarallp.com')),
            'best_before' => now()->addYear()->format('Y-m'),
            'copies' => 1,
        ];
    }

    public function labelData(Product $product, array $values): array
    {
        return $this->formatLabelData(array_merge($this->defaults($product), $values));
    }

    public function formatLabelData(array $data): array
    {
        $bestBefore = CarbonImmutable::createFromFormat('!Y-m', (string) $data['best_before']);

        $data['category'] = trim((string) $data['category']);
        $data['country'] = mb_strtoupper(trim((string) $data['country']));
        $data['product_name'] = mb_strtoupper(trim((string) $data['product_name']));
        $data['price'] = number_format((float) $data['price'], 2, '.', '');
        $data['unit_label'] = trim((string) $data['unit_label']);
        $data['company_name'] = mb_strtoupper(trim((string) $data['company_name']));
        $data['fssai'] = trim((string) $data['fssai']);
        $data['website'] = $this->websiteUrl((string) $data['website']);
        $data['best_before_label'] = $bestBefore->format('F Y');
        $data['copies'] = (int) ($data['copies'] ?? 1);

        $data['category_font_size'] = $this->categoryFontSize($data['category']);
        $data['category_text_top'] = round(-5.2 + ((21.3 - $data['category_font_size']) * 0.45), 2);
        $data['product_font_size'] = $this->productFontSize($data['product_name']);
        $data['country_font_size'] = mb_strlen($data['country']) <= 18 ? 8.0 : 6.6;
        $data['price_font_size'] = mb_strlen($data['unit_label']) <= 16 ? 8.0 : 6.7;
        $data['company_font_size'] = mb_strlen($data['company_name']) <= 16 ? 8.0 : 6.8;
        $data['website_font_size'] = mb_strlen($data['website']) <= 27 ? 6.0 : 5.0;

        return $data;
    }

    public function makePdf(Product $product, array $values): PDF
    {
        $label = $this->labelData($product, $values);

        return PdfFacade::loadView('labels.product', array_merge([
            'label' => $label,
            'copies' => $label['copies'],
        ], $this->pdfAssets()))->setPaper([
            0,
            0,
            self::PAGE_WIDTH_POINTS,
            self::PAGE_HEIGHT_POINTS,
        ]);
    }

    public function retailMrp(Product $product): float
    {
        $price = (float) (($product->mrp_price ?? null) ?: ($product->base_price ?? 0));
        $gstRate = (float) ($product->gst_rate ?? 0);

        if (($product->b2c_price_includes_gst ?? true) && $gstRate > 0) {
            $price *= 1 + ($gstRate / 100);
        }

        return round($price, 2);
    }

    public function supportsVariableWeight(Product $product): bool
    {
        return (string) ($product->sell_unit ?? '') === 'kg'
            || (string) ($product->pack_type ?? '') === 'variable_weight';
    }

    public function formatWeightKg(float $weightKg): string
    {
        if ($weightKg < 1) {
            return $this->formatNumber($weightKg * 1000).' gms';
        }

        return $this->formatNumber($weightKg).' kg';
    }

    public function pdfAssets(): array
    {
        return [
            'fontRegularUrl' => public_path('fonts/RobotoMono-Regular.ttf'),
            'fontBoldUrl' => public_path('fonts/RobotoMono-Bold.ttf'),
            'logoUrl' => public_path('images/labels/bandara-mark.png'),
        ];
    }

    private function unitLabel(Product $product): string
    {
        $weightKg = (float) ($product->product_weight ?? 0);

        if ($weightKg > 0) {
            return $this->formatWeightKg($weightKg);
        }

        $pieces = (float) ($product->pieces_per_pack ?? 0);
        if ($pieces > 0) {
            return $this->formatNumber($pieces).' pcs';
        }

        return match ((string) ($product->sell_unit ?? 'piece')) {
            'kg' => '1 kg',
            'pack' => '1 pack',
            default => '1 pc',
        };
    }

    private function formatNumber(float $value): string
    {
        return abs($value - round($value)) < 0.00001
            ? number_format($value, 0)
            : rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function websiteUrl(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'https://bandarallp.com';
        }

        return preg_match('#^https?://#i', $value) ? $value : 'https://'.$value;
    }

    private function categoryFontSize(string $category): float
    {
        return match (true) {
            mb_strlen($category) <= 5 => 21.3,
            mb_strlen($category) <= 8 => 16.0,
            mb_strlen($category) <= 12 => 12.0,
            default => 9.0,
        };
    }

    private function productFontSize(string $name): float
    {
        return match (true) {
            mb_strlen($name) <= 20 => 10.0,
            mb_strlen($name) <= 28 => 8.2,
            mb_strlen($name) <= 36 => 7.0,
            default => 6.0,
        };
    }
}

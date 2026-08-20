<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductLabelBatchRequest;
use App\Http\Requests\Admin\ProductLabelRequest;
use App\Models\Product;
use App\Services\ProductLabelBatchService;
use App\Services\ProductLabelService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ProductLabelController extends Controller
{
    public function index(
        Request $request,
        ProductLabelService $labels,
        ProductLabelBatchService $batches,
    ): View {
        $search = trim((string) $request->input('q', ''));

        $products = Product::query()
            ->with('categories')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $products->getCollection()->each(function (Product $product) use ($labels, $batches): void {
            $product->setAttribute('label_mrp', $labels->retailMrp($product));
            $product->setAttribute('label_batch_enabled', $batches->supports($product));
        });

        return view('admin.product-labels.index', compact('products', 'search'));
    }

    public function edit(
        Product $product,
        ProductLabelService $labels,
        ProductLabelBatchService $batches,
    ): View {
        $form = $labels->defaults($product);
        $label = $labels->labelData($product, $form);

        return view('admin.product-labels.edit', [
            'product' => $product,
            'form' => $form,
            'label' => $label,
            'fontRegularUrl' => asset('fonts/RobotoMono-Regular.ttf'),
            'fontBoldUrl' => asset('fonts/RobotoMono-Bold.ttf'),
            'logoUrl' => asset('images/labels/bandara-mark.png'),
            'batchEnabled' => $batches->supports($product),
        ]);
    }

    public function batchEdit(
        Product $product,
        ProductLabelService $labels,
        ProductLabelBatchService $batches,
    ): View {
        abort_unless(
            $batches->supports($product),
            422,
            'Variable-weight batch labels require a product sold by kg or configured as variable weight.',
        );

        $form = $batches->defaults($product);
        $inventory = $batches->availableInventory($product);
        $firstInventoryItem = collect($inventory['pieces'])->concat($inventory['packs'])->first();
        $previewWeight = (float) ($firstInventoryItem['weight_kg'] ?? 1);
        $previewValues = array_merge($form, [
            'price' => round((float) $form['price_per_kg'] * $previewWeight, 2),
            'unit_label' => $labels->formatWeightKg($previewWeight),
            'best_before' => $firstInventoryItem['best_before'] ?? $form['best_before'],
            'copies' => 1,
        ]);

        return view('admin.product-labels.batch', [
            'product' => $product,
            'form' => $form,
            'inventory' => $inventory,
            'label' => $labels->labelData($product, $previewValues),
            'fontRegularUrl' => asset('fonts/RobotoMono-Regular.ttf'),
            'fontBoldUrl' => asset('fonts/RobotoMono-Bold.ttf'),
            'logoUrl' => asset('images/labels/bandara-mark.png'),
        ]);
    }

    public function pdf(
        ProductLabelRequest $request,
        Product $product,
        ProductLabelService $labels,
    ): Response {
        $values = $request->validated();
        $pdf = $labels->makePdf($product, $values);
        $filename = Str::slug($values['product_name'] ?: $product->name ?: 'product').'-label.pdf';
        $disposition = $values['disposition'] === 'download'
            ? HeaderUtils::DISPOSITION_ATTACHMENT
            : HeaderUtils::DISPOSITION_INLINE;

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function batchPdf(
        ProductLabelBatchRequest $request,
        Product $product,
        ProductLabelBatchService $batches,
    ): Response {
        abort_unless(
            $batches->supports($product),
            422,
            'Variable-weight batch labels require a product sold by kg or configured as variable weight.',
        );

        $values = $request->validated();
        $items = $batches->resolveItems($product, $values, $request->manualWeights());
        $pdf = $batches->makePdf($product, $values, $items);
        $filename = Str::slug($values['product_name'] ?: $product->name ?: 'product')
            .'-batch-'.count($items).'-labels.pdf';
        $disposition = $values['disposition'] === 'download'
            ? HeaderUtils::DISPOSITION_ATTACHMENT
            : HeaderUtils::DISPOSITION_INLINE;

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}

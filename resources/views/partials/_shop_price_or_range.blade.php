@php
    $pieceSelector = $product->piece_selector ?? ['enabled' => false];
    $hasPieceSelector = (bool) data_get($pieceSelector, 'enabled', false);
    $bands = data_get($pieceSelector, 'bands', []);

    $quote = app(\App\Services\PricingService::class)->quote(auth()->user(), $product);
    $effectivePrice = (float) ($quote['price'] ?? 0);
    $basePrice = (float) ($quote['compare_at_price'] ?? $product->base_price ?? 0);
    $isB2BPrice = ($quote['customer_type'] ?? 'b2c') === 'b2b';
    $isSpecialPrice = !$isB2BPrice && (bool) ($quote['is_special'] ?? false) && $effectivePrice > 0 && $basePrice > $effectivePrice;
    $moq = (float) ($quote['moq'] ?? 1);
    $priceTaxLabel = ($quote['display_price_includes_gst'] ?? false) ? 'incl GST' : 'excl GST';
@endphp

@if($hasPieceSelector)
    <div class="space-y-2" data-piece-range-card="{{ $product->id }}">
        <div class="space-y-1">
            <div class="text-[11px] font-medium text-gray-900 dark:text-gray-50">
                Variable weight
            </div>
            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                Choose a slab size range to continue.
            </div>
        </div>

        @if(!empty($bands))
            <div>
                <select
                    class="piece-range-select w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2.5 py-2 text-[11px] text-gray-700 dark:text-gray-200"
                    data-product-url="{{ route('product.show', $product) }}"
                >
                    @foreach($bands as $band)
                        <option
                            value="{{ $band['key'] }}"
                            data-label="{{ $band['label'] }}"
                            data-url="{{ route('product.show', $product) }}?band={{ urlencode($band['key']) }}#piece-selector-root"
                            data-selected-text="View {{ $band['label'] }}"
                            @selected($loop->first)
                        >
                            {{ $band['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <a
                href="{{ route('product.show', $product) }}?band={{ urlencode($bands[0]['key']) }}#piece-selector-root"
                class="piece-range-link inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800"
            >
                View {{ $bands[0]['label'] }}
            </a>
        @else
            <span class="inline-flex items-center justify-center rounded-sm border border-gray-200 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-400">
                Out of stock
            </span>
        @endif
    </div>

    @if(!empty($bands))
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.querySelector('[data-piece-range-card="{{ $product->id }}"]');
            if (!root || root.dataset.bound === 'true') return;
            root.dataset.bound = 'true';

            const select = root.querySelector('.piece-range-select');
            const link = root.querySelector('.piece-range-link');

            function updateRangeLink() {
                const opt = select.options[select.selectedIndex];
                const value = select.value;

                if (!value || !opt) {
                    link.href = select.dataset.productUrl + '#piece-selector-root';
                    link.textContent = 'View available slabs';
                    return;
                }

                link.href = opt.dataset.url || (select.dataset.productUrl + '#piece-selector-root');
                link.textContent = opt.dataset.selectedText || ('View ' + (opt.dataset.label || opt.textContent));
            }

            select.addEventListener('change', updateRangeLink);
            updateRangeLink();
        });
        </script>
    @endif
@else
    <div class="space-y-1">
        @if($isB2BPrice)
            @if($effectivePrice > 0)
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($effectivePrice, 2) }}</span>
                    <span class="text-[10px] rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">B2B {{ $priceTaxLabel }}</span>
                </div>
                @if($moq > 1)
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">MOQ: {{ rtrim(rtrim(number_format($moq, 3), '0'), '.') }}</div>
                @endif
            @else
                <div class="text-[11px] font-medium text-amber-700 dark:text-amber-300">B2B price pending</div>
            @endif
        @elseif($isSpecialPrice)
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($effectivePrice, 2) }}</span>
                <span class="text-[11px] text-gray-400 line-through">₹{{ number_format($basePrice, 2) }}</span>
            </div>
        @else
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($effectivePrice, 2) }}</div>
        @endif
    </div>

    @if(isset($mrp, $effective) && $mrp !== null && $mrp > 0 && $mrp > $effective)
        <div class="flex items-center gap-2">
            <span class="text-[11px] text-red-600 line-through">
                ₹{{ number_format($mrp, 2) }}
            </span>

            @php
                $offPct = $mrp > 0 ? (($mrp - $effective) / $mrp) * 100 : 0;
            @endphp

            @if($offPct > 0.5)
                <span class="text-[10px] font-semibold text-green-700 dark:text-green-300">
                    {{ number_format($offPct, 0) }}% OFF
                </span>
            @endif
        </div>
    @else
        @if(isset($effective, $base) && $product->is_special && $effective < $base)
            <span class="text-[11px] text-gray-400 line-through">
                ₹{{ number_format($base, 2) }}
            </span>
        @endif
    @endif
@endif
<footer class="border-t border-gray-200 dark:border-gray-800">
    <div class="px-4 sm:px-6 lg:px-8 py-3 text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-between">
        <span>Bandara LLP</span>
        <span>v1.0 · {{ now()->format('Y-m-d') }}</span>
    </div>

{{-- BANDARA-B2B-CORRECTIVE:FOOTER:START --}}
@include('partials.b2b-application.footer-links')
{{-- BANDARA-B2B-CORRECTIVE:FOOTER:END --}}
</footer>

@php
    $company = config('bandara_content.company');
@endphp

<section id="contact" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900/70 sm:p-6">
    <p class="text-xs font-normal uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Still need help?</p>
    <h2 class="mt-2 text-xl font-light tracking-tight text-slate-950 dark:text-white">Speak with Bandara</h2>
    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
        Include your order number and the email address or phone number linked to your account so the team can investigate quickly.
    </p>
    <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
        <a href="mailto:{{ $company['support_email'] }}" class="rounded-lg border border-slate-200 p-4 transition hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-500">
            <span class="block text-xs text-slate-500 dark:text-slate-400">Email</span>
            <span class="mt-1 block break-all text-slate-900 dark:text-white">{{ $company['support_email'] }}</span>
        </a>
        {{-- <a href="tel:{{ preg_replace('/\s+/', '', $company['support_phone']) }}" class="rounded-lg border border-slate-200 p-4 transition hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-500">
            <span class="block text-xs text-slate-500 dark:text-slate-400">Phone / WhatsApp</span>
            <span class="mt-1 block text-slate-900 dark:text-white">{{ $company['support_phone'] }}</span>
        </a> --}}
        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-700 sm:col-span-2 lg:col-span-1">
            <span class="block text-xs text-slate-500 dark:text-slate-400">Support hours</span>
            <span class="mt-1 block text-slate-900 dark:text-white">{{ $company['support_hours'] }}</span>
        </div>
    </div>
</section>

@extends('layouts.customer')

@section('title', 'Terms & Policies')

@push('head')
    <meta name="description" content="Bandara terms covering orders, delivery, cancellations, perishable-product claims, payments, Bandara Credit and B2B supply.">
@endpush

@section('content')
    @include('pages.partials.content-nav')

    @php
        $company = config('bandara_content.company');
        $delivery = config('bandara_content.delivery');
        $rewards = config('bandara_content.rewards');
    @endphp

        <div class="bg-gray-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    <div class="min-h-screen bg-stone-50 text-slate-800 dark:bg-slate-950 dark:text-slate-200">
        <header class="border-b border-stone-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Legal and commercial information</p>
                <h1 class="mt-4 max-w-4xl text-4xl font-light tracking-[-0.03em] text-slate-950 dark:text-white sm:text-5xl">Terms & Policies</h1>
                <p class="mt-5 max-w-3xl text-sm font-light leading-7 text-slate-600 dark:text-slate-300 sm:text-base">These terms govern use of the Bandara website and purchases from {{ $company['legal_name'] }}. Consumer and business-specific provisions are identified where they differ.</p>
                @include('pages.partials.policy-meta')
            </div>
        </header>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[0.28fr_0.72fr] lg:px-8 lg:py-16">
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">On this page</p>
                <nav aria-label="Terms sections" class="mt-4 grid gap-1 text-sm">
                    @foreach ([
                        'scope' => '1. Scope and acceptance',
                        'accounts' => '2. Accounts and eligibility',
                        'products' => '3. Products and availability',
                        'pricing' => '4. Pricing, tax and payment',
                        'orders' => '5. Order acceptance',
                        'delivery' => '6. Delivery',
                        'cancellation' => '7. Cancellation',
                        'returns' => '8. Replacement and refunds',
                        'handling' => '9. Storage and food handling',
                        'credit' => '10. Bandara Credit',
                        'b2b' => '11. B2B terms',
                        'website-use' => '12. Website use and content',
                        'liability' => '13. Liability and force majeure',
                        'grievance' => '14. Complaints and disputes',
                        'changes' => '15. Changes and contact',
                    ] as $id => $label)
                        <a href="#{{ $id }}" class="rounded-md px-3 py-2 text-slate-600 transition hover:bg-white hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">{{ $label }}</a>
                    @endforeach
                </nav>
            </aside>

            <article class="space-y-12 text-sm font-light leading-7 text-slate-600 dark:text-slate-300 [&_h2]:text-2xl [&_h2]:font-light [&_h2]:tracking-tight [&_h2]:text-slate-950 dark:[&_h2]:text-white [&_h3]:mt-6 [&_h3]:font-normal [&_h3]:text-slate-900 dark:[&_h3]:text-slate-100 [&_li]:ml-5 [&_li]:list-disc [&_p+p]:mt-4 [&_ul]:mt-4 [&_ul]:space-y-2">
                <section id="scope" class="scroll-mt-28">
                    <h2>1. Scope and acceptance</h2>
                    <p>These Terms & Policies apply to the shopping website at {{ $company['shopping_domain'] }}, related Bandara account features, and orders accepted by {{ $company['legal_name'] }}. References to “Bandara”, “we”, “us” or “our” mean {{ $company['legal_name'] }}. “Customer” includes a B2C consumer and, where applicable, an approved B2B buyer.</p>
                    <p>By creating an account, placing an order or using a Bandara service, you agree to the terms relevant to that activity. The Privacy Policy explains how personal data is handled.</p>
                </section>

                <section id="accounts" class="scroll-mt-28">
                    <h2>2. Accounts and eligibility</h2>
                    <p>A person placing an order must be legally capable of entering into a contract. A person under 18 should use Bandara only through, or with the involvement of, a parent or legal guardian.</p>
                    <p>You are responsible for accurate account, address and contact details and for protecting your password, PIN, OTP and other credentials. Report suspected unauthorised access promptly. Bandara may restrict or suspend an account where fraud, misuse, abusive behaviour, repeated false claims, security risk or unlawful activity is reasonably suspected.</p>
                </section>

                <section id="products" class="scroll-mt-28">
                    <h2>3. Product information and availability</h2>
                    <p>Product photographs are representative. Natural products may vary in colour, marbling, size, shape, cut and appearance. Fixed-weight packs are supplied according to the declared net quantity. Individually selected or catch-weight items are charged using the weight shown in the product selection, package, order record or invoice.</p>
                    <p>Adding an item to a cart does not reserve stock. Availability is checked again during checkout and confirmation. If an item becomes unavailable, Bandara may offer an alternative, seek approval for partial fulfilment, defer the item, or cancel and refund the unavailable portion.</p>
                    <h3>Substitutions</h3>
                    <p>Bandara does not make a material substitution without customer consent. We will explain relevant differences in product, brand, weight, price, ingredients or storage condition. We will not knowingly substitute in a way that changes an allergen, dietary classification, animal species or another material characteristic without explicit approval.</p>
                </section>

                <section id="pricing" class="scroll-mt-28">
                    <h2>4. Pricing, GST and payment</h2>
                    <p>B2C prices are displayed inclusive of applicable GST unless clearly stated otherwise. B2B prices are ordinarily displayed exclusive of GST, with tax shown separately in the order or invoice.</p>
                    <p>Online payments may be processed through Razorpay using the methods made available at checkout, including eligible cards, UPI, net banking or supported wallets. Cash on Delivery is not available. Approved B2B customers may also use NEFT, RTGS, IMPS or cheque, subject to verification or realisation. Cash is not accepted for B2B invoices.</p>
                    <p>If money is debited but no confirmation is received, avoid an immediate duplicate payment and contact support with the transaction reference. Bandara will reconcile a verified receipt to the intended order or initiate a refund. Where Bandara did not receive the amount, reversal timing is controlled by the bank or payment provider.</p>
                    <h3>Pricing errors</h3>
                    <p>Where Bandara accepts an order at an ordinary undercharge caused by our error, we will generally honour the confirmed price. An overcharge will be corrected and the excess refunded, with any associated Bandara Credit adjustment. Before dispatch, Bandara may correct or cancel an order affected by a manifest technical error, an incompatible discount, manipulation, an unlawful price or an error a reasonable customer would recognise as unintended. Any amount collected for a cancelled order will be refunded.</p>
                </section>

                <section id="orders" class="scroll-mt-28">
                    <h2>5. Order submission and acceptance</h2>
                    <p>Submitting an order is an offer to buy. A B2C order is normally accepted when successful payment has been verified and Bandara issues its confirmation. A B2B order is accepted when Bandara confirms it after reviewing stock, pricing, MOQ, delivery feasibility, account status, credit limit and payment requirements.</p>
                    <p>An automated gateway acknowledgment does not by itself guarantee acceptance. Bandara may cancel before dispatch where stock is unavailable, the address is not serviceable, payment or credit verification fails, suspected fraud is identified, fulfilment would be unsafe, or a manifest technical or pricing error has occurred.</p>
                </section>

                <section id="delivery" class="scroll-mt-28">
                    <h2>6. Delivery</h2>
                    <h3>Pune delivery timing</h3>
                    <p>Orders confirmed by {{ $delivery['same_day_cutoff'] }} are normally delivered within Pune on the same day, subject to stock, traffic, capacity and serviceability. Orders confirmed later are normally delivered on the next delivery day, although earlier fulfilment may be possible. Delivery operates seven days a week, subject to notified closures and events beyond Bandara’s reasonable control.</p>
                    <h3>B2C charges</h3>
                    <p>The standard eligible B2C charge is ₹{{ $delivery['b2c_base_fee'] }} for the first {{ $delivery['b2c_base_km'] }} kilometres and ₹{{ $delivery['b2c_extra_per_km'] }} for each additional kilometre. The amount calculated and displayed before confirmation applies to the order. Cold-chain, packaging or handling charges may apply where displayed.</p>
                    <h3>Local and intercity B2B</h3>
                    <p>Local B2B delivery is free for the first {{ $delivery['b2b_free_km'] }} kilometres and ₹{{ $delivery['b2b_extra_per_km'] }} for each additional kilometre. Intercity B2B delivery is quoted separately for each case and is ordinarily considered for shipments of at least {{ $delivery['intercity_b2b_min_kg'] }} kg unless Bandara agrees otherwise.</p>
                    <h3>Handover and failed delivery</h3>
                    <p>At the customer’s recorded request, Bandara may hand an order to security, reception, an employee, a neighbour or another authorised recipient. Delivery is complete when handed to that person. If no authorised recipient is available, redelivery depends on product condition, cold-chain integrity, shelf life and capacity, and an additional charge may apply. A perishable order may not qualify for refund where safe redelivery is no longer possible because the customer or authorised recipient was unavailable.</p>
                    <p>If an address or phone issue is found before dispatch, Bandara may hold the order and contact the customer. If no response is received within 48 hours, we may cancel and refund it. Weather, flooding, road closure, public disruption, power failure, transport interruption or similar events may delay delivery; if safe fulfilment is no longer possible, Bandara may cancel the unfulfilled portion and refund it.</p>
                </section>

                <section id="cancellation" class="scroll-mt-28">
                    <h2>7. Cancellation</h2>
                    <p>A customer may cancel before the order has been dispatched or marked out for delivery. Cancellation may remain possible after picking or packing because ordinary products are generally maintained in pre-cut or pre-packed saleable form. Cancellation is not normally available after dispatch.</p>
                    <p>A valid prepaid cancellation before dispatch is refunded in full, including order-level delivery, cold-chain and handling charges collected for that cancelled order. A confirmed B2B change or cancellation remains subject to Bandara’s approval and may revise quantity, price, tax, delivery or invoice details.</p>
                </section>

                <section id="returns" class="scroll-mt-28">
                    <h2>8. Returns, replacement and refunds</h2>
                    <p>Frozen, chilled and other perishable food cannot be returned or exchanged merely because the customer changed their mind, ordered the wrong quantity, no longer requires it, or does not personally prefer its taste, texture, appearance or cooking characteristics.</p>
                    <p>Bandara will review claims involving a wrong or missing item, materially damaged or unsealed packaging, a product received completely thawed or apparently unsafe, an expired product, a verified defect, or a material difference from the confirmed order.</p>
                    <h3>Reporting and evidence</h3>
                    <p>Visible delivery issues should normally be reported within one hour of handover. A quality concern that could not reasonably be detected then should be reported as soon as discovered, preferably within 24 hours, before the use-by or expiry date. These reporting periods support investigation and do not limit rights that cannot legally be excluded.</p>
                    <p>Provide the order number and clear photographs or video of the product, outer packaging, seal, label, batch or lot details, date information and the issue. Keep the product and original packaging under the stated storage conditions until Bandara confirms disposal or arranges collection.</p>
                    <h3>Resolution</h3>
                    <p>After review, Bandara may agree a replacement, refund or Bandara Credit with the customer. Where replacement is unavailable or cannot be safely delivered within a reasonable time, a refund will normally be offered. Monetary refunds are normally initiated within 48 hours; banks and payment providers may require additional time to display them.</p>
                    <p>If the entire order fails because of an issue attributable to Bandara, related delivery, cold-chain and handling charges will also be refunded. For a claim concerning only one item in an otherwise correct order, Bandara will resolve the affected item and charges directly attributable to it; general order-level charges are not automatically refundable.</p>
                    <p>Opening a product solely to inspect or prepare it does not automatically defeat a genuine claim. Verification may be impossible where the product was substantially consumed, discarded, altered, improperly stored, or retained without its original packaging and label. Custom-cut, sliced, repacked, dairy, bakery and other perishable items remain non-returnable for change of mind but retain eligible remedies for wrong supply, verified damage, safety or material defects.</p>
                </section>

                <section id="handling" class="scroll-mt-28">
                    <h2>9. Storage, thawing and food information</h2>
                    <p>Store frozen products at or below −18°C and chilled products at the temperature stated on the product page or physical label, commonly 2–4°C. Transfer perishables to appropriate storage promptly after delivery.</p>
                    <p>Follow the physical label and product-specific instructions for thawing and preparation. Unless the label states otherwise, thaw under refrigeration rather than in standing water or a microwave. Do not refreeze after complete thawing unless the manufacturer expressly permits it.</p>
                    <p>Ingredients, allergens, manufacturer or importer information, country of origin and preparation directions should be reviewed on the physical label before use. Where online information differs materially from the delivered label, do not consume the product until Bandara clarifies the difference. Customers with allergies must review the declaration and any “may contain” cross-contact statement carefully.</p>
                </section>

                <section id="credit" class="scroll-mt-28">
                    <h2>10. Bandara Credit</h2>
                    <p>Bandara Credit is available only to eligible registered B2C customers. B2B accounts and B2B purchases do not earn or redeem it. Credits may be earned on qualifying merchandise spend and used against eligible future orders as shown at checkout.</p>
                    <p>Current tiers are Silver (₹{{ number_format($rewards['silver_min']) }}–₹{{ number_format($rewards['silver_max']) }}), Gold (₹{{ number_format($rewards['gold_min']) }}–₹{{ number_format($rewards['gold_max']) }}) and Platinum (₹{{ number_format($rewards['platinum_min']) }} and above), assessed using the applicable rolling eligible-spend rules.</p>
                    <p>Credits may remain pending until payment and order-completion conditions are met. They are personal, non-transferable, not legal tender and cannot be withdrawn as cash. Credit earned from a cancelled or refunded order may be reversed; redeemed credit is restored in proportion to the amount actually refunded, subject to fraud, misuse or valid adjustments.</p>
                    <p>Coupon stacking, promotional bonuses, earning exclusions, redemption limits and birthday benefits depend on the programme rules displayed at the relevant time. Bandara may amend, suspend or withdraw a programme feature prospectively, with material changes reflected in the current terms.</p>
                </section>

                <section id="b2b" class="scroll-mt-28">
                    <h2>11. Additional B2B commercial terms</h2>
                    <p>B2B prices, MOQ, product visibility, credit limits and payment terms may be assigned specifically to the approved customer account. Approved Pay Later access is subject to the credit period, limit and due date shown in the account or invoice.</p>
                    <p>Partial payments are accepted where authorised for the relevant account, order or invoice. The unpaid balance remains due by the stated date. Bandara may hold procurement, preparation, dispatch or further orders until the required advance or overdue amount is received, and may suspend Pay Later or revise the credit limit.</p>
                    <p>B2B returns require prior recorded approval. Approved cases may be resolved by replacement, credit note, account adjustment or refund, provided the goods remain identifiable and were properly stored. A separate written quotation or commercial agreement may add order-specific terms for specially sourced, imported, custom-prepared or intercity supply.</p>
                </section>

                <section id="website-use" class="scroll-mt-28">
                    <h2>12. Website use, intellectual property and reviews</h2>
                    <p>Bandara owns or lawfully uses the website, brand, text, design, photographs, graphics, software and other content. Personal shopping use is permitted; copying, scraping, systematic extraction, republication or commercial exploitation requires written permission unless law permits otherwise.</p>
                    <p>Do not use the service unlawfully, impersonate another person, submit false information, interfere with security or availability, introduce malware, attempt unauthorised access, manipulate prices or payments, misuse coupons or Bandara Credit, or make knowingly false claims.</p>
                    <p>Reviews must reflect genuine experience and must not be unlawful, abusive, discriminatory, defamatory, infringing, deceptive or unrelated. Any review incentive must be disclosed. By submitting content, the contributor grants Bandara a non-exclusive, worldwide, royalty-free licence to store, format, reproduce and display it in connection with Bandara’s business. Bandara may moderate content that breaches these rules without changing a legitimate review into a materially different opinion.</p>
                </section>

                <section id="liability" class="scroll-mt-28">
                    <h2>13. Liability and events beyond control</h2>
                    <p>Nothing in these terms excludes rights or liabilities that cannot lawfully be excluded, including mandatory consumer and food-safety remedies or liability for fraud. To the extent permitted by law, Bandara is responsible for direct, reasonably foreseeable loss caused by its breach, but not indirect or consequential loss, customer-side cold-chain failure, improper storage or use, or loss caused by circumstances beyond reasonable control.</p>
                    <p>Events beyond reasonable control may include severe weather, flooding, fire, epidemic, government action, road closure, transport interruption, power or telecommunications failure, supplier disruption, labour disturbance or an unexpected equipment or cold-chain failure not caused by a lack of reasonable care. Bandara will take reasonable mitigation steps and may cancel and refund the unfulfilled portion where safe performance is no longer reasonably possible.</p>
                </section>

                <section id="grievance" class="scroll-mt-28">
                    <h2>14. Customer support, grievances and disputes</h2>
                    <p>Raise an order, payment, delivery, account or product concern first with Customer Support at <a href="mailto:{{ $company['support_email'] }}" class="underline underline-offset-4">{{ $company['support_email'] }}</a> or {{ $company['support_phone'] }} during {{ $company['support_hours'] }}.</p>
                    <p>An unresolved matter may be escalated to <strong class="font-normal text-slate-900 dark:text-slate-100">{{ $company['grievance']['name'] }}, {{ $company['grievance']['designation'] }}</strong> at <a href="mailto:{{ $company['grievance']['email'] }}" class="underline underline-offset-4">{{ $company['grievance']['email'] }}</a>. Include the order number, account contact, a clear description and supporting material.</p>
                    <p>These terms are governed by Indian law. Subject to mandatory rights to approach an appropriate consumer commission or statutory forum, courts at Pune, Maharashtra have jurisdiction. The parties should first attempt resolution through Bandara’s support and grievance process.</p>
                </section>

                <section id="changes" class="scroll-mt-28">
                    <h2>15. Changes and company details</h2>
                    <p>Bandara may update these terms. The current version and effective date will be displayed here. Changes ordinarily apply prospectively; material changes will be notified through the website, account or available contact details where appropriate or legally required.</p>
                    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900/70">
                        <p><strong class="font-normal text-slate-900 dark:text-slate-100">{{ $company['legal_name'] }}</strong><br>{{ $company['registered_address'] }}<br>GSTIN: {{ $company['gstin'] }}<br>FSSAI licence: {{ $company['fssai'] }}</p>
                    </div>
                    <p class="mt-5">For a quick explanation, visit <a href="{{ route('content.help') }}" class="underline underline-offset-4">Help & FAQs</a>. Personal-data practices are explained in the <a href="{{ route('content.privacy') }}" class="underline underline-offset-4">Privacy Policy</a>.</p>
                </section>
            </article>
        </div>
    </div>
    </div>
    </div>
@endsection

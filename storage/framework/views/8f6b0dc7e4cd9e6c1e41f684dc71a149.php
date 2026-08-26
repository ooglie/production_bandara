<?php $__env->startSection('title', 'Privacy Policy'); ?>

<?php $__env->startPush('head'); ?>
    <meta name="description" content="How Bandara collects, uses, shares, protects and retains personal information for accounts, orders, delivery, payments and support.">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('pages.partials.content-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $company = config('bandara_content.company');
    ?>

        <div class="bg-gray-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
    <div class="min-h-screen bg-stone-50 text-slate-800 dark:bg-slate-950 dark:text-slate-200">
        <header class="border-b border-stone-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Your information</p>
                <h1 class="mt-4 max-w-4xl text-4xl font-light tracking-[-0.03em] text-slate-950 dark:text-white sm:text-5xl">Privacy Policy</h1>
                <p class="mt-5 max-w-3xl text-sm font-light leading-7 text-slate-600 dark:text-slate-300 sm:text-base">This policy explains how <?php echo e($company['legal_name']); ?> handles personal data when you browse, register, place an order, use B2B terms, contact support or interact with delivery services.</p>
                <?php echo $__env->make('pages.partials.policy-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </header>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[0.28fr_0.72fr] lg:px-8 lg:py-16">
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">On this page</p>
                <nav aria-label="Privacy sections" class="mt-4 grid gap-1 text-sm">
                    <?php $__currentLoopData = [
                        'who-we-are' => '1. Who we are',
                        'data-collected' => '2. Information collected',
                        'collection' => '3. How it is collected',
                        'uses' => '4. Why we use it',
                        'payments-location' => '5. Payments and location',
                        'sharing' => '6. Sharing and providers',
                        'cookies' => '7. Cookies and security tools',
                        'retention' => '8. Retention',
                        'security' => '9. Security',
                        'choices' => '10. Your choices and requests',
                        'children' => '11. Children',
                        'changes-contact' => '12. Changes and contact',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="#<?php echo e($id); ?>" class="rounded-md px-3 py-2 text-slate-600 transition hover:bg-white hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"><?php echo e($label); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>
            </aside>

            <article class="space-y-12 text-sm font-light leading-7 text-slate-600 dark:text-slate-300 [&_h2]:text-2xl [&_h2]:font-light [&_h2]:tracking-tight [&_h2]:text-slate-950 dark:[&_h2]:text-white [&_h3]:mt-6 [&_h3]:font-normal [&_h3]:text-slate-900 dark:[&_h3]:text-slate-100 [&_li]:ml-5 [&_li]:list-disc [&_p+p]:mt-4 [&_ul]:mt-4 [&_ul]:space-y-2">
                <section id="who-we-are" class="scroll-mt-28">
                    <h2>1. Who we are</h2>
                    <p><?php echo e($company['legal_name']); ?> operates the Bandara shopping service at <?php echo e($company['shopping_domain']); ?>. For privacy questions, email <a href="mailto:<?php echo e($company['privacy_email']); ?>" class="underline underline-offset-4"><?php echo e($company['privacy_email']); ?></a>. The registered address is <?php echo e($company['registered_address']); ?>.</p>
                </section>

                <section id="data-collected" class="scroll-mt-28">
                    <h2>2. Information we collect</h2>
                    <p>The information depends on the way you use Bandara and may include:</p>
                    <ul>
                        <li>name, email address, phone number and date of birth;</li>
                        <li>delivery, billing and saved address details;</li>
                        <li>account login, security and preference information;</li>
                        <li>cart, wishlist, order, invoice, return, refund and Bandara Credit records;</li>
                        <li>payment status, gateway identifiers, bank-transfer or cheque references and reconciliation information;</li>
                        <li>B2B business details, pricing, MOQ, credit limits, payment terms and account correspondence;</li>
                        <li>support tickets, messages, attachments, complaints and grievance communications;</li>
                        <li>device, browser, IP address, session, security and technical logs reasonably generated by the website.</li>
                    </ul>
                    <p>Date of birth is used for birthday-related Bandara Credit or account benefits and reasonable account validation. It is not used for general advertising.</p>
                </section>

                <section id="collection" class="scroll-mt-28">
                    <h2>3. How information is collected</h2>
                    <p>We receive information directly when you register, sign in, enter an address, place or pay for an order, submit B2B information, raise a ticket, make a complaint or contact us. We also receive transaction status and technical information from service providers used to complete or secure those activities.</p>
                    <p>Bandara may derive geographic coordinates, serviceability and delivery distance from an address entered by the customer. This does not mean that Bandara continuously tracks the customer’s device location.</p>
                </section>

                <section id="uses" class="scroll-mt-28">
                    <h2>4. Why we use personal data</h2>
                    <p>Bandara uses personal data to:</p>
                    <ul>
                        <li>create, authenticate and secure accounts;</li>
                        <li>display customer-specific B2B prices, MOQ, credit and payment terms;</li>
                        <li>process orders, payments, invoices, refunds and account adjustments;</li>
                        <li>verify serviceability, calculate delivery fees and complete delivery;</li>
                        <li>operate Bandara Credit and apply eligible birthday or purchase benefits;</li>
                        <li>provide support, investigate complaints and prevent fraud or misuse;</li>
                        <li>maintain business, tax, food-safety, accounting and audit records;</li>
                        <li>operate, troubleshoot and protect the website and related systems;</li>
                        <li>comply with applicable law and lawful requests.</li>
                    </ul>
                    <p>Bandara does not currently use Google Analytics, Meta Pixel or another general advertising analytics tool and does not currently send general marketing through email, SMS or WhatsApp. Necessary service communications about verification, security, orders, delivery, payment, refunds and support may still be sent.</p>
                </section>

                <section id="payments-location" class="scroll-mt-28">
                    <h2>5. Payments, maps and address-based location</h2>
                    <h3>Payments</h3>
                    <p>Eligible online payments are handled through Razorpay. Bandara receives payment status, transaction references and reconciliation information needed to confirm an order, issue a refund or investigate a problem. Complete card credentials are entered into the payment provider’s secure flow rather than being intentionally stored by Bandara.</p>
                    <h3>Maps and delivery distance</h3>
                    <p>Google mapping or distance services may process an entered delivery address or derived coordinates to confirm serviceability, estimate distance, calculate delivery charges and assist delivery. Assigned delivery personnel may receive the recipient name, address, phone number and instructions reasonably needed for the assigned delivery.</p>
                </section>

                <section id="sharing" class="scroll-mt-28">
                    <h2>6. When information is shared</h2>
                    <p>Bandara shares personal data only to the extent reasonably necessary for the purpose involved, including with:</p>
                    <ul>
                        <li>Razorpay and relevant banks or payment networks for payments, reconciliation and refunds;</li>
                        <li>Google services used for maps, distance calculation, translation and reCAPTCHA;</li>
                        <li>IBM or other authorised infrastructure, hosting and backup providers;</li>
                        <li>couriers, cold-chain carriers and assigned delivery personnel;</li>
                        <li>professional advisers, auditors, insurers or authorities where necessary or legally required;</li>
                        <li>a successor organisation in a lawful restructuring, merger or transfer, subject to appropriate protections.</li>
                    </ul>
                    <p>Service providers are expected to use information for the contracted or legally permitted purpose and to apply appropriate safeguards. Bandara does not sell customer personal data.</p>
                </section>

                <section id="cookies" class="scroll-mt-28">
                    <h2>7. Cookies and security tools</h2>
                    <p>Bandara uses necessary cookies or similar storage for sessions, login, cart continuity, security, preferences and core website operation. Google reCAPTCHA may process device and interaction signals to distinguish legitimate use from automated abuse. Google Translate may process page text or language selections when translation is used.</p>
                    <p>Blocking necessary cookies may prevent login, cart, checkout or account features from working correctly. Any future non-essential analytics or advertising technology should be described here before it is activated.</p>
                </section>

                <section id="retention" class="scroll-mt-28">
                    <h2>8. How long information is kept</h2>
                    <p>Bandara keeps personal data only for as long as reasonably necessary for the purpose collected. Active account and operational records may be reviewed after one financial year, but invoices, orders, tax, accounting, food-safety, fraud-prevention, dispute and audit records may be retained for the longer period required by applicable law or while a proceeding or claim remains open.</p>
                    <p>When data is no longer required, Bandara will delete, anonymise or securely isolate it in accordance with applicable retention and backup processes.</p>
                </section>

                <section id="security" class="scroll-mt-28">
                    <h2>9. Security</h2>
                    <p>Bandara applies reasonable administrative, technical and organisational controls designed to protect personal data, including access restriction, authentication, logging, secure payment-provider flows, backups and system maintenance. No online system can be guaranteed absolutely secure.</p>
                    <p>Customers should use a unique password or PIN, protect OTPs, sign out on shared devices and report suspected unauthorised access. Bandara will not ask for a complete password or PIN through an unsolicited message.</p>
                </section>

                <section id="choices" class="scroll-mt-28">
                    <h2>10. Your choices and requests</h2>
                    <p>Subject to applicable law and necessary verification, you may request access to or correction of account information, raise a concern about processing, or request account deletion through the inbuilt support ticket system or by contacting support.</p>
                    <p>An account-deletion request does not require Bandara to erase records that must be kept for tax, accounting, food-safety, fraud prevention, legal claims or another lawful obligation. Where deletion is not immediately possible, access may be restricted and the reason explained.</p>
                    <p>Privacy enquiries may be sent to <a href="mailto:<?php echo e($company['privacy_email']); ?>" class="underline underline-offset-4"><?php echo e($company['privacy_email']); ?></a>. General order support remains available at <a href="mailto:<?php echo e($company['support_email']); ?>" class="underline underline-offset-4"><?php echo e($company['support_email']); ?></a>.</p>
                </section>

                <section id="children" class="scroll-mt-28">
                    <h2>11. Children</h2>
                    <p>Bandara’s shopping service is intended for people legally capable of placing an order. A person under 18 should use it only through, or with the involvement of, a parent or legal guardian. A guardian who believes a child provided personal data without appropriate involvement should contact the privacy email.</p>
                </section>

                <section id="changes-contact" class="scroll-mt-28">
                    <h2>12. Changes, complaints and contact</h2>
                    <p>Bandara may update this policy to reflect changes in services, providers, security practices or applicable law. The current version and effective date will appear on this page, with reasonable notice of material changes where appropriate or required.</p>
                    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900/70">
                        <p><strong class="font-normal text-slate-900 dark:text-slate-100">Privacy contact</strong><br><a href="mailto:<?php echo e($company['privacy_email']); ?>" class="underline underline-offset-4"><?php echo e($company['privacy_email']); ?></a><br><?php echo e($company['registered_address']); ?></p>
                        <p class="mt-4"><strong class="font-normal text-slate-900 dark:text-slate-100">Grievance Officer</strong><br><?php echo e($company['grievance']['name']); ?>, <?php echo e($company['grievance']['designation']); ?><br><a href="mailto:<?php echo e($company['grievance']['email']); ?>" class="underline underline-offset-4"><?php echo e($company['grievance']['email']); ?></a></p>
                    </div>
                    <p class="mt-5">For order and service questions, see <a href="<?php echo e(route('content.help')); ?>" class="underline underline-offset-4">Help & FAQs</a>. Contractual terms are in <a href="<?php echo e(route('content.terms')); ?>" class="underline underline-offset-4">Terms & Policies</a>.</p>
                </section>
            </article>
        </div>
    </div>
    </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/pages/privacy.blade.php ENDPATH**/ ?>
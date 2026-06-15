Delivery address fee refresh fix

Changed file:
- resources/views/customer/checkout/index.blade.php

What changed:
- Address radio buttons now trigger a checkout totals refresh using the existing checkout GET route with address_id.
- The returned checkout HTML is parsed and only the payment method block, Bandara Credit block, and totals block are replaced.
- The selected customer note remains untouched because the page is not fully reloaded during a successful refresh.
- If the AJAX refresh fails, the browser falls back to loading /checkout?address_id=... so the server still recalculates the fee.
- B2B payment method selection is preserved when still eligible and falls back to Razorpay if Pay Later is no longer eligible after the selected address changes the total.

Validation performed here:
- php -l resources/views/customer/checkout/index.blade.php passed.
- php artisan route:list could not be run in this container because the bundled Composer platform check requires PHP >= 8.5.0, while this container has PHP 8.4.16.

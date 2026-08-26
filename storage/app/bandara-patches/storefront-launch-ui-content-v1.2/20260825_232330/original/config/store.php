<?php

return [
    // Online checkout stock is held only after the customer starts checkout/payment.
    // Keep this short so abandoned payment attempts do not block stock for long.
    'stock_reservation_ttl_minutes' => (int) env('STOCK_RESERVATION_TTL_MINUTES', 5),

    // Where invoice copies go for accounting
    'accountant_email' => env('STORE_ACCOUNTANT_EMAIL', null),

    'invoice' => [
        'seller' => [
            'signature_name' => env('STORE_INVOICE_SIGNATURE_NAME', 'For Bandara by Maytira'),
            'fssai_no' => env('STORE_INVOICE_FSSAI_NO', '21526079001348'),
            'gstin_no' => env('STORE_INVOICE_GSTIN_NO', '27ABEFB3240N1ZE'),
            'address' => env('STORE_INVOICE_ADDRESS', '303B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001. MH. India'),
        ],

        'bank' => [
            'account_no' => env('STORE_INVOICE_BANK_ACCOUNT_NO', '129663700000319'),
            'account_name' => env('STORE_INVOICE_BANK_ACCOUNT_NAME', 'Bandara LLP'),
            'ifsc' => env('STORE_INVOICE_BANK_IFSC', 'YESB0001296'),
            'bank_name' => env('STORE_INVOICE_BANK_NAME', 'Yes Bank Ltd.'),
        ],

        // Static UPI QR payload shown beside the bank details.
        'qr_payload' => env('STORE_INVOICE_QR_PAYLOAD', 'upi://pay?mc=5811&pa=yespay.bizsbiz229338@yesbankltd&pn=BANDARA LLP'),
    ],
];

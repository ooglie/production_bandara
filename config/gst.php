<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Indian GST place-of-supply configuration
    |--------------------------------------------------------------------------
    |
    | Bandara's supplier state is derived from the seller GSTIN configured for
    | invoices.  Address state codes are the application's two-letter codes;
    | gst_code is the two-digit state/UT code used at the start of a GSTIN.
    |
    */
    'supplier_gstin' => env('STORE_INVOICE_GSTIN_NO', '27ABEFB3240N1ZE'),

    'states' => [
        '01' => ['code' => 'JK', 'name' => 'Jammu and Kashmir'],
        '02' => ['code' => 'HP', 'name' => 'Himachal Pradesh'],
        '03' => ['code' => 'PB', 'name' => 'Punjab'],
        '04' => ['code' => 'CH', 'name' => 'Chandigarh'],
        '05' => ['code' => 'UK', 'name' => 'Uttarakhand'],
        '06' => ['code' => 'HR', 'name' => 'Haryana'],
        '07' => ['code' => 'DL', 'name' => 'Delhi'],
        '08' => ['code' => 'RJ', 'name' => 'Rajasthan'],
        '09' => ['code' => 'UP', 'name' => 'Uttar Pradesh'],
        '10' => ['code' => 'BR', 'name' => 'Bihar'],
        '11' => ['code' => 'SK', 'name' => 'Sikkim'],
        '12' => ['code' => 'AR', 'name' => 'Arunachal Pradesh'],
        '13' => ['code' => 'NL', 'name' => 'Nagaland'],
        '14' => ['code' => 'MN', 'name' => 'Manipur'],
        '15' => ['code' => 'MZ', 'name' => 'Mizoram'],
        '16' => ['code' => 'TR', 'name' => 'Tripura'],
        '17' => ['code' => 'ML', 'name' => 'Meghalaya'],
        '18' => ['code' => 'AS', 'name' => 'Assam'],
        '19' => ['code' => 'WB', 'name' => 'West Bengal'],
        '20' => ['code' => 'JH', 'name' => 'Jharkhand'],
        '21' => ['code' => 'OD', 'name' => 'Odisha'],
        '22' => ['code' => 'CG', 'name' => 'Chhattisgarh'],
        '23' => ['code' => 'MP', 'name' => 'Madhya Pradesh'],
        '24' => ['code' => 'GJ', 'name' => 'Gujarat'],
        // Both prefixes remain in the official GST master. The application
        // presents the merged Union Territory as one address option (DH).
        '25' => ['code' => 'DH', 'name' => 'Daman and Diu', 'canonical_for_address' => false],
        '26' => ['code' => 'DH', 'name' => 'Dadra and Nagar Haveli and Daman and Diu', 'canonical_for_address' => true],
        '27' => ['code' => 'MH', 'name' => 'Maharashtra'],
        '29' => ['code' => 'KA', 'name' => 'Karnataka'],
        '30' => ['code' => 'GA', 'name' => 'Goa'],
        '31' => ['code' => 'LD', 'name' => 'Lakshadweep'],
        '32' => ['code' => 'KL', 'name' => 'Kerala'],
        '33' => ['code' => 'TN', 'name' => 'Tamil Nadu'],
        '34' => ['code' => 'PY', 'name' => 'Puducherry'],
        '35' => ['code' => 'AN', 'name' => 'Andaman and Nicobar Islands'],
        '36' => ['code' => 'TS', 'name' => 'Telangana'],
        '37' => ['code' => 'AP', 'name' => 'Andhra Pradesh'],
        '38' => ['code' => 'LA', 'name' => 'Ladakh'],
    ],
];

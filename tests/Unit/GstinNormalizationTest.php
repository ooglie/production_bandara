<?php

namespace Tests\Unit;

use App\Models\CustomerAddress;
use App\Models\User;
use Tests\TestCase;

class GstinNormalizationTest extends TestCase
{
    public function test_customer_and_address_gstin_values_are_normalized_before_storage(): void
    {
        $user = new User();
        $user->gst_number = ' 27abcde-1234f1z5 ';

        $address = new CustomerAddress();
        $address->gstin = ' 29abcde 1234f1z5 ';

        $this->assertSame('27ABCDE1234F1Z5', $user->gst_number);
        $this->assertSame('29ABCDE1234F1Z5', $address->gstin);
    }
}

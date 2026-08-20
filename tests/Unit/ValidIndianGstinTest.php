<?php

namespace Tests\Unit;

use App\Rules\ValidIndianGstin;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidIndianGstinTest extends TestCase
{
    public function test_it_accepts_a_gstin_matching_the_selected_billing_state(): void
    {
        $validator = Validator::make(
            ['gstin' => '27ABCDE1234F1Z5'],
            ['gstin' => ['nullable', new ValidIndianGstin('MH', 'Maharashtra')]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_it_rejects_a_gstin_from_a_different_billing_state(): void
    {
        $validator = Validator::make(
            ['gstin' => '27ABCDE1234F1Z5'],
            ['gstin' => ['nullable', new ValidIndianGstin('KA', 'Karnataka')]],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('billing address is in Karnataka', $validator->errors()->first('gstin'));
    }
}

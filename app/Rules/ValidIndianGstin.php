<?php

namespace App\Rules;

use App\Services\GstPlaceOfSupplyService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class ValidIndianGstin implements ValidationRule
{
    public function __construct(
        protected ?string $expectedStateCode = null,
        protected ?string $expectedStateName = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        try {
            app(GstPlaceOfSupplyService::class)->assertValidGstin(
                (string) $value,
                $this->expectedStateCode,
                $this->expectedStateName,
            );
        } catch (InvalidArgumentException $e) {
            $fail($e->getMessage());
        }
    }
}

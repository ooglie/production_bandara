<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\B2BApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class B2BApplicationApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly B2BApplication $application) {}
}

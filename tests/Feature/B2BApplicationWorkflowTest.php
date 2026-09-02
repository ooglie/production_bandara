<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\B2BApplicationStatus;
use App\Models\B2BApplication;
use App\Models\B2BCustomerProfile;
use App\Models\User;
use App\Services\B2B\B2BApplicationApprovalService;
use App\Services\B2B\B2BApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class B2BApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_submission_does_not_convert_a_pending_customer_to_b2b(): void
    {
        $customer = User::factory()->create(['customer_type' => config('b2b_application.customer_type.b2c', 'b2c')]);
        $application = $this->application($customer, B2BApplicationStatus::Draft);

        app(B2BApplicationWorkflowService::class)->submit($customer, $application);

        self::assertSame(B2BApplicationStatus::Submitted, $application->fresh()->status);
        self::assertSame((string) config('b2b_application.customer_type.b2c', 'b2c'), (string) $customer->fresh()->getRawOriginal('customer_type'));
    }

    public function test_saving_requested_information_keeps_the_application_in_that_review_state(): void
    {
        $customer = User::factory()->create(['customer_type' => config('b2b_application.customer_type.b2c', 'b2c')]);
        $application = $this->application($customer, B2BApplicationStatus::MoreInformationRequired);
        $data = $this->businessData();
        $data['legal_business_name'] = 'Updated Business Name LLP';

        app(B2BApplicationWorkflowService::class)->saveBusinessDetails($customer, $data);

        $fresh = $application->fresh();
        self::assertSame(B2BApplicationStatus::MoreInformationRequired, $fresh->status);
        self::assertSame('Updated Business Name LLP', $fresh->legal_business_name);
    }

    public function test_approval_is_the_only_step_that_converts_customer_and_creates_profile(): void
    {
        $customer = User::factory()->create(['customer_type' => config('b2b_application.customer_type.b2c', 'b2c')]);
        $actor = User::factory()->create();
        $application = $this->application($customer, B2BApplicationStatus::Submitted);

        app(B2BApplicationApprovalService::class)->approve($application, $actor, [
            'pay_later_enabled' => true,
            'credit_limit' => 50000,
            'payment_terms_days' => 15,
            'minimum_order_value' => 5000,
            'customer_message' => 'Approved for business purchasing.',
        ]);

        self::assertSame((string) config('b2b_application.customer_type.b2b', 'b2b'), (string) $customer->fresh()->getRawOriginal('customer_type'));
        self::assertDatabaseHas('b2b_applications', ['id' => $application->id, 'status' => 'approved']);
        self::assertDatabaseHas('b2b_customer_profiles', [
            'user_id' => $customer->id,
            'pay_later_enabled' => true,
            'payment_terms_days' => 15,
        ]);
        self::assertNotNull(B2BCustomerProfile::query()->where('user_id', $customer->id)->first());
    }

    private function application(User $customer, B2BApplicationStatus $status): B2BApplication
    {
        return B2BApplication::query()->create(array_merge($this->businessData(), [
            'user_id' => $customer->id,
            'status' => $status,
            'interested_categories' => ['meat', 'seafood'],
            'estimated_monthly_purchase' => '50000_100000',
            'purchase_frequency' => 'weekly',
            'requirements_message' => 'Weekly restaurant supply.',
            'terms_accepted_at' => now(),
            'submitted_at' => $status === B2BApplicationStatus::Draft ? null : now(),
            'customer_message' => $status === B2BApplicationStatus::MoreInformationRequired ? 'Please confirm quantities.' : null,
        ]));
    }

    private function businessData(): array
    {
        return [
            'contact_first_name' => 'Test',
            'contact_last_name' => 'Buyer',
            'email' => 'buyer@example.test',
            'phone' => '9876543210',
            'whatsapp' => '9876543210',
            'preferred_contact_method' => 'whatsapp',
            'legal_business_name' => 'Test Kitchen LLP',
            'trading_name' => 'Test Kitchen',
            'business_type' => 'restaurant',
            'gst_registered' => false,
            'gstin' => null,
            'pan' => null,
            'fssai_number' => null,
            'website' => null,
            'address_line_1' => '1 Test Road',
            'address_line_2' => null,
            'state_id' => 1,
            'city_id' => 1,
            'state_name' => 'Maharashtra',
            'city_name' => 'Mumbai',
            'postal_code' => '400001',
        ];
    }
}

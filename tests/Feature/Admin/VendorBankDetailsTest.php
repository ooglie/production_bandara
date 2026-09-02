<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorBankDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_view_and_update_encrypted_vendor_bank_details(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'name' => 'Bank Details Vendor',
            'code' => 'BANK-VENDOR',
            'bank_name' => '  HDFC Bank  ',
            'bank_ifsc_code' => ' hdfc0001234 ',
            'bank_account_number' => '0012 3456-7890',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.vendors.index'));
        $response->assertSessionHasNoErrors();

        $vendor = Vendor::query()->where('code', 'BANK-VENDOR')->firstOrFail();

        $this->assertSame('HDFC Bank', $vendor->bank_name);
        $this->assertSame('HDFC0001234', $vendor->bank_ifsc_code);
        $this->assertSame('001234567890', $vendor->bank_account_number);
        $this->assertSame('•••• •••• 7890', $vendor->maskedBankAccountNumber());

        $storedAccountNumber = (string) DB::table('vendors')
            ->where('id', $vendor->id)
            ->value('bank_account_number');

        $this->assertNotSame('001234567890', $storedAccountNumber);
        $this->assertStringNotContainsString('001234567890', $storedAccountNumber);

        $this->actingAs($admin)
            ->get(route('admin.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('HDFC Bank')
            ->assertSee('HDFC0001234')
            ->assertSee('•••• •••• 7890')
            ->assertDontSee('001234567890');

        $this->actingAs($admin)
            ->get(route('admin.vendors.edit', $vendor))
            ->assertOk()
            ->assertSee('001234567890');

        $update = $this->actingAs($admin)->put(route('admin.vendors.update', $vendor), [
            'name' => $vendor->name,
            'code' => $vendor->code,
            'bank_name' => 'ICICI Bank',
            'bank_ifsc_code' => 'icic0005678',
            'bank_account_number' => '987654321012',
            'is_active' => '1',
        ]);

        $update->assertRedirect(route('admin.vendors.index'));
        $update->assertSessionHasNoErrors();

        $vendor->refresh();

        $this->assertSame('ICICI Bank', $vendor->bank_name);
        $this->assertSame('ICIC0005678', $vendor->bank_ifsc_code);
        $this->assertSame('987654321012', $vendor->bank_account_number);
        $this->assertSame('•••• •••• 1012', $vendor->maskedBankAccountNumber());
    }

    public function test_vendor_bank_fields_are_optional_and_invalid_values_are_rejected(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'name' => 'Vendor Without Bank Details',
            'code' => 'NO-BANK',
            'is_active' => '1',
        ])->assertRedirect(route('admin.vendors.index'))
          ->assertSessionHasNoErrors();

        $vendor = Vendor::query()->where('code', 'NO-BANK')->firstOrFail();

        $this->assertNull($vendor->bank_name);
        $this->assertNull($vendor->bank_ifsc_code);
        $this->assertNull($vendor->bank_account_number);

        $invalid = $this->actingAs($admin)
            ->from(route('admin.vendors.edit', $vendor))
            ->put(route('admin.vendors.update', $vendor), [
                'name' => $vendor->name,
                'code' => $vendor->code,
                'bank_name' => 'Example Bank',
                'bank_ifsc_code' => 'INVALID',
                'bank_account_number' => 'ABC123',
                'is_active' => '1',
            ]);

        $invalid->assertRedirect(route('admin.vendors.edit', $vendor));
        $invalid->assertSessionHasErrors([
            'bank_ifsc_code',
            'bank_account_number',
        ]);
    }

    private function adminUser(): User
    {
        Role::findOrCreate('Admin', 'web');

        $admin = User::factory()->create([
            'customer_type' => 'staff',
            'is_active' => true,
        ]);

        $admin->assignRole('Admin');

        return $admin;
    }
}

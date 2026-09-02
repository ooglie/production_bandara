<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addBankName = ! Schema::hasColumn('vendors', 'bank_name');
        $addIfscCode = ! Schema::hasColumn('vendors', 'bank_ifsc_code');
        $addAccountNumber = ! Schema::hasColumn('vendors', 'bank_account_number');

        if (! $addBankName && ! $addIfscCode && ! $addAccountNumber) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table) use ($addBankName, $addIfscCode, $addAccountNumber): void {
            if ($addBankName) {
                $table->string('bank_name', 150)->nullable();
            }

            if ($addIfscCode) {
                $table->string('bank_ifsc_code', 11)->nullable();
            }

            if ($addAccountNumber) {
                // Laravel's encrypted cast produces variable-length ciphertext.
                $table->text('bank_account_number')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = collect([
            'bank_name',
            'bank_ifsc_code',
            'bank_account_number',
        ])->filter(fn (string $column): bool => Schema::hasColumn('vendors', $column))
          ->values()
          ->all();

        if ($columns === []) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};

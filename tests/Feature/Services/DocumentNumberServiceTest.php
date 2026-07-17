<?php

namespace Tests\Feature\Services;

use App\Services\DocumentNumberService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-24 10:15:00'));

        $this->rebuildTestSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('document_number_sequences');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_it_generates_matching_order_and_invoice_numbers(): void
    {
        $first = $this->service()->nextOrderInvoicePair();
        $second = $this->service()->nextOrderInvoicePair();

        $this->assertSame('BA-ORD-240626-101', $first['order_number']);
        $this->assertSame('BA-INV-240626-101', $first['invoice_number']);
        $this->assertSame(101, $first['sequence']);

        $this->assertSame('BA-ORD-240626-102', $second['order_number']);
        $this->assertSame('BA-INV-240626-102', $second['invoice_number']);
        $this->assertSame(102, $second['sequence']);
    }

    public function test_it_resets_the_sequence_each_month(): void
    {
        $june = $this->service()->nextOrderInvoicePair(Carbon::parse('2026-06-30 23:59:00'));
        $july = $this->service()->nextOrderInvoicePair(Carbon::parse('2026-07-01 00:01:00'));

        $this->assertSame('BA-ORD-300626-101', $june['order_number']);
        $this->assertSame('BA-INV-300626-101', $june['invoice_number']);

        $this->assertSame('BA-ORD-010726-101', $july['order_number']);
        $this->assertSame('BA-INV-010726-101', $july['invoice_number']);
    }

    public function test_it_skips_existing_numbers_when_sequence_row_is_missing(): void
    {
        DB::table('orders')->insert([
            'order_number' => 'BA-ORD-240626-101',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert([
            'invoice_number' => 'BA-INV-240626-101',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pair = $this->service()->nextOrderInvoicePair();

        $this->assertSame('BA-ORD-240626-102', $pair['order_number']);
        $this->assertSame('BA-INV-240626-102', $pair['invoice_number']);
        $this->assertSame(102, $pair['sequence']);
    }

    private function service(): DocumentNumberService
    {
        return app(DocumentNumberService::class);
    }

    private function rebuildTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('document_number_sequences');
        Schema::enableForeignKeyConstraints();

        Schema::create('document_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('sequence_key', 80);
            $table->string('period', 6);
            $table->unsignedInteger('last_number')->default(100);
            $table->timestamps();
            $table->unique(['sequence_key', 'period'], 'doc_num_seq_key_period_unique');
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->timestamps();
        });
    }
}

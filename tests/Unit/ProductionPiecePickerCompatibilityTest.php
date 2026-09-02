<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductionPiecePickerCompatibilityTest extends TestCase
{
    public function test_production_piece_picker_uses_actual_available_piece_records_and_legacy_modes(): void
    {
        $view = file_get_contents(resource_path('views/admin/production/create.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/ProductionRunController.php'));
        $reversalService = file_get_contents(app_path('Services/ProductionRunReversalService.php'));

        $this->assertIsString($view);
        $this->assertIsString($controller);
        $this->assertIsString($reversalService);

        // Native controls are present in the HTML response rather than created
        // dynamically after a browser-specific select event.
        $this->assertStringContainsString('data-piece-lot-group', $view);
        $this->assertStringContainsString('data-piece-selection-control="1"', $view);
        $this->assertStringContainsString('for="{{ $pieceInputId }}"', $view);
        $this->assertStringNotContainsString("document.createElement('label')", $view);

        // The UI must not depend on one exact inward_mode string. Restored and
        // legacy databases may still contain pieces_weight even though newer
        // vendor invoices normalize the lot to pieces.
        $this->assertStringContainsString("['pieces', 'pieces_weight']", $view);
        $this->assertStringContainsString("['pieces', 'pieces_weight']", $controller);
        $this->assertStringContainsString('requires_piece_selection', $view);
        $this->assertStringNotContainsString("lot.inward_mode === 'pieces'", $view);
        $this->assertStringNotContainsString("lot.inward_mode !== 'pieces'", $view);

        // Older single/whole piece lots that have a piece count and total
        // weight but no child inventory_pieces remain usable only as a whole.
        $this->assertStringContainsString('consume_entire_input_lot', $view);
        $this->assertStringContainsString('consume_entire_input_lot', $controller);
        $this->assertStringContainsString('data-selection-kind="whole-lot"', $view);

        // Only truly available, unsold and unconsumed piece rows may be shown
        // or posted by the production run.
        $this->assertStringContainsString("whereNull('sold_order_item_id')", $controller);
        $this->assertStringContainsString("whereNull('consumed_in_production_run_id')", $controller);
        $this->assertStringContainsString("COALESCE(available_weight_kg, weight_kg, 0) > 0", $controller);

        // Use broadly supported event checks rather than relying on a browser
        // realm-specific instanceof HTMLInputElement test.
        $this->assertStringContainsString("typeof target.matches === 'function'", $view);
        $this->assertStringNotContainsString('target instanceof HTMLInputElement', $view);
        $this->assertStringContainsString("inputLotEl.addEventListener('input', handleInputLotSelection)", $view);

        // Reversal remains possible for the whole-lot legacy fallback even
        // though that source lot intentionally has no InventoryPiece rows.
        $this->assertStringContainsString('trackedPieceRecordCount', $reversalService);
        $this->assertStringContainsString('if ($trackedPieceRecordCount > 0)', $reversalService);
    }
}

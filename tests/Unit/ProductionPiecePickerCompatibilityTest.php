<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductionPiecePickerCompatibilityTest extends TestCase
{
    public function test_production_piece_picker_uses_server_rendered_native_controls(): void
    {
        $view = file_get_contents(resource_path('views/admin/production/create.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('data-piece-lot-group', $view);
        $this->assertStringContainsString('data-piece-checkbox="1"', $view);
        $this->assertStringContainsString('for="{{ $pieceInputId }}"', $view);
        $this->assertStringContainsString("inputLotEl.addEventListener('input', handleInputLotSelection)", $view);
        $this->assertStringNotContainsString("document.createElement('label')", $view);
    }
}

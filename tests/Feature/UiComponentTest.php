<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentTest extends TestCase
{
    public function test_ui_components_render_accessible_states(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.card title="Settings" description="Manage the workflow.">
                <x-ui.form-field label="Workflow name" for="workflow" error="Required" required>
                    <x-ui.input id="workflow" invalid disabled />
                </x-ui.form-field>
                <x-ui.badge variant="success">Active</x-ui.badge>
            </x-ui.card>
        BLADE);

        $this->assertStringContainsString('Settings', $html);
        $this->assertStringContainsString('for="workflow"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('Active', $html);
    }
}

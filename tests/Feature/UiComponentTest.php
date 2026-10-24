<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentTest extends TestCase
{
    public function test_shared_pagination_renders_numbered_controls_and_page_jump(): void
    {
        $paginator = new LengthAwarePaginator(collect([1]), 420, 10, 1, ['pageName' => 'assetsPage']);

        $html = Blade::render('<x-offorest.pagination :paginator="$paginator" />', ['paginator' => $paginator]);

        $this->assertStringContainsString('aria-current="page" aria-label="Trang 1, hiện tại"', $html);
        $this->assertStringContainsString("gotoPage(2, 'assetsPage')", $html);
        $this->assertStringContainsString('Đi tới trang:', $html);
        $this->assertStringContainsString('max="42"', $html);
        $this->assertStringContainsString('aria-label="Trang trước"', $html);
        $this->assertStringContainsString('wire:loading.attr="disabled" wire:target="gotoPage"', $html);
        $this->assertStringContainsString('type="submit" wire:loading.attr="disabled" wire:target="gotoPage"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_shared_pagination_keeps_pages_around_the_current_page(): void
    {
        $paginator = new LengthAwarePaginator(collect([1]), 420, 10, 20, ['pageName' => 'assetsPage']);

        $html = Blade::render('<x-offorest.pagination :paginator="$paginator" />', ['paginator' => $paginator]);

        $this->assertStringContainsString('gotoPage(19,', $html);
        $this->assertStringContainsString('aria-current="page" aria-label="Trang 20, hiện tại"', $html);
        $this->assertStringContainsString('gotoPage(21,', $html);
    }

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

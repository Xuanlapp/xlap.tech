<?php

namespace Tests\Feature;

use App\Livewire\Pages\SuncatcherTwo\AddProductDesign;
use App\Models\User;
use App\Services\SuncatcherTwo\SuncatcherTwoService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SuncatcherTwoScriptDraftTest extends TestCase
{
    public function test_script_is_rendered_without_creating_an_item_and_survives_restore(): void
    {
        Http::preventStrayRequests();
        $user = new User;
        $user->id = 7;
        $workflow = ['script' => ['audience' => 'Parents buying a keepsake', 'main' => 'Product on white']];
        $service = Mockery::mock(SuncatcherTwoService::class);
        $service->shouldReceive('createAsset')->never();
        $service->shouldReceive('generateWorkflowScriptDraft')->once()
            ->withArgs(fn ($owner, $keyword, $image, $listing, $provider, $model) => $owner->id === 7 && $keyword === 'Baby ornament' && $provider === 'cheapkeyai')
            ->andReturn(['workflow' => $workflow, 'script' => $workflow['script']]);
        $this->app->instance(SuncatcherTwoService::class, $service);

        Livewire::actingAs($user)->test(AddProductDesign::class, ['providerKey' => 'cheapkeyai'])
            ->set('keyword', 'Baby ornament')
            ->set('imageLink', 'https://example.com/image.png')
            ->call('saveAndGenerateData')
            ->assertHasNoErrors()
            ->assertSee('Generated Script')
            ->assertSee('Parents buying a keepsake')
            ->assertNotDispatched('product-design-created');

        Livewire::actingAs($user)->test(AddProductDesign::class)
            ->call('restoreDraft', ['draftWorkflow' => $workflow])
            ->assertSee('Parents buying a keepsake')
            ->assertSee('Product on white');
    }

    public function test_person_uploads_are_independent_and_replacing_one_removes_only_its_old_file(): void
    {
        Storage::fake('public');
        $user = new User;
        $user->id = 7;

        $component = Livewire::actingAs($user)->test(AddProductDesign::class)
            ->set('personAUpload', UploadedFile::fake()->image('a-first.png'));
        $firstA = $component->get('personUrls.a');
        $component->set('personBUpload', UploadedFile::fake()->image('b.png'));
        $urlB = $component->get('personUrls.b');
        $component->set('personAUpload', UploadedFile::fake()->image('a-second.png'));
        $secondA = $component->get('personUrls.a');

        $this->assertNotSame($firstA, $urlB);
        $this->assertNotSame($firstA, $secondA);
        $this->assertSame($urlB, $component->get('personUrls.b'));
        Storage::disk('public')->assertMissing(str_replace('/storage/', '', $firstA));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $secondA));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $urlB));
    }
    public function test_empty_draft_renders_without_a_parse_error(): void
    {
        Livewire::test(AddProductDesign::class)->assertStatus(200)->assertDontSee('Generated Script');
    }
}

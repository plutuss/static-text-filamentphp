<?php

namespace Plutuss\StaticTextFilament\Tests;

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Orchestra\Testbench\TestCase;
use Plutuss\Models\Page;
use Plutuss\Models\PageItem;
use Plutuss\Providers\StaticTextServiceProvider;
use Plutuss\StaticTextFilament\Resources\BlockData;
use Plutuss\StaticTextFilament\Resources\BlockResource;
use Plutuss\StaticTextFilament\Resources\PageResource;
use Plutuss\StaticTextFilament\Resources\Pages\ManageBlocks;
use Plutuss\StaticTextFilament\Resources\Pages\ManagePages;
use Plutuss\StaticTextFilament\StaticTextFilamentServiceProvider;
use ReflectionClass;

final class StaticTextPluginTest extends TestCase
{
    protected $enablesPackageDiscoveries = true;

    protected function getPackageProviders($app): array
    {
        return [
            StaticTextServiceProvider::class,
            StaticTextFilamentServiceProvider::class,
            TestPanelProvider::class,
            OtherPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $source = dirname((new ReflectionClass(Page::class))->getFileName(), 2);
        $this->artisan('migrate', [
            '--realpath' => true,
            '--path' => [$source . '/database/migrations'],
        ])->assertSuccessful();

        $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Editor']));
        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
    }

    public function test_panel_lists_pages_and_blocks_created_through_the_original_models(): void
    {
        $home = Page::add('Home', '/', 'home');
        $about = Page::add('About', '/about', 'about');
        $header = PageItem::add('header', $home->id, []);
        $footer = PageItem::add('footer', $about->id, []);

        $this->assertContains(PageResource::class, Filament::getCurrentPanel()->getResources());
        $this->assertContains(BlockResource::class, Filament::getCurrentPanel()->getResources());
        $this->assertSame('/admin/static-text/pages', PageResource::getUrl(isAbsolute: false));
        $this->assertSame('/admin/static-text/blocks', BlockResource::getUrl(isAbsolute: false));
        $this->assertNotContains(PageResource::class, Filament::getPanel('public')->getResources());
        $this->assertNotContains(BlockResource::class, Filament::getPanel('public')->getResources());

        Livewire::test(ManagePages::class)->assertCanSeeTableRecords([$home, $about]);
        Livewire::test(ManageBlocks::class)
            ->assertCanSeeTableRecords([$header, $footer])
            ->filterTable('page', $home->id)
            ->assertCanSeeTableRecords([$header])
            ->assertCanNotSeeTableRecords([$footer]);
    }

    public function test_editor_can_create_and_edit_a_page(): void
    {
        Livewire::test(ManagePages::class)
            ->callAction('create', data: ['name' => 'Home', 'slug' => '/', 'template' => 'home'])
            ->assertHasNoFormErrors();

        $page = Page::query()->sole();

        Livewire::test(ManagePages::class)
            ->callAction(TestAction::make('edit')->table($page), data: [
                'name' => 'Homepage',
                'slug' => '/',
                'template' => 'home',
                'seo_title' => 'Homepage SEO',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('Homepage', $page->refresh()->name);
        $this->assertSame('Homepage SEO', $page->seo_title);
    }

    public function test_editor_can_create_a_block_but_cannot_duplicate_its_name_on_the_same_page(): void
    {
        $home = Page::add('Home', '/', 'home');
        $about = Page::add('About', '/about', 'about');
        PageItem::add('header', $home->id, []);
        $payload = [
            'page_id' => $about->id,
            'name' => 'header',
            'data' => [['key' => 'title_de', 'type' => 'text', 'value' => 'Willkommen']],
        ];

        $restoreRepeater = Repeater::fake();
        try {
            Livewire::test(ManageBlocks::class)
                ->callAction('create', data: $payload)
                ->assertHasNoFormErrors();

            Livewire::test(ManageBlocks::class)
                ->callAction('create', data: $payload)
                ->assertHasFormErrors(['name' => ['unique']]);
        } finally {
            $restoreRepeater();
        }

        $this->assertDatabaseCount('page_items', 2);
        $this->assertSame(
            $payload['data'],
            PageItem::query()->where('page_id', $about->id)->sole()->data->all(),
        );
    }

    public function test_editor_can_view_and_update_a_saved_block_with_metadata(): void
    {
        $home = Page::add('Home', '/', 'home');
        $row = ['id' => 42, 'key' => 'title', 'value' => 'Old', 'meta' => ['source' => 'seeder']];
        $block = PageItem::add('header', $home->id, [$row]);

        Livewire::test(ManageBlocks::class)
            ->mountAction(TestAction::make('view')->table($block))
            ->assertSchemaStateSet([
                'name' => 'header',
                'stored_data' => json_encode([$row], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

        $restoreRepeater = Repeater::fake();
        try {
            Livewire::test(ManageBlocks::class)
                ->callAction(TestAction::make('edit')->table($block), data: [
                    'page_id' => $home->id,
                    'name' => 'header',
                    'data' => [['_original_index' => 0, 'key' => 'title', 'type' => null, 'value' => 'New']],
                ])
                ->assertHasNoFormErrors();
        } finally {
            $restoreRepeater();
        }

        $this->assertSame([array_replace($row, ['value' => 'New'])], $block->refresh()->data->all());
    }

    public function test_reordering_preserves_metadata_and_original_value_types(): void
    {
        $first = ['id' => 5, 'key' => 'count', 'type' => 'number', 'value' => 0];
        $second = ['id' => 12, 'key' => 'settings', 'value' => ['enabled' => true]];
        $block = new PageItem(['data' => [$first, $second]]);

        $form = BlockData::prepareForEditing([], $block);
        $form['data'] = array_reverse($form['data']);
        $form['data'][] = ['key' => 'new', 'type' => 'text', 'value' => 'New'];

        $this->assertSame(
            [$second, $first, ['key' => 'new', 'type' => 'text', 'value' => 'New']],
            BlockData::mergeEditedFields($form, $block)['data'],
        );
    }
}

final class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin');
    }
}

final class OtherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->id('public')->path('public');
    }
}

# Static Text for Filament

Manage the pages and content blocks from `plutuss/static-text-laravel` in a Filament panel. The plugin uses the original Eloquent models and tables. Existing records appear as soon as you enable the plugin.

## Requirements

- PHP 8.2 or later within the 8.x series
- Filament 4 or 5 with a configured panel
- Static Text Laravel 2.x on Laravel 11 or 12

## Install from a local checkout

In the Laravel application that hosts the Filament panel, register this checkout as a Composer path repository and require the plugin.

```sh
composer config repositories.static-text-filament path /Users/developer/Projects/static-text-filamentphp
composer require 'plutuss/static-text-filamentphp:*@dev'
```

If Composer cannot resolve Static Text Laravel 2.x from Packagist, register the upstream Git repository and rerun the require command.

```sh
composer config repositories.static-text-core vcs https://github.com/plutuss/static-text.git
```

For a fresh Static Text Laravel installation, publish and run the base package's migrations.

```sh
php artisan vendor:publish --provider='Plutuss\Providers\StaticTextServiceProvider'
php artisan migrate
```

If you are upgrading an existing Static Text Laravel 1.x database, publish only the new index migration, then run `php artisan migrate`.

```sh
php artisan vendor:publish --tag=static-text-page-item-index
php artisan migrate
```

## Open the admin panel

Laravel discovers the plugin's service provider. The **Static Text** group appears automatically in the Filament panel with ID `admin`. Open **Pages** or **Blocks** to work with existing records.

If your admin panel uses another ID, configure it in the host application's `.env`:

```dotenv
STATIC_TEXT_FILAMENT_PANEL=backoffice
```

Use a non-tenant admin panel. For role-based access, register Laravel policies for `Plutuss\Models\Page` and `Plutuss\Models\PageItem` with the appropriate `viewAny`, `view`, `create`, and `update` permissions. The package manages shared site content.

If you prefer to register the plugin in a panel provider, set `STATIC_TEXT_FILAMENT_PANEL=` to disable automatic registration and add `->plugin(\Plutuss\StaticTextFilament\StaticTextPlugin::make())` to the panel configuration.

If the application uses cached configuration or Filament component discovery, refresh those caches after installing the package or changing its panel.

## Manage content

- **Static Text → Pages** lists pages and block counts. Open a page to view or edit its name, slug, template and SEO fields. Use **Blocks** to open the block list filtered to that page.
- **Static Text → Blocks** lists content blocks and their owning pages. Create a block by choosing a page, naming the block, and adding fields. Each field has a key, a value and an optional type.
- Use **View** to read the block fields. Expand **Stored JSON** to inspect the full payload, including metadata such as field IDs. Use **Edit** to change or reorder fields. Existing metadata stays attached to retained fields.
- Localization uses suffixes such as `title_de`. Use a path relative to the disk configured by Static Text Laravel for `image` and `file` fields. The form edits the stored path.
- Block names are unique within a page. Each page keeps its own blocks even when names such as `header` are reused.

## Tests

From this package's directory, run the Filament and Livewire integration tests with PHP's PDO SQLite extension enabled.

```sh
composer install
composer test
```

The tests boot a Filament panel with Laravel Testbench and run the original Static Text migrations against an in-memory database.

## License

[MIT](LICENSE.md)

# Static Text for Filament

Manage pages, SEO metadata, and localized content blocks in a Filament panel with `plutuss/static-text-filamentphp`. The plugin reads and writes the existing Eloquent models and tables from Static Text Laravel, so you can edit content your application already renders.

## Requirements

- PHP 8.2 or later within the 8.x series
- Filament 4 or 5 with a configured panel
- Static Text Laravel 2.x on Laravel 11 or 12

## Installation

Run Composer in the Laravel application that hosts your Filament panel. If a tagged release is available in your Composer repositories, install it with:

```sh
composer require plutuss/static-text-filamentphp
```

For development with a local checkout, register it as a path repository. Replace the example path with the path from your application to the checkout.

```sh
composer config repositories.static-text-filament path ../static-text-filamentphp
composer require 'plutuss/static-text-filamentphp:*@dev'
```

If Composer cannot resolve Static Text Laravel 2.x, register its upstream Git repository in the host application and rerun the installation command.

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

## Panel configuration

Laravel discovers the plugin's service provider. The **Static Text** group appears automatically in the Filament panel with ID `admin`. Open **Pages** or **Blocks** to work with existing records.

If your admin panel uses another ID, configure it in the host application's `.env`:

```dotenv
STATIC_TEXT_FILAMENT_PANEL=backoffice
```

Use a non-tenant admin panel. For role-based access, register Laravel policies for `Plutuss\Models\Page` and `Plutuss\Models\PageItem` with the appropriate `viewAny`, `view`, `create`, and `update` permissions. The package manages shared site content.

To register the plugin yourself, set `STATIC_TEXT_FILAMENT_PANEL=` to disable automatic registration. Add the plugin call to the existing chain in your panel provider's `panel()` method.

```php
return $panel
    ->id('backoffice')
    ->plugin(\Plutuss\StaticTextFilament\StaticTextPlugin::make());
```

To override the package configuration in PHP, publish it to `config/static-text-filament.php`.

```sh
php artisan vendor:publish --tag=static-text-filament-config
```

If the application uses cached configuration or Filament component discovery, refresh those caches after installing the package or changing its panel.

## Manage content

- **Static Text → Pages** lets you create, view, and edit pages. Each page has a name, a unique slug, a unique template, and optional SEO fields. The list shows block counts. Use **Blocks** to open the block list filtered to that page.
- **Static Text → Blocks** lists content blocks and their owning pages. Create a block by choosing a page, naming the block, and adding fields. Each field has a key, a value and an optional type.
- Use **View** to read the block fields. Expand **Stored JSON** to inspect the full payload, including metadata such as field IDs. Use **Edit** to change or reorder fields. Existing metadata stays attached to retained fields.
- Use keys such as `title_en` and `title_de` for translations. For `image` and `file` fields, enter a path relative to the disk configured by Static Text Laravel.
- Block names are unique within a page. Each page keeps its own blocks even when names such as `header` are reused.

### Render a block in Blade

Create a block named `header` and add `title_en` and `title_de` fields. Pass the corresponding `Plutuss\Models\Page` instance to your Blade view as `$page`, then use the base field key. Static Text Laravel resolves the field for the application's active locale.

```blade
<h1>{{ $page->show('header:title') }}</h1>
```

See the upstream package for the rendering API and storage configuration. [Static Text Laravel](https://github.com/plutuss/static-text)

## Tests

From this package's directory, run the Filament and Livewire integration tests with PHP's PDO SQLite extension enabled.

```sh
composer install
composer test
```

The tests boot a Filament panel with Laravel Testbench and run the original Static Text migrations against an in-memory database.

## License

[MIT](LICENSE.md)

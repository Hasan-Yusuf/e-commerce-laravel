<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The two files the layout loads — `my-app.css` and `app.js` — are one entry
 * each: a layer or a behaviour that is not imported is a part that never loads,
 * which is the only way the split can fail without a test noticing.
 */
class AssetEntryTest extends TestCase
{
    public function test_every_layer_reaches_the_storefront_stylesheet(): void
    {
        $entry = file_get_contents(resource_path('css/my-app.css'));

        foreach (['reset.css', 'base.css', 'utilities.css'] as $layer) {
            static::assertStringContainsString(
                '@import "./'.$layer.'";',
                $entry,
                "The stylesheet entry never imports {$layer}.",
            );
        }
    }

    public function test_every_token_and_component_reaches_the_storefront_stylesheet(): void
    {
        $entry = file_get_contents(resource_path('css/my-app.css'));

        foreach (['tokens', 'components'] as $folder) {
            $parts = File::files(resource_path("css/{$folder}"));

            static::assertNotEmpty($parts, "No stylesheet in css/{$folder}.");

            foreach ($parts as $part) {
                static::assertStringContainsString(
                    '@import "./'.$folder.'/'.$part->getFilename().'";',
                    $entry,
                    "The stylesheet entry never imports {$folder}/{$part->getFilename()}.",
                );
            }
        }
    }

    public function test_the_storefront_stylesheet_imports_only_what_is_there(): void
    {
        $entry = file_get_contents(resource_path('css/my-app.css'));

        preg_match_all('/@import "([^"]+)"/', $entry, $matches);

        static::assertNotEmpty($matches[1]);

        foreach ($matches[1] as $import) {
            static::assertFileExists(
                resource_path('css/'.str_replace('./', '', $import)),
                "The stylesheet entry imports {$import}, which is not there.",
            );
        }
    }

    public function test_every_behaviour_reaches_the_storefront_script(): void
    {
        $entry = file_get_contents(resource_path('js/app.js'));

        $modules = File::files(resource_path('js/components'));

        static::assertNotEmpty($modules);

        foreach ($modules as $module) {
            static::assertStringContainsString(
                './components/'.$module->getFilenameWithoutExtension(),
                $entry,
                "The script entry never imports {$module->getFilename()}.",
            );
        }
    }
}

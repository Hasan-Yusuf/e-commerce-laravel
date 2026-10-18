<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TranslationsTest extends TestCase
{
    public function test_filament_translation_files_are_in_sync(): void
    {
        $langPath = lang_path();

        $enPath = "{$langPath}/en/filament";
        $arPath = "{$langPath}/ar/filament";

        static::assertDirectoryExists($enPath);
        static::assertDirectoryExists($arPath);

        $enFiles = File::files($enPath);

        foreach ($enFiles as $file) {
            $enFileName = $file->getFilename();
            $enData = Arr::dot(include $file->getPathname());

            $arFilePath = "{$arPath}/{$enFileName}";

            static::assertFileExists(
                $arFilePath,
                "Arabic file {$arFilePath} is missing",
            );

            $arData = Arr::dot(include $arFilePath);

            foreach (array_keys($enData) as $key) {
                static::assertArrayHasKey(
                    $key,
                    $arData,
                    "Arabic key {$key} is missing in {$arFilePath}",
                );
            }

            foreach (array_keys($arData) as $key) {
                static::assertArrayHasKey(
                    $key,
                    $enData,
                    "English key {$key} is missing in {$enFileName}",
                );
            }
        }

        $arFiles = File::files($arPath);

        foreach ($arFiles as $file) {
            $arFileName = $file->getFilename();
            $arData = Arr::dot(include $file->getPathname());

            $enFilePath = "{$arPath}/{$arFileName}";

            static::assertFileExists(
                $enFilePath,
                "Arabic file {$enFilePath} is missing",
            );
        }
    }

    public function test_catalog_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/catalog.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/catalog.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "catalog" are out of sync between English and Arabic.',
        );
    }

    public function test_cart_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/carts.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/carts.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "carts" are out of sync between English and Arabic.',
        );
    }

    public function test_wishlist_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/wishlist.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/wishlist.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "wishlist" are out of sync between English and Arabic.',
        );
    }

    public function test_auth_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/auth.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/auth.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "auth" are out of sync between English and Arabic.',
        );
    }

    public function test_component_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/components.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/components.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "components" are out of sync between English and Arabic.',
        );
    }

    public function test_checkout_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/checkout.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/checkout.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "checkout" are out of sync between English and Arabic.',
        );
    }

    public function test_account_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/account.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/account.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "account" are out of sync between English and Arabic.',
        );
    }

    public function test_orders_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/orders.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/orders.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "orders" are out of sync between English and Arabic.',
        );
    }

    public function test_contact_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/contact.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/contact.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "contact" are out of sync between English and Arabic.',
        );
    }

    public function test_validation_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/validation.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/validation.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "validation" are out of sync between English and Arabic.',
        );
    }

    public function test_password_translation_files_are_in_sync(): void
    {
        $englishKeys = Arr::dot(include lang_path('en/passwords.php'));
        $arabicKeys = Arr::dot(include lang_path('ar/passwords.php'));

        static::assertEqualsCanonicalizing(
            array_keys($englishKeys),
            array_keys($arabicKeys),
            'Translation keys for "passwords" are out of sync between English and Arabic.',
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TypeSpec;
use Tests\TestCase;

/**
 * The columns a locale reads and writes under come from the locales the
 * application declares, so the catalogue is bilingual because of one config
 * entry rather than because two codes are written into the models.
 */
class SpecLocaleColumnsTest extends TestCase
{
    public function test_a_declared_locale_names_its_own_column(): void
    {
        static::assertSame('en', TypeSpec::localeColumn('en'));
        static::assertSame('ar', TypeSpec::localeColumn('ar'));
    }

    public function test_a_locale_the_application_does_not_declare_reads_as_the_fallback(): void
    {
        config(['locales' => ['en' => ['label' => 'English', 'dir' => 'ltr']]]);

        // "fr" has no column of its own, so it reads the fallback locale's.
        static::assertSame(app()->getFallbackLocale(), TypeSpec::localeColumn('fr'));
    }

    public function test_the_option_labels_follow_the_declared_locales(): void
    {
        $spec = new TypeSpec([
            'options' => [
                ['label_en' => 'Black', 'label_ar' => 'أسود', 'label_fr' => 'Noir'],
            ],
        ]);

        static::assertSame(['Black'], $spec->optionLabels('en'));
        static::assertSame(['أسود'], $spec->optionLabels('ar'));

        // A third locale needs no code of its own: its labels are stored under
        // its own key and read back the same way.
        config([
            'locales' => [
                'en' => ['label' => 'English', 'dir' => 'ltr'],
                'fr' => ['label' => 'Français', 'dir' => 'ltr'],
            ],
        ]);

        static::assertSame(['Noir'], $spec->optionLabels('fr'));
    }
}

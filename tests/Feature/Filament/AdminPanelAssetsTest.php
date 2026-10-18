<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Scripts the admin panel loads on every page of it.
 */
class AdminPanelAssetsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Pasting a clipboard image is scoped to one upload by a panel-wide script,
     * so it has to be loaded wherever an upload might be.
     */
    public function test_the_panel_loads_the_scoped_file_paste_script(): void
    {
        $this
            ->get(route('filament.admin.auth.login'))
            ->assertOk()
            ->assertSee('js/app/scoped-file-paste.js', false);
    }
}

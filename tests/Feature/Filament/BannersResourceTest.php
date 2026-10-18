<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Models\Banner;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Banners resource.
 *
 * The banner image is a Spatie Media Library upload, which cannot be exercised
 * headlessly, so uploads are not covered. The banners table only exposes an
 * edit record action (plus a bulk delete), so deletion is covered through the
 * delete header action on the edit page.
 */
class BannersResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_banners(): void
    {
        $this->actingAsAdmin();

        $banners = Banner::factory()->count(3)->create();

        Livewire::test(ListBanners::class)
            ->assertOk()
            ->assertCanSeeTableRecords($banners);
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_banner(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateBanner::class)
            ->fillForm([
                'title_en' => null,
                'link' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title_en' => 'required',
                'link' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('banners', 0);
    }

    public function test_it_creates_a_banner(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateBanner::class)
            ->fillForm([
                'title_en' => 'Summer sale',
                'title_ar' => 'تخفيضات الصيف',
                'link' => 'https://example.com/summer',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('banners', [
            'title_en' => 'Summer sale',
            'title_ar' => 'تخفيضات الصيف',
            'link' => 'https://example.com/summer',
        ]);
    }

    public function test_it_writes_the_link_from_the_banner_name(): void
    {
        $this->actingAsAdmin();

        // Naming the offer is enough: the link is the shop's search for it.
        Livewire::test(CreateBanner::class)
            ->fillForm(['title_en' => 'Summer sale'])
            ->set('data.title_en', 'Ramadan Offers')
            ->assertSchemaStateSet([
                'link' => route('products.index', ['q' => 'ramadan-offers']),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('banners', [
            'title_en' => 'Ramadan Offers',
            'link' => route('products.index', ['q' => 'ramadan-offers']),
        ]);
    }

    public function test_it_edits_a_banner(): void
    {
        $this->actingAsAdmin();

        $banner = Banner::factory()->create(['title_en' => 'Old title']);

        Livewire::test(EditBanner::class, [
            'record' => $banner->getRouteKey(),
        ])
            ->fillForm([
                'title_en' => 'New title',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title_en' => 'New title',
        ]);
    }

    public function test_it_deletes_a_banner(): void
    {
        $this->actingAsAdmin();

        $banner = Banner::factory()->create();

        Livewire::test(EditBanner::class, [
            'record' => $banner->getRouteKey(),
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('banners', [
            'id' => $banner->id,
        ]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}

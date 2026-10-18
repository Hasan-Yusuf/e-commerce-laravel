<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\RelationManagers\ProductsRelationManager;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Brands resource.
 *
 * ProductsRelationManager is not registered on the resource
 * (app/Filament/Resources/Brands/BrandResource.php:66-71), so it is mounted
 * directly with Livewire to cover its rendering.
 */
class BrandsResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_brands(): void
    {
        $this->actingAsAdmin();

        $brands = Brand::factory()->count(3)->create();

        Livewire::test(ListBrands::class)
            ->call('loadTable')
            ->assertOk()
            ->assertCanSeeTableRecords($brands);
    }

    public function test_it_searches_brands_by_their_localized_name(): void
    {
        $this->actingAsAdmin();

        $acme = Brand::factory()->create([
            'name_en' => 'Acme',
            'name_ar' => 'أكمي',
        ]);

        $globex = Brand::factory()->create([
            'name_en' => 'Globex',
            'name_ar' => 'غلوبكس',
        ]);

        Livewire::test(ListBrands::class)
            ->call('loadTable')
            ->searchTable('Acme')
            ->assertOk()
            ->assertCanSeeTableRecords([$acme])
            ->assertCanNotSeeTableRecords([$globex])
            ->assertCountTableRecords(1);

        Livewire::test(ListBrands::class)
            ->call('loadTable')
            ->searchTable('أكمي')
            ->assertOk()
            ->assertCanSeeTableRecords([$acme])
            ->assertCanNotSeeTableRecords([$globex])
            ->assertCountTableRecords(1);
    }

    public function test_it_sorts_brands_by_their_english_name(): void
    {
        $this->actingAsAdmin();

        Brand::factory()->create(['name_en' => 'Globex']);
        Brand::factory()->create(['name_en' => 'Acme']);

        Livewire::test(ListBrands::class)
            ->call('loadTable')
            ->sortTable('name')
            ->assertOk();
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_brand(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateBrand::class)
            ->fillForm([
                'name_en' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name_en' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('brands', 0);
    }

    public function test_it_creates_a_brand(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        Livewire::test(CreateBrand::class)
            ->fillForm([
                'name_en' => 'Acme',
                'name_ar' => 'Acme AR',
                'logo_light' => UploadedFile::fake()->image('light.webp'),
                'logo_dark' => UploadedFile::fake()->image('dark.webp'),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('brands', [
            'name_en' => 'Acme',
        ]);
    }

    public function test_it_edits_a_brand(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $brand = Brand::factory()->create(['name_en' => 'Old name']);

        Livewire::test(EditBrand::class, [
            'record' => $brand->getRouteKey(),
        ])
            ->fillForm([
                'name_en' => 'New name',
                'logo_light' => UploadedFile::fake()->image('light.webp'),
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name_en' => 'New name',
        ]);
    }

    public function test_it_deletes_a_brand(): void
    {
        $this->actingAsAdmin();

        $brand = Brand::factory()->create();

        Livewire::test(ListBrands::class)
            ->call('loadTable')
            ->callAction(TestAction::make('delete')->table($brand))
            ->assertNotified();

        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_it_renders_the_products_relation_manager(): void
    {
        $this->actingAsAdmin();

        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        Livewire::test(ProductsRelationManager::class, [
            'ownerRecord' => $brand,
            'pageClass' => EditBrand::class,
        ])
            ->call('loadTable')
            ->assertOk()
            ->assertCanSeeTableRecords([$product]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}

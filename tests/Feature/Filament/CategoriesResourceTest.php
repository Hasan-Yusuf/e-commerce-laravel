<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\RelationManagers\TypesRelationManager;
use App\Models\Category;
use App\Models\Type;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Categories resource.
 *
 * TypesRelationManager is not registered on the resource
 * (app/Filament/Resources/Categories/CategoryResource.php:72-77), so it is
 * mounted directly with Livewire to cover its rendering.
 */
class CategoriesResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_categories(): void
    {
        $this->actingAsAdmin();

        $categories = Category::factory()->count(3)->create();

        Livewire::test(ListCategories::class)
            ->assertOk()
            ->assertCanSeeTableRecords($categories);
    }

    public function test_it_searches_categories_by_their_localized_name(): void
    {
        $this->actingAsAdmin();

        $cameras = Category::factory()->create([
            'name_en' => 'Cameras',
            'name_ar' => 'كاميرات',
            'parent_id' => null,
        ]);

        $furniture = Category::factory()->create([
            'name_en' => 'Furniture',
            'name_ar' => 'أثاث',
            'parent_id' => null,
        ]);

        Livewire::test(ListCategories::class)
            ->searchTable('Cameras')
            ->assertOk()
            ->assertCanSeeTableRecords([$cameras])
            ->assertCanNotSeeTableRecords([$furniture])
            ->assertCountTableRecords(1);

        Livewire::test(ListCategories::class)
            ->searchTable('كاميرات')
            ->assertOk()
            ->assertCanSeeTableRecords([$cameras])
            ->assertCanNotSeeTableRecords([$furniture])
            ->assertCountTableRecords(1);
    }

    public function test_it_sorts_categories_by_their_english_name(): void
    {
        $this->actingAsAdmin();

        Category::factory()->create([
            'name_en' => 'Furniture',
            'parent_id' => null,
        ]);

        Category::factory()->create([
            'name_en' => 'Cameras',
            'parent_id' => null,
        ]);

        Livewire::test(ListCategories::class)->sortTable('name')->assertOk();
    }

    public function test_it_groups_categories_by_their_parent(): void
    {
        $this->actingAsAdmin();

        $parent = Category::factory()->create([
            'name_en' => 'Electronics',
            'name_ar' => 'إلكترونيات',
            'parent_id' => null,
        ]);

        Category::factory()->create([
            'name_en' => 'Cameras',
            'parent_id' => $parent->id,
        ]);

        Livewire::test(ListCategories::class)
            ->set('tableGrouping', 'parent.name_en:asc')
            ->assertOk()
            ->assertSee('fi-ta-group-heading', false)
            ->assertSee('Electronics');
    }

    public function test_it_filters_categories_by_their_parent(): void
    {
        $this->actingAsAdmin();

        $parent = Category::factory()->create([
            'name_en' => 'Electronics',
            'parent_id' => null,
        ]);

        $child = Category::factory()->create([
            'name_en' => 'Cameras',
            'parent_id' => $parent->id,
        ]);

        $unrelated = Category::factory()->create([
            'name_en' => 'Furniture',
            'parent_id' => null,
        ]);

        Livewire::test(ListCategories::class)
            ->filterTable('parent_id', $parent->id)
            ->assertOk()
            ->assertCanSeeTableRecords([$child])
            ->assertCanNotSeeTableRecords([$unrelated]);
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_category(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name_en' => null,
                'name_ar' => null,
                'slug' => null,
                'description_en' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name_en' => 'required',
                'name_ar' => 'required',
                'description_en' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_it_creates_a_category(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name_en' => 'Cameras',
                'name_ar' => 'Cameras AR',
                'slug' => 'cameras',
                'description_en' => 'All the cameras',
                'Image' => UploadedFile::fake()->image('thumbnail.webp'),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('categories', [
            'slug' => 'cameras',
            'name_en' => 'Cameras',
        ]);
    }

    public function test_it_edits_a_category(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $category = Category::factory()->create();

        Livewire::test(EditCategory::class, [
            'record' => $category->getRouteKey(),
        ])
            ->fillForm([
                'name_ar' => 'Updated AR',
                'Image' => UploadedFile::fake()->image('thumbnail.webp'),
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name_ar' => 'Updated AR',
        ]);
    }

    public function test_it_deletes_a_category(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(ListCategories::class)
            ->callAction(TestAction::make('delete')->table($category))
            ->assertNotified();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_it_renders_the_types_relation_manager(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();
        $type = Type::factory()->create(['category_id' => $category->id]);

        Livewire::test(TypesRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->call('loadTable')
            ->assertOk()
            ->assertCanSeeTableRecords([$type]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}

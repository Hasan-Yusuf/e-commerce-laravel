<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Models\Country;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Countries resource.
 */
class CountriesResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_countries(): void
    {
        $this->actingAsAdmin();

        $countries = Country::factory()->count(3)->create();

        Livewire::test(ListCountries::class)
            ->assertOk()
            ->assertCanSeeTableRecords($countries);
    }

    public function test_it_creates_a_country(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCountry::class)
            ->fillForm([
                'name_en' => 'Qatar',
                'name_ar' => 'قطر',
                'calling_code' => '974',
                'phone_length' => 8,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('countries', [
            'name_en' => 'Qatar',
            'slug' => 'qatar',
        ]);
    }

    public function test_it_searches_countries_by_their_localized_name(): void
    {
        $this->actingAsAdmin();

        $qatar = Country::factory()->create([
            'name_en' => 'Qatar',
            'name_ar' => 'قطر',
        ]);

        $bahrain = Country::factory()->bahrain()->create();

        Livewire::test(ListCountries::class)
            ->searchTable('Qatar')
            ->assertOk()
            ->assertCanSeeTableRecords([$qatar])
            ->assertCanNotSeeTableRecords([$bahrain])
            ->assertCountTableRecords(1);

        Livewire::test(ListCountries::class)
            ->searchTable('قطر')
            ->assertOk()
            ->assertCanSeeTableRecords([$qatar])
            ->assertCanNotSeeTableRecords([$bahrain])
            ->assertCountTableRecords(1);
    }

    public function test_it_sorts_countries_by_their_english_name(): void
    {
        $this->actingAsAdmin();

        Country::factory()->create([
            'name_en' => 'Qatar',
            'name_ar' => 'قطر',
        ]);

        Country::factory()->bahrain()->create();

        Livewire::test(ListCountries::class)->sortTable('name')->assertOk();
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_country(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCountry::class)
            ->fillForm([
                'name_en' => null,
                'name_ar' => null,
                'calling_code' => null,
                'phone_length' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name_en' => 'required',
                'name_ar' => 'required',
                'calling_code' => 'required',
                'phone_length' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('countries', 0);
    }

    public function test_it_edits_a_country(): void
    {
        $this->actingAsAdmin();

        $country = Country::factory()->bahrain()->create();

        Livewire::test(EditCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->fillForm([
                'calling_code' => '9730',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'calling_code' => '9730',
        ]);
    }

    public function test_it_deletes_a_country(): void
    {
        $this->actingAsAdmin();

        $country = Country::factory()->create();

        Livewire::test(ListCountries::class)
            ->callAction(TestAction::make('delete')->table($country))
            ->assertNotified();

        $this->assertDatabaseMissing('countries', [
            'id' => $country->id,
        ]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Types\Pages\ListTypes;
use App\Filament\Resources\Types\TypeResource;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every custom admin action explains itself on hover. The tooltip text is
 * rendered into the page, so asserting on the rendered HTML also catches a
 * tooltip wired to a key that was never given a translation.
 */
class ActionTooltipsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_product_actions_explain_themselves(): void
    {
        $this->actingAsAdmin();

        Product::factory()->create([
            'activated_at' => null,
            'featured_at' => null,
        ]);

        Livewire::test(ListProducts::class)
            ->assertOk()
            ->assertSee(ProductResource::trans('actions.restock_tooltip'))
            ->assertSee(ProductResource::trans('actions.duplicate_tooltip'))
            ->assertSee(ProductResource::trans('actions.activate_tooltip'))
            ->assertSee(ProductResource::trans('actions.feature_tooltip'))
            ->assertSee(ProductResource::trans('actions.activate_selected_tooltip'))
            ->assertSee(ProductResource::trans('actions.unfeature_selected_tooltip'));
    }

    public function test_contact_message_action_explains_itself(): void
    {
        $this->actingAsAdmin();

        ContactMessage::factory()->create(['read_at' => null]);

        Livewire::test(ListContactMessages::class)
            ->assertOk()
            ->assertSee(ContactMessageResource::trans('actions.mark_read_tooltip'));
    }

    public function test_type_action_explains_itself(): void
    {
        $this->actingAsAdmin();

        Type::factory()->create(['specs_need_review' => true]);

        Livewire::test(ListTypes::class)
            ->loadTable()
            ->assertOk()
            ->assertSee(TypeResource::trans('actions.update_products_tooltip'));
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}

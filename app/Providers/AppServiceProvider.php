<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Cart\CartService;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\LoadedProducts;
use App\Services\Catalog\ProductDiscountResolver;
use App\Services\Payments\PaymentGatewayRegistry;
use App\Services\Wishlist\WishlistService;
use App\View\Components\Header;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        // One cart per request: the resolved pricing cache and the cart lookup
        // are shared by the controller, the header badge and the views.
        $this->app->singleton(CartService::class);

        // One wishlist per request, so every product card's heart toggle reads
        // the same resolved list instead of querying on its own.
        $this->app->singleton(WishlistService::class);

        // One product lookup per request: a listing hands it the page it has
        // just read, so each card's controls read that instance rather than
        // querying the product again.
        $this->app->singleton(LoadedProducts::class);

        // One discount resolution per request, so DiscountObserver can flush the
        // request's loaded discounts when a discount is saved or removed rather
        // than leaving stale pricing behind.
        $this->app->singleton(ProductDiscountResolver::class);

        // One category tree per request: a category or product page reads the
        // whole tree for its header and its breadcrumbs, and this keeps those
        // reads sharing one in-memory copy rather than loading the table again.
        $this->app->singleton(CategoryTreeService::class);

        // One payment registry per request: the drivers are built from
        // config/payments.php once, and the same instances answer the payment
        // step, the review and the validation of a submitted method.
        $this->app->singleton(PaymentGatewayRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('header', Header::class);

        // A refusal reads as "no such thing" rather than "not for you". Every
        // ability in this application guards something that belongs to somebody
        // — an order above all — and those are addressed by references anyone
        // can guess, so answering 403 would tell the enquirer that it exists.
        Gate::defaultDenialResponse(Response::denyAsNotFound());

        // The account password policy, defined once. RegisterRequest validates
        // against it and the registration view reads the very same rule for the
        // browser contract (`passwordrules`) and the hint list, so the frontend
        // can never promise something the backend does not enforce.
        Password::defaults(
            static fn (): Password => Password::min(8)
                ->max(255)
                ->letters()
                ->numbers()
                ->uncompromised(),
        );

        Model::preventLazyLoading(! app()->isProduction());
    }
}

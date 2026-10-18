<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Http\Request;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    public function test_sets_locale_from_plain_string_session_value(): void
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('locale', 'ar');

        $middleware = new SetLocaleMiddleware;

        $response = $middleware->handle($request, static fn ($req) => response('ok'));

        static::assertSame('ar', $this->app->getLocale());
        static::assertSame('rtl', config('app.locale_dir'));
        static::assertSame('ok', $response->getContent());
    }

    public function test_sets_locale_from_array_session_value_and_normalises_it(): void
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('locale', config('locales.ar'));

        $middleware = new SetLocaleMiddleware;

        $middleware->handle($request, static fn () => response('ok'));

        static::assertSame('ar', $this->app->getLocale());
        static::assertSame('ar', $request->session()->get('locale'));
        static::assertSame('rtl', config('app.locale_dir'));
    }

    public function test_falls_back_to_app_locale_when_session_value_is_invalid(): void
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('locale', 'xyz');

        $middleware = new SetLocaleMiddleware;

        $middleware->handle($request, static fn () => response('ok'));

        static::assertSame(config('app.locale'), $this->app->getLocale());
        static::assertSame('ltr', config('app.locale_dir'));
    }
}

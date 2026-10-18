@php
    // The dialog is only usable while the auth routes exist; each form inside
    // disables its submit button otherwise.
    $accountsOpen = Route::has('login.store') && Route::has('register.store');

    // Reopen the dialog after a failed submission made from the dialog itself.
    // The dedicated auth pages render the same errors inline, so they never
    // need the dialog pushed back over the top of them. Each auth form keeps
    // its errors in its own bag, so one panel can never show the other's.
    $authFailed = $errors->getBag('login')->isNotEmpty()
        || $errors->getBag('register')->isNotEmpty();

    $openOnLoad = $authFailed
        && ! request()->routeIs('login', 'register', 'password.request');
@endphp

<div
    class="auth-modal"
    id="auth-modal"
    data-auth-modal
    data-open="{{ $openOnLoad ? 'true' : 'false' }}"
    aria-hidden="{{ $openOnLoad ? 'false' : 'true' }}"
    @unless ($openOnLoad) hidden @endunless
>
    <div class="auth-modal__overlay" data-auth-close></div>

    <div class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
        <header class="auth-modal__header">
            <h2 class="auth-modal__title" id="auth-modal-title">{{ __('components.auth.title') }}</h2>

            <x-button
                variant="bare"
                class="auth-modal__close"
                data-auth-close
                :aria-label="__('components.auth.close')"
            >
                <x-icons.close-icon class="auth-modal__close-icon" aria-hidden="true" />
            </x-button>
        </header>

        @unless ($accountsOpen)
            <p class="auth-modal__notice">{{ __('components.auth.preview') }}</p>
        @endunless

        {{-- Both forms live side by side (stacked on small screens). --}}
        <div class="auth-modal__panels">
            <section class="auth-panel" aria-labelledby="auth-panel-login">
                <h3 class="auth-panel__title" id="auth-panel-login">{{ __('components.header.login') }}</h3>

                {{-- The very same form the /login page renders, kept in one
                     place so the two can never drift apart. --}}
                <x-auth-login-form prefix="auth-login" dialog-focus :disabled="! $accountsOpen" :autofocus="false" />
            </section>

            <section class="auth-panel" aria-labelledby="auth-panel-register">
                <h3 class="auth-panel__title" id="auth-panel-register">{{ __('components.header.signup') }}</h3>

                {{-- The very same form the /register page renders, kept in one
                     place so the two can never drift apart. --}}
                <x-auth-register-form prefix="auth-register" :disabled="! $accountsOpen" :autofocus="false" />
            </section>
        </div>
    </div>
</div>

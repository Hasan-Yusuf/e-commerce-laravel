@props([
    // The form is rendered twice on one page whenever the layout's auth dialog
    // is present, so every id is prefixed with the copy it belongs to.
    'prefix' => 'login',
    // The dialog's copy cannot be submitted while the auth routes are missing.
    'disabled' => false,
    // The page's form owns focus; in the dialog the script moves focus in.
    'autofocus' => true,
    // auth-modal.js focuses the field carrying this attribute when it opens.
    'dialogFocus' => false,
])

<form class="auth-form form" method="POST" action="{{ route('login.store') }}" data-submit-once>
    @csrf

    <x-inputs.text
        name="email"
        type="email"
        :id="$prefix.'-email'"
        :label="__('components.auth.email')"
        autocomplete="email"
        :autofocus="$autofocus"
        :data-auth-focus="$dialogFocus ?: null"
        required
        error-bag="login"
    />

    <x-inputs.text
        name="password"
        type="password"
        :id="$prefix.'-password'"
        :label="__('components.auth.password')"
        autocomplete="current-password"
        required
        error-bag="login"
    />

    <div class="auth-form__row">
        <x-inputs.checkbox
            name="remember"
            :id="$prefix.'-remember'"
            :label="__('components.auth.remember')"
            wrapper-class="auth-form__remember"
        />

        @if (Route::has('password.request'))
            <a class="auth-form__link" href="{{ route('password.request') }}"> {{ __('components.auth.forgot') }} </a>
        @endif
    </div>

    <div class="form-actions">
        <x-button type="submit" block :disabled="$disabled"> {{ __('components.auth.login_submit') }} </x-button>
    </div>
</form>

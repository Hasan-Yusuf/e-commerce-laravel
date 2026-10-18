@props([
    // The form is rendered twice on one page whenever the layout's auth dialog
    // is present, so every id is prefixed with the copy it belongs to.
    'prefix' => 'register',
    // The dialog's copy cannot be submitted while the auth routes are missing.
    'disabled' => false,
    // The page's form owns focus; in the dialog the login panel does.
    'autofocus' => true,
])

<form class="auth-form form" method="POST" action="{{ route('register.store') }}" data-submit-once>
    @csrf

    <x-inputs.text
        name="name"
        :id="$prefix.'-name'"
        :label="__('components.auth.name')"
        autocomplete="name"
        :autofocus="$autofocus"
        required
        error-bag="register"
    />

    <x-inputs.text
        name="email"
        type="email"
        :id="$prefix.'-email'"
        :label="__('components.auth.email')"
        autocomplete="email"
        required
        error-bag="register"
    />

    {{-- Carries the rule's length, character and generator constraints. --}}
    <x-inputs.password
        name="password"
        :id="$prefix.'-password'"
        :label="__('components.auth.password')"
        error-bag="register"
    />

    <x-inputs.text
        name="password_confirmation"
        type="password"
        :id="$prefix.'-password-confirmation'"
        :label="__('components.auth.password_confirmation')"
        autocomplete="new-password"
        required
        error-bag="register"
    />

    <div class="form-actions">
        <x-button type="submit" block :disabled="$disabled"> {{ __('components.auth.register_submit') }} </x-button>
    </div>
</form>

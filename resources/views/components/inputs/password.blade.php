@props([
    'name' => 'password',
    'id' => null,
    'label' => null,
    'required' => true,
    'showErrors' => true,
    'errorBag' => 'default',
])

@php
    // The account password policy, read from the rule the request validates with
    // (configured once in AppServiceProvider). Everything the browser is told —
    // the generator hint, the native constraints, the JS mirror and the hint
    // list — is derived from that one rule, so the frontend can never promise
    // something the server does not enforce.
    //
    // Blade has no `use` imports, so the rule class is named in full: the bare
    // `Password` name in a template resolves to the password-reset broker
    // facade, not to Illuminate\Validation\Rules\Password.
    $passwordRule = \Illuminate\Validation\Rules\Password::defaults();
    $appliedRules = $passwordRule->appliedRules();

    $inputId = $id ?? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $rulesId = $inputId.'-rules';

    // The name Laravel itself would use in the server-side message, so the
    // bubble the browser shows and the inline error the server renders use the
    // very same words.
    $translationKey = "validation.attributes.{$name}";
    $attribute = __($translationKey) === $translationKey
        ? ($label ?? $name)
        : __($translationKey);

    // Native constraints: the length rules are exact, and each character rule
    // becomes a lookahead. A breached password cannot be expressed here (or in
    // JS), so that check stays on the server.
    $requirements = implode('', array_filter([
        $appliedRules['mixedCase']
            ? '(?=.*\p{Ll}.*\p{Lu}|.*\p{Lu}.*\p{Ll})'
            : null,
        $appliedRules['letters'] && ! $appliedRules['mixedCase']
            ? '(?=.*\p{L})'
            : null,
        $appliedRules['numbers'] ? '(?=.*\p{N})' : null,
        $appliedRules['symbols'] ? '(?=.*[\p{Z}\p{S}\p{P}])' : null,
    ]));

    $pattern = $requirements === '' ? null : $requirements.'.*';

    $passwordHints = array_values(array_filter([
        __('auth.password_rules.min', ['count' => $appliedRules['min']]),
        $appliedRules['mixedCase']
            ? __('auth.password_rules.mixed_case')
            : null,
        $appliedRules['letters'] && ! $appliedRules['mixedCase']
            ? __('auth.password_rules.letters')
            : null,
        $appliedRules['numbers'] ? __('auth.password_rules.numbers') : null,
        $appliedRules['symbols'] ? __('auth.password_rules.symbols') : null,
        $appliedRules['uncompromised']
            ? __('auth.password_rules.uncompromised')
            : null,
    ]));

    // Read by the password-policy script, which mirrors the checks below into
    // the field's constraint validation so the browser's own valid/invalid state
    // (and the green/red field styling) agrees with the server's.
    $policy = [
        'min' => $appliedRules['min'],
        'max' => $appliedRules['max'],
        'letters' => $appliedRules['letters'],
        'numbers' => $appliedRules['numbers'],
        'symbols' => $appliedRules['symbols'],
        'mixedCase' => $appliedRules['mixedCase'],
        'messages' => [
            'min' => __('validation.min.string', ['attribute' => $attribute]),
            'max' => __('validation.max.string', ['attribute' => $attribute]),
            'mixedCase' => __('validation.password.mixed', ['attribute' => $attribute]),
            'letters' => __('validation.password.letters', ['attribute' => $attribute]),
            'numbers' => __('validation.password.numbers', ['attribute' => $attribute]),
            'symbols' => __('validation.password.symbols', ['attribute' => $attribute]),
        ],
    ];
@endphp

<x-inputs.text
    :name="$name"
    :id="$inputId"
    :label="$label"
    type="password"
    autocomplete="new-password"
    aria-describedby="{{ $rulesId }}"
    :minlength="$appliedRules['min']"
    :maxlength="$appliedRules['max']"
    :pattern="$pattern"
    passwordrules="{{ $passwordRule->toPasswordRulesString() }}"
    data-password-policy="{{ json_encode($policy, JSON_UNESCAPED_UNICODE) }}"
    :required="$required"
    :show-errors="$showErrors"
    :error-bag="$errorBag"
>
    <x-slot:hint>
        <ul class="form-field__rules" id="{{ $rulesId }}">
            @foreach ($passwordHints as $passwordHint)
                <li class="form-field__rule">{{ $passwordHint }}</li>
            @endforeach
        </ul>
    </x-slot:hint>
</x-inputs.text>

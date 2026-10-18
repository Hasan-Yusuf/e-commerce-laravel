<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Authentication Language Lines
     |--------------------------------------------------------------------------
     |
     | The following language lines are used during authentication for various
     | messages that we need to display to the user. You are free to modify
     | these language lines according to your application's requirements.
     |
     */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'login' => [
        'title' => 'Login',
        'subtitle' => 'Sign in to your account to check out faster and keep your saved products.',
        'alt' => "Don't have an account?",
        'status' => 'Welcome back, :name.',
    ],

    'register' => [
        'title' => 'Create account',
        'subtitle' => 'Create an account to check out faster and keep your cart and wishlist.',
        'alt' => 'Already have an account?',
        'status' => 'Your account is ready.',
    ],

    /*
     |--------------------------------------------------------------------------
     | Password rules
     |--------------------------------------------------------------------------
     |
     | Read from the password rule configured in AppServiceProvider, so the hint
     | on the registration form always describes what the server enforces.
     |
     */

    'password_rules' => [
        'min' => 'At least :count characters',
        'mixed_case' => 'Both uppercase and lowercase letters',
        'letters' => 'At least one letter',
        'numbers' => 'At least one number',
        'symbols' => 'At least one symbol',
        'uncompromised' => 'Not exposed in a known data breach',
    ],

    'forgot' => [
        'title' => 'Forgot password',
        'subtitle' => 'Recover access to your account',
        'message' => 'Password recovery is not available yet. Please contact support if you can no longer sign in.',
        'back' => 'Back to login',
    ],

    'logout' => [
        'status' => 'You have been signed out.',
    ],
];

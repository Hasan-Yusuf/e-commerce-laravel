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

    'failed' => 'بيانات الدخول هذه لا تطابق سجلاتنا.',
    'password' => 'كلمة المرور غير صحيحة.',
    'throttle' => 'محاولات دخول كثيرة. يرجى المحاولة بعد :seconds ثانية.',

    'login' => [
        'title' => 'تسجيل الدخول',
        'subtitle' => 'سجّل الدخول إلى حسابك لإتمام الشراء أسرع والاحتفاظ بالمنتجات التي حفظتها.',
        'alt' => 'ليس لديك حساب؟',
        'status' => 'مرحباً بعودتك، :name.',
    ],

    'register' => [
        'title' => 'إنشاء حساب',
        'subtitle' => 'أنشئ حساباً لإتمام الشراء أسرع والاحتفاظ بسلتك وقائمة مفضلاتك.',
        'alt' => 'لديك حساب بالفعل؟',
        'status' => 'حسابك جاهز.',
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
        'min' => 'على الأقل :count أحرف',
        'mixed_case' => 'أحرف كبيرة وأحرف صغيرة',
        'letters' => 'حرف واحد على الأقل',
        'numbers' => 'رقم واحد على الأقل',
        'symbols' => 'رمز واحد على الأقل',
        'uncompromised' => 'غير مكشوفة في تسريب بيانات معروف',
    ],

    'forgot' => [
        'title' => 'نسيت كلمة المرور',
        'subtitle' => 'استعد الوصول إلى حسابك',
        'message' => 'استعادة كلمة المرور غير متاحة بعد. يرجى التواصل مع الدعم إذا لم تعد قادراً على تسجيل الدخول.',
        'back' => 'العودة إلى تسجيل الدخول',
    ],

    'logout' => [
        'status' => 'تم تسجيل خروجك.',
    ],
];

<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'حقل :attribute يجب أن يكون نصاً.',
    'integer' => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
    'numeric' => 'حقل :attribute يجب أن يكون رقماً.',
    'email' => 'اكتب بريداً إلكترونياً صحيحاً.',
    'url' => 'حقل :attribute يجب أن يكون رابطاً صحيحاً.',
    'in' => 'القيمة المختارة في :attribute غير صحيحة.',
    'exists' => 'القيمة المختارة في :attribute غير موجودة.',
    'unique' => 'قيمة :attribute مستخدمة مسبقاً.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'image' => 'حقل :attribute يجب أن يكون صورة.',
    'file' => 'حقل :attribute يجب أن يكون ملفاً.',
    'mimes' => 'حقل :attribute يجب أن يكون ملفاً من نوع: :values.',
    'array' => 'حقل :attribute غير صحيح.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'min' => [
        'numeric' => ':attribute يجب ألا يقل عن :min.',
        'string' => ':attribute يجب ألا يقل عن :min أحرف.',
        'array' => 'اختر :min على الأقل في :attribute.',
        'file' => ':attribute يجب ألا يقل عن :min كيلوبايت.',
    ],
    'max' => [
        'numeric' => ':attribute يجب ألا يزيد على :max.',
        'string' => ':attribute يجب ألا يزيد على :max حرفاً.',
        'array' => ':attribute يجب ألا يزيد على :max عنصراً.',
        'file' => 'حجم :attribute يجب ألا يزيد على :max كيلوبايت.',
    ],
    'attributes' => [
        'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور', 'file' => 'الملف', 'name' => 'الاسم',
        'phone' => 'رقم الهاتف', 'address' => 'العنوان', 'price' => 'السعر', 'stock' => 'الكمية',
        'old_price' => 'السعر قبل الخصم', 'color' => 'اللون', 'icon' => 'الأيقونة', 'hours' => 'أوقات الدوام',
        'delivery_areas' => 'مناطق التوصيل', 'map_url' => 'رابط الخريطة', 'free_delivery_over' => 'حد التوصيل المجاني',
    ],
];

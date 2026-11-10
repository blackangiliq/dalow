<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // الإعدادات تُحفظ في الذاكرة داخل الطلب الواحد، فنمسحها بين اختبار وآخر
        Setting::flushCache();
    }
}

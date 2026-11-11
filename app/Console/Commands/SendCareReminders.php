<?php

namespace App\Console\Commands;

use App\Services\CareService;
use Illuminate\Console\Command;

class SendCareReminders extends Command
{
    protected $signature = 'care:remind';

    protected $description = 'يرسل تذكيرات إعادة شراء الأدوية المستحقة اليوم للزبائن';

    public function handle(CareService $care): int
    {
        $this->info('أُرسل '.$care->sendDueReminders().' تذكير.');

        return self::SUCCESS;
    }
}

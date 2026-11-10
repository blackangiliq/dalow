<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, TelegramService $telegram)
    {
        abort_unless(hash_equals(TelegramService::webhookSecret(), $secret), 404);
        $telegram->processUpdate($request->all());

        return response()->json(['ok' => true]);
    }
}

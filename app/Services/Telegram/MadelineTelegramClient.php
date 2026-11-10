<?php

namespace App\Services\Telegram;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * عميل تيليجرام بحساب مستخدم حقيقي (MTProto عبر MadelineProto)، يراسل الموظفين بأرقام هواتفهم مباشرة.
 * يربط المدير الحساب مرة واحدة من لوحة التحكم (إشعارات تيليجرام).
 */
class MadelineTelegramClient implements TelegramClient
{
    private ?API $api = null;

    public function __construct(
        private readonly ?string $apiId,
        private readonly ?string $apiHash,
        private readonly string $sessionPath,
        private readonly string $linkedMarkerPath,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->apiId) && filled($this->apiHash);
    }

    public function isLinked(): bool
    {
        return $this->isConfigured() && file_exists($this->linkedMarkerPath);
    }

    public function linkedAccount(): ?array
    {
        return $this->isLinked()
            ? json_decode((string) file_get_contents($this->linkedMarkerPath), true)
            : null;
    }

    public function sendToPhone(string $phone, string $contactName, string $message): array
    {
        $this->assertLinked();
        $international = PhoneNumber::toInternational($phone);
        if (! $international) {
            throw new TelegramDeliveryException('رقم الهاتف غير صحيح.');
        }

        return $this->guard('الإرسال', function () use ($international, $contactName, $message) {
            $result = $this->api()->contacts->importContacts(contacts: [[
                '_' => 'inputPhoneContact',
                'client_id' => random_int(1, PHP_INT_MAX),
                'phone' => $international,
                'first_name' => $contactName,
                'last_name' => '',
            ]]);

            $userId = $result['imported'][0]['user_id'] ?? null;
            if (! $userId) {
                throw new TelegramDeliveryException('الرقم غير مسجَّل في تيليجرام، أو يخفي صاحبه حسابه عن غير جهات الاتصال.');
            }

            $this->api()->messages->sendMessage(peer: $userId, message: $message, parse_mode: ParseMode::HTML);

            $user = collect($result['users'] ?? [])->firstWhere('id', $userId) ?? [];

            return ['id' => (int) $userId, 'name' => $this->userName($user) ?: $contactName];
        });
    }

    public function sendToUser(int|string $userId, string $message): void
    {
        $this->assertLinked();
        $this->guard('الإرسال', function () use ($userId, $message) {
            $this->api()->messages->sendMessage(peer: $userId === 'me' ? 'me' : (int) $userId, message: $message, parse_mode: ParseMode::HTML);
        });
    }

    public function contacts(): array
    {
        $this->assertLinked();

        return $this->guard('جلب جهات الاتصال', function () {
            $result = $this->api()->contacts->getContacts();
            $contacts = [];
            foreach ($result['users'] ?? [] as $user) {
                if (($user['bot'] ?? false) || ($user['self'] ?? false) || ($user['deleted'] ?? false)) {
                    continue;
                }
                $contacts[] = [
                    'id' => (int) $user['id'],
                    'name' => $this->userName($user) ?: 'بدون اسم',
                    'phone' => filled($user['phone'] ?? null) ? (string) $user['phone'] : null,
                    'username' => $user['username'] ?? null,
                ];
            }
            usort($contacts, fn ($a, $b) => strcmp($a['name'], $b['name']));

            return $contacts;
        });
    }

    // ---------- ربط الحساب ----------

    public function startLogin(string $phone): void
    {
        $this->guard('إرسال الكود', fn () => $this->api()->phoneLogin($phone));
    }

    /** @return 'linked'|'password_required'|'signup_required' */
    public function completeLogin(string $code): string
    {
        return $this->guard('تأكيد الكود', function () use ($code) {
            $authorization = $this->api()->completePhoneLogin($code);

            return match ($authorization['_'] ?? null) {
                'account.password' => 'password_required',
                'account.needSignup' => 'signup_required',
                default => $this->markLinked(),
            };
        });
    }

    public function completePasswordLogin(string $password): string
    {
        return $this->guard('تأكيد كلمة المرور', function () use ($password) {
            $this->api()->complete2faLogin($password);

            return $this->markLinked();
        });
    }

    public function logout(): void
    {
        try {
            if (file_exists($this->sessionPath)) {
                $this->api()->logout();
            }
        } catch (Throwable) {
            // نفصل الحساب محلياً حتى إن فشل تسجيل الخروج من طرف تيليجرام (جلسة معطوبة مثلاً)
        }
        @unlink($this->linkedMarkerPath);
    }

    private function markLinked(): string
    {
        $self = $this->api()->getSelf() ?: [];

        file_put_contents($this->linkedMarkerPath, json_encode([
            'id' => $self['id'] ?? null,
            'name' => $this->userName($self),
            'username' => $self['username'] ?? null,
            'phone' => $self['phone'] ?? null,
            'linked_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE));

        return 'linked';
    }

    private function assertLinked(): void
    {
        if (! $this->isLinked()) {
            throw new TelegramDeliveryException('حساب تيليجرام غير مربوط بعد. اربطه من «إشعارات تيليجرام».');
        }
    }

    /** يوحّد أخطاء تيليجرام والاتصال في رسالة عربية واحدة قابلة للعرض */
    private function guard(string $action, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (TelegramDeliveryException $e) {
            throw $e;
        } catch (RPCErrorException $e) {
            throw new TelegramDeliveryException("رفض تيليجرام {$action}: ".$this->explain($e->getMessage()), previous: $e);
        } catch (Throwable $e) {
            // التفاصيل التقنية في السجل، وللمدير رسالة مفهومة
            Log::warning("Telegram client ({$action}): ".$e->getMessage());

            throw new TelegramDeliveryException(
                "تعذّر {$action}: لم يتم الاتصال بخوادم تيليجرام. تأكد أن السيرفر يصل إلى الإنترنت ثم أعد المحاولة. (التفاصيل في storage/logs/laravel.log)",
                previous: $e,
            );
        }
    }

    private function explain(string $rpc): string
    {
        return match (true) {
            str_contains($rpc, 'PHONE_NUMBER_INVALID') => 'رقم الهاتف غير صحيح. اكتبه بالصيغة الدولية مثل +9647701234567',
            str_contains($rpc, 'PHONE_CODE_INVALID') => 'الكود غير صحيح',
            str_contains($rpc, 'PHONE_CODE_EXPIRED') => 'انتهت صلاحية الكود. اطلب كوداً جديداً',
            str_contains($rpc, 'PASSWORD_HASH_INVALID') => 'كلمة المرور غير صحيحة',
            str_contains($rpc, 'API_ID_INVALID') => 'API ID أو API Hash غير صحيح',
            str_contains($rpc, 'FLOOD_WAIT') => 'محاولات كثيرة، انتظر قليلاً ثم أعد المحاولة ('.$rpc.')',
            str_contains($rpc, 'PEER_FLOOD') => 'تيليجرام قيّد الإرسال من هذا الحساب مؤقتاً',
            str_contains($rpc, 'AUTH_KEY_UNREGISTERED'), str_contains($rpc, 'SESSION_REVOKED') => 'انتهت جلسة الحساب. افصل الحساب ثم اربطه من جديد',
            default => $rpc,
        };
    }

    /** @param array<string, mixed> $user */
    private function userName(array $user): string
    {
        return trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? ''));
    }

    private function api(): API
    {
        if ($this->api) {
            return $this->api;
        }
        if (! $this->isConfigured()) {
            throw new TelegramDeliveryException('أدخل API ID و API Hash أولاً (تحصل عليهما من my.telegram.org).');
        }
        if (! class_exists(API::class)) {
            throw new TelegramDeliveryException('مكتبة MadelineProto غير مثبتة. نفّذ composer install على السيرفر.');
        }
        if (! is_dir(dirname($this->sessionPath))) {
            mkdir(dirname($this->sessionPath), 0700, true);
        }

        $settings = (new Settings)
            ->setAppInfo((new AppInfo)->setApiId((int) $this->apiId)->setApiHash((string) $this->apiHash))
            ->setLogger((new LoggerSettings)
                ->setType(Logger::FILE_LOGGER)
                ->setExtra(storage_path('logs/madelineproto.log'))
                ->setLevel(Logger::LEVEL_WARNING));

        return $this->api = new API($this->sessionPath, $settings);
    }
}

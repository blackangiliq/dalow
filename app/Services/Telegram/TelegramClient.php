<?php

namespace App\Services\Telegram;

/**
 * حساب تيليجرام حقيقي (رقم هاتف) يرسل منه الموقع إشعارات الطلبات إلى الموظفين.
 */
interface TelegramClient
{
    /** هل أُدخل API ID و API Hash؟ */
    public function isConfigured(): bool;

    /** هل رُبط الحساب (تم تسجيل الدخول برقم الهاتف)؟ */
    public function isLinked(): bool;

    /** @return array{id: ?int, name: string, username: ?string, phone: ?string, linked_at: string}|null */
    public function linkedAccount(): ?array;

    /**
     * يرسل إلى رقم هاتف (يضيفه كجهة اتصال إن لزم).
     *
     * @return array{id: int, name: string} حساب تيليجرام الذي استلم الرسالة
     *
     * @throws TelegramDeliveryException
     */
    public function sendToPhone(string $phone, string $contactName, string $message): array;

    /**
     * يرسل إلى حساب معروف المعرف (أسرع، بلا بحث بالرقم).
     *
     * @throws TelegramDeliveryException
     */
    public function sendToUser(int|string $userId, string $message): void;

    /**
     * جهات اتصال الحساب المرتبط.
     *
     * @return array<int, array{id: int, name: string, phone: ?string, username: ?string}>
     *
     * @throws TelegramDeliveryException
     */
    public function contacts(): array;
}

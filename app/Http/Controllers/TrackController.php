<?php

namespace App\Http\Controllers;

use App\Models\TrackEvent;
use Illuminate\Http\Request;

/** يستقبل النقرات وزمن مشاهدة الأقسام من المتصفح */
class TrackController extends Controller
{
    public function __invoke(Request $request)
    {
        $visitor = $request->attributes->get('visitor');
        $events = json_decode((string) $request->input('events'), true);
        if (! $visitor || ! is_array($events) || auth('web')->check()) {
            return response()->noContent();
        }
        foreach (array_slice($events, 0, 25) as $event) {
            $kind = $event['k'] ?? '';
            $name = (string) ($event['n'] ?? '');
            if (! in_array($kind, ['click', 'dwell'], true) || ! preg_match('/^[\p{L}\p{N}:_ \-]{1,80}$/u', $name)) {
                continue;
            }
            TrackEvent::create([
                'visitor' => $visitor,
                'day' => today(),
                'kind' => $kind,
                'name' => $name,
                // زمن المشاهدة بالثواني، بحد أقصى عشر دقائق لتجاهل التبويبات المنسية
                'value' => $kind === 'dwell' ? max(1, min(600, (int) ($event['v'] ?? 1))) : 1,
            ]);
        }

        return response()->noContent();
    }
}

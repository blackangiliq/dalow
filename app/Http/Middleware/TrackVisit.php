<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * يسجل زيارة كل صفحة في المتجر لإحصاءات لوحة التحكم.
 * الزائر يُعرَّف بكوكي عشوائي فقط: لا اسم ولا رقم ولا عنوان IP.
 */
class TrackVisit
{
    public const COOKIE = 'dv';

    public function handle(Request $request, Closure $next): Response
    {
        $visitor = $request->cookie(self::COOKIE);
        $fresh = ! is_string($visitor) || ! preg_match('/^[a-f0-9]{32}$/', $visitor);
        if ($fresh) {
            $visitor = Str::lower(Str::random(32));
            $visitor = substr(md5($visitor.microtime()), 0, 32);
        }
        $request->attributes->set('visitor', $visitor);

        $response = $next($request);

        if ($fresh) {
            $response->headers->setCookie(cookie(self::COOKIE, $visitor, 60 * 24 * 365));
        }
        if ($this->shouldTrack($request, $response)) {
            try {
                $route = $request->route();
                $name = (string) $route?->getName();
                [$type, $ref] = match (true) {
                    $name === 'home' => ['home', null],
                    $name === 'category' => ['category', $route->parameter('category')?->id],
                    $name === 'product' => ['product', $route->parameter('product')?->id],
                    $name === 'products' => [$request->filled('q') ? 'search' : 'products', null],
                    in_array($name, ['cart', 'checkout'], true) => [$name, null],
                    $name === 'order.done' => ['order', null],
                    default => ['page', null],
                };
                Visit::create([
                    'visitor' => $visitor,
                    'day' => today(),
                    'type' => $type,
                    'ref_id' => $ref,
                    'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 190),
                    'referrer' => $this->referrer($request),
                    'device' => $this->device((string) $request->userAgent()),
                ]);
            } catch (\Throwable) {
                // الإحصاءات لا تعطّل الصفحة أبداً
            }
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && ! $request->ajax() && ! $request->expectsJson()
            && ! Auth::guard('web')->check() // مدير لوحة التحكم لا يُحسب زائراً
            && ! preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless/i', (string) $request->userAgent());
    }

    private function referrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (! $host || $host === $request->getHost()) {
            return null;
        }
        $host = preg_replace('/^(www|m|l|lm)\./', '', strtolower($host));

        return mb_substr($host, 0, 120);
    }

    private function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/ipad|tablet|(android(?!.*mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/mobile|iphone|android/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }
}

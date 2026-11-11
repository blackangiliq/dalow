<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * حماية النماذج من الرسائل الآلية: حقل مخفي لا يراه الإنسان.
 * البرامج الآلية تملؤه، فنتجاهل الطلب ونرد كأنه نجح.
 */
class Honeypot
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('website')) {
            return back()->with('ok', 'تم الإرسال.');
        }

        return $next($request);
    }
}

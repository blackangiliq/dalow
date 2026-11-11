<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** صفحات الإعداد والإحصاءات للمدير فقط. الموظف يعمل على الطلبات والمنتجات والزبائن. */
class RequireRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('error', 'هذه الصفحة للمدير فقط.');
        }

        return $next($request);
    }
}

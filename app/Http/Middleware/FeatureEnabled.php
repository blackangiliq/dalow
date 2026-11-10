<?php

namespace App\Http\Middleware;

use App\Support\Ui;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** الصفحة تُغلق (404) إذا أوقف المدير خاصيتها من «واجهة الموقع» */
class FeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(Ui::on($feature), 404);

        return $next($request);
    }
}

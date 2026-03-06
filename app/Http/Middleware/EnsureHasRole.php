<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasRole
{
    /**
     * Проверяет, что у аутентифицированного пользователя есть нужная роль.
     *
     * Использование в роутах:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,editor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $request->user()->hasAnyRole($roles)) {
            abort(403, 'Недостаточно прав для выполнения этого действия.');
        }

        return $next($request);
    }
}

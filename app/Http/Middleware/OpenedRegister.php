<?php

namespace App\Http\Middleware;

use App\CashRegister;
use Closure;
use Illuminate\Http\Request;

class OpenedRegister
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     *
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user_id = auth()->user()->id;
        $count = CashRegister::where('user_id', $user_id)->where('status', 'open')->count();
        if ($count == 0) {
            return redirect()->route('cash-register.create');
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Member;
use App\Utils\Messages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()){
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        if($user->role_no === Member::ROLE_MANAGER) {
            return $next($request);
        }
        return response()->json(['message' => Messages::MSG_0017], 403);
    }
}

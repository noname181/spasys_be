<?php

namespace App\Http\Middleware;

use App\Models\Member;
use App\Utils\Messages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()){
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user_role = Auth::user()->role->role_eng;

        foreach($roles as $role) {
            if($user_role == $role){
                return $next($request);
            }
        }

        return response()->json(['message' => Messages::MSG_0017], 405);
    }
}

<?php
 
namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
 
class Authenticate_EWHP
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Authorization');

        if($key == 'key_akljf9823_oert83745'){
            $response = $next($request);
            return $response;
        }
 
        // Perform action
 
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
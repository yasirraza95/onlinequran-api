<?php

namespace App\Http\Middleware;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Closure;

class RoleRestrict
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {   
        if(Auth::guard('user')->check()){
            if (JWTAuth::user()->user_type != "admin") {
                return response()->json([
                    "error" => "Sorry! You don't have access to it."
                ],401);
            }
        }else{
            return response()->json([
                "error" => "Sorry! You don't have access to it."
            ],401);
        }

        return $next($request);
    }
}

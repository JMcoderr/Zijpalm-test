<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfAdminOrSelf
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if(!$user) {
            return redirect()->route('home');
        }

        // routeUser is the user that this route is referencing, i.e website.com/members/{user}.
        $routeUser = $request->route('user');

        if(!$user->is_admin && $user->id !== $routeUser->id){
            return redirect()->route('home');
        }

        return $next($request);
    }
}

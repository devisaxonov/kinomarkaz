<?php

namespace App\Core\Http;

use Closure;

interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response;
}

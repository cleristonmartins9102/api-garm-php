<?php

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

final class JwtDateTimeMiddleware
{

    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        $token = $request->getAttribute('jwt');
        $now = new \DateTime();
        $expired_date = new \DateTime($token['expired_at']);
        if ($now > $expired_date)
            return $response->withStatus(401);
        $response = $next($request, $response);
        return $response;
    }
}

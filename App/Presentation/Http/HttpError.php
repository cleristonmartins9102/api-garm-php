<?php

namespace App\Presentation\Http;

use App\Presentation\Http\Response;
use App\Presentation\Protocol\HttpResponse;
use Throwable;

function serverError(Throwable $error): HttpResponse  {
  return new Response(500, $error);
}

function badRequest($body): HttpResponse  {
  return new Response(400, $body);
}

function ok($body): HttpResponse {
  return new Response(200, $body);
}
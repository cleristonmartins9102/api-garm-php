<?php

namespace App\Presentation\Protocol;

use Domain\Model\Response;

interface Controller {
  public function handle(HttpRequest $request, HttpResponse $response): HttpResponse;
}
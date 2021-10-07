<?php

namespace App\Infra\Http;

use App\Presentation\Protocol\HttpResponse;
use Slim\Http\Response;

class AdaptorResponse implements HttpResponse
{
  protected Response $response;
  public function __construct(Response $response)
  {
    $this->response = $response;
  }
  public function getStatusCode() {}
  public function getBody() {}
  public function withJson() {}

}

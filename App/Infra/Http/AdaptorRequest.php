<?php

namespace App\Infra\Http;

use App\Presentation\Protocol\HttpRequest;
use Slim\Http\Request;
use stdClass;

class AdaptorRequest implements HttpRequest
{
  protected Request $request;
  public function __construct(Request $request)
  {
    $this->request = $request;
  }
  public function getBody(): stdClass
  {
    return json_decode($this->request->getBody(), false);
  }
  public function getUser(): string
  {
    return $this->request->getAttribute('jwt')['name'];
  }
  public function getAttribute(string $value): string {
    return $this->request->getAttribute($value);
  }
}

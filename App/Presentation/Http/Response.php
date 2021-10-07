<?php

namespace App\Presentation\Http;

use App\Presentation\Protocol\HttpResponse;

class Response implements HttpResponse 
{
  public int $statusCode;
  public $body;
  public string $user;

  function __construct(int $statusCode, $body)
  {
    $this->statusCode = $statusCode;
    $this->body = $body;
  }
  public function getBody()
  {
    return $this->body;
  }

  public function getStatusCode(): int
  {
    return $this->statusCode;
  }
}

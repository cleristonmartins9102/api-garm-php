<?php

namespace App\Presentation\Implement;

use App\Presentation\Protocol\HttpRequest;

class Request implements HttpRequest
{
  public int $statusCode;
  public $body;
  public string $user;

  public function getBody()
  {
    return $this->body;
  }
  public function getUser(): string
  {
    return $this->user;
  }

  public function getStatusCode(): int
  {
    return $this->statusCode;
  }
}

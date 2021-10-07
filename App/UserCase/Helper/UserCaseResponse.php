<?php

namespace App\UserCase\Helper;

use App\UserCase\Protocol\CasesResponse;

class UserCaseResponse implements CasesResponse {
  public $statusCode = 0;
  public $body = null;
  public function __construct(int $statusCode, $body)
  {
    $this->statusCode = $statusCode;
    $this->body = $body;
  }

  public function getBody() {
    return $this->body;
  }
  
  public function getStatusCode()
  {
    return $this->statusCode;
  }
}
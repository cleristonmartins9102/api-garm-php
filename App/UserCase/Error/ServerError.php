<?php

namespace App\UserCase\Error;

use Error;

class ServerError extends Error {
  public function __construct(string $message)
  {
    parent::__construct($message);
    $this->name = 'Server Error';
  }
}
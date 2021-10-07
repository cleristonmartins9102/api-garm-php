<?php

namespace App\Presentation\Error;

use Error;

class MissingParamError extends Error {
  public function __construct(string $param)
  {
    parent::__construct("Missing Param: {$param}");
    $this->name = 'Missing Param Error:';
  }
}
<?php

namespace App\Presentation\Error;

use Error;

class ParamError extends Error {
  public function __construct(string $param, $expect, $received)
  {
    parent::__construct("Param Error: expect {$expect} - received: {$received}");
    $this->name = 'ParamError';
  }
}
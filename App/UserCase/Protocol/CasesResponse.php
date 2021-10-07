<?php

namespace App\UserCase\Protocol;

use Domain\Model\Response;

interface CasesResponse extends Response {
  public function getStatusCode();
}
<?php

namespace App\Presentation\Protocol;

use Domain\Model\Response;

interface HttpResponse extends Response {
  public function getStatusCode();
  public function getBody();
}
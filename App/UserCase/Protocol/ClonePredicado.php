<?php

namespace App\UserCase\Protocol;

use Domain\Model\Response;

interface ClonePredicado {
  public function clone(): Response ;
}
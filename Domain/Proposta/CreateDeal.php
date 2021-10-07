<?php

namespace Domain\Proposta;

use Domain\Model\Response;

interface CreateDeal {
  public function create(): Response;
}
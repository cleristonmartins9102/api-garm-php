<?php

namespace Domain\Proposta;

use App\Model\Proposta\Proposta;
use App\UserCase\Helper\UserCaseResponse;
use Domain\Model\Response;

interface CreateNumber
{
  public function create(): Response;
}
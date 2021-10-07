<?php

namespace App\UserCase\Helper;

use DateTime;

function calcDiasConsumo(DateTime $dataInicio, DateTime $dataFinal): int {
  return $dataInicio->diff($dataFinal)->days + 1;
}
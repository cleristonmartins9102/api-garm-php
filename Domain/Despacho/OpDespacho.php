<?php

namespace Domain\Despacho;

use App\Model\Depot\Depot;
use App\Model\Despacho\DespachoStatus;
use Domain\Operacao;

interface OpDespacho extends Operacao
{
  public function deleteContainer($id_container = null);
  public function get_status(): DespachoStatus;
  public function get_depot(): Depot;
  public function get_terminal_operacao_nome();
  public function get_terminal_destino_nome();
  public function liberarFaturamento();

}

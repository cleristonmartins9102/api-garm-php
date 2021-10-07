<?php

namespace App\UserCase\Proposta\PropostaTerminais;

use App\Lib\Database\Record;
use Domain\Proposta\PropostaTerminais\AddPropostaTerminal;

class Add extends Record implements AddPropostaTerminal {
  const TABLENAME = 'PropostaTerminal';
  const MANYTOMANY = true;
  public function add(int $id_proposta, int $id_terminal): void {
    $this->id_proposta = $id_proposta;
    $this->id_terminal = $id_terminal;
    $this->store();
  }
}
<?php

namespace Domain\Proposta\PropostaTerminais;

interface AddPropostaTerminal {  
  public function add(int $id_proposta, int $id_terminal): void;
}
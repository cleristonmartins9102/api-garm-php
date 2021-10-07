<?php

namespace App\Model\Processo\UseCase;

use App\Lib\Database\Record;
use App\Model\Processo\Protocol\GetFornecedor;
use App\Model\Processo\Processo;
use App\Model\Pessoa\Individuo;
use App\Presentation\Error\ParamError;

class ProcessoGetFornecedor extends Record implements GetFornecedor {
  private $id_processo;
  public function __construct($id_processo)
  {
    $this->id_processo = $id_processo;
  }
  public function get_fornecedor_nome(): string
  {
    $processo = new Processo($this->id_processo);
    $fornecedor = new Individuo($processo->id_fornecedor);
    if (is_null($fornecedor->nome)) return '';
    return $fornecedor->nome;
  }
}
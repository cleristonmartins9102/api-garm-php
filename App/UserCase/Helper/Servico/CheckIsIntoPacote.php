<?php

namespace App\UserCase\Helper\Servico;

use App\Model\Servico\Pacote;

class CheckIsIntoPacote 
{
  protected $lista_pacote;
  protected $item;
  public function __construct(array $list, $item)
  {
    $this->lista_pacote = $list;
    $this->item = $item;
  }
  public function check(): bool
  {
    for ($i = 0; $i < count($this->lista_pacote); $i++) {
      if (($this->item->dimensao === 'ambos' or $this->item->dimensao === 'nenhum') or $this->lista_pacote[$i]->dimensao === $this->item->dimensao) {
        $pac = (new Pacote())('id_predicado', $this->lista_pacote[$i]->id_predicado);
        $pac->id_servico = $this->item->id_predicado;
        return (count($pac->pacoteHasPredicado) > 0);
      }
    }
    return false;
  }
}

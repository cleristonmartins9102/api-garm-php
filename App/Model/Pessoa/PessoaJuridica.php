<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;

/**
 *
 */
class PessoaJuridica extends Record implements PessoaInterface
{
  const TABLENAME = "PessoaJuridica";

  public function set_individuo(Individuo $individuo)
  {
    $individuo->tipo = self::TABLENAME;
    $individuo->store();
    $this->id_individuo = $individuo->id;
    $this->id_pessoajuridica = $individuo->id;
    return $this->store();
  }
}






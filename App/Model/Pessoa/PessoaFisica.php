<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;

class PessoaFisica extends Record implements PessoaInterface
{
    const TABLENAME = "PessoaFisica";

    public function set_individuo(Individuo $individuo)
    {
      //Gravando o individuo
      $individuo->tipo = self::TABLENAME;
      $individuo->store();
      //Gravando a pessoa fisica
      $this->id_individuo = $individuo->id;
      $this->id_pessoafisica = $individuo->id;
      return $this->store();
    }
}


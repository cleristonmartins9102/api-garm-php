<?php
namespace App\Model\Aplicacao;

use App\Lib\Database\Record;
use App\Model\Pessoa\Contato;
use App\Model\Shared\EmailCredencial;

/**
 *
 */
class AplicacaoModulo extends Record
{
  const MANYTOMANY = true;
  const TABLENAME = "AplicacaoModulo";

  public function get_contatos()
  {
    return new Contato($this->id_contato);
  }

  public function get_modulo()
  {
    return new Modulo($this->id_modulo);
  }

  public function get_credencial() {
    return new EmailCredencial($this->id_emailcredencial);
  }
}

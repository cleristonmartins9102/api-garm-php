<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;

class Endereco extends Record
{
  const TABLENAME = 'Endereco';
  private $cidade;

  public function get_cidade()
  {
    return (new Cidade($this->id_cidade));
  }

  public function get_estado()
  {
    return (new Estado((new Cidade($this->id_cidade))->id_estado));
  }
}

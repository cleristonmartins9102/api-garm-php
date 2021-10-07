<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;

class GrupoContato extends Record
{
  const MANYTOMANY = 'true';
  const TABLENAME = 'GrupoContato';

  public function get_contato()
  {
    return new Contato($this->id_contato);
  }
}

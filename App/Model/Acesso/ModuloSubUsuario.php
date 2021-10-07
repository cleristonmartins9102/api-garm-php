<?php

namespace App\Model\Acesso;

use App\Lib\Database\Record;
use App\Model\Acesso\Permissao;

class ModuloSubUsuario extends Record
{
    const TABLENAME = 'ModuloSubUsuario';

    public function permissao() {
        return new Permissao($this->id_permissao);
    }
}

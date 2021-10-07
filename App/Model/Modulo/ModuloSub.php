<?php

namespace App\Model\Modulo;

use App\Lib\Database\Record;
use App\Model\Aplicacao\Modulo;

class ModuloSub extends Record
{
    const TABLENAME = 'ModuloSub';

    public function get_modulo() {
        return new Modulo($this->id_modulo);
    }

    public function get_tipo() {
        return new ModuloSubTipo($this->id_modulosubtipo);
    }
}

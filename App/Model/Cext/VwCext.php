<?php

namespace App\Model\Cext;

use App\Lib\Database\Record;

class VwCext extends Record
{
    const TABLENAME = 'VwCext';

    public function get_tipo() {
        return new CextTipo($this->id_tipo);
    }
}

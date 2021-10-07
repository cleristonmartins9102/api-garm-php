<?php

namespace App\Model\Comissionario;

use App\Lib\Database\Record;

use App\Model\Comissionario\Comissionario;
use App\Model\Vendedor\Vendedor;
use App\Model\Shared\UniCob;


class Comissionario extends Record
{
    const TABLENAME = 'Comissionario';

    public function get_unicob_unidade() {
        return (new Unicob($this->id_unicob))->unidade;
    }

    public function get_faturas() {
        $comissionario = new Comissionario(1);
        
    }
}

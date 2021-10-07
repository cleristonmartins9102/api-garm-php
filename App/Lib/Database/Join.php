<?php

namespace App\Lib\Database;

use App\Lib\Database\Filter;
// tipo tabela (filtro) 
// JOIN TAB on TAB.ID = dsdsd

class Join
{
    const INNER = 'INNER JOIN';
    const LEFT = 'LEFT JOIN';
    const RIGHT = 'RIGHT JOIN';

    final function __construct($forma = INNER, $tab, Filter $filter ) {

    }

}

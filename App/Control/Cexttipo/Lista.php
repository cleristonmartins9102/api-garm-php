<?php

namespace App\Control\Cexttipo;

use App\Mvc\Controller;
use App\Model\Cext\CextTipo;

class Lista extends Controller
{
    public function all()
    {
        self::openTransaction();
        $object = (new CextTipo)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $cext_tipo) {
            $dataFull['items'][] = $cext_tipo->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }
}

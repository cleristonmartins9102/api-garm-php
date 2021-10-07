<?php

namespace App\Control\Imposto;

use App\Mvc\Controller;
use App\Model\Imposto\Imposto;

class Lista extends Controller
{
    public function all()
    {
        self::openTransaction();
        $object = (new Imposto)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $imposto) {
            $dataFull['items'][] = $imposto->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }
}

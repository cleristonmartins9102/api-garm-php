<?php

namespace App\Control\Cextclassificacao;

use App\Mvc\Controller;
use App\Model\Cext\CextClassificacao;

class Lista extends Controller
{
    public function all()
    {
        self::openTransaction();
        $object = (new CextClassificacao)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $cext_classificacao) {
            $dataFull['items'][] = $cext_classificacao->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }
}

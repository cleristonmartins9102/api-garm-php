<?php

namespace App\Control\Cextenquadramento;

use App\Mvc\Controller;
use App\Model\Cext\CextClassificacao;
use App\Model\Cext\CextEnquadramento;

class Lista extends Controller
{
    public function all()
    {
        self::openTransaction();
        $object = (new CextEnquadramento)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $cext_enquadramento) {
            $dataFull['items'][] = $cext_enquadramento->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }
}

<?php

namespace App\Control\Cext;

use App\Mvc\Controller;
use App\Model\Cext\VwCext;
use App\Model\Imposto\Imposto;

class Lista extends Controller
{
    public function all()
    {
        self::openTransaction();
        $object = (new VwCext)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $cext) {
            if ($cext->tipo->tipo === 'imposto') {
                $imposto = $this->getImposto($cext->id_imposto);
                $cext->nome = $imposto->nome;
            }
            $dataFull['items'][] = $cext->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }

    private function getImposto($id_imposto): Imposto {
        return new Imposto($id_imposto);
    }
}

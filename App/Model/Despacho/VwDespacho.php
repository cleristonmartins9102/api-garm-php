<?php
namespace App\Model\Despacho;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

class VwDespacho extends Record {
    const TABLENAME = 'VwDespacho';

    public function addEvento($evento = null, $app_forward = null, $app = null)
    {
        $despacho_evento = new DespachoEvento();
        $despacho_evento->id_despacho = $this->id_despacho;
        $despacho_evento->id_forward = $app_forward;
        $despacho_evento->evento = $evento;
        $despacho_evento->store();
    }

    public function get_eventos()
    {
        // SELECT * FROM DespachoEvento
        $criteria = new Criteria;
        $criteria->add(new Filter('id_despacho', '=', $this->id_despacho));
        $repository = new Repository('App\Model\Despacho\DespachoEvento');
        $object = $repository->load($criteria);
        // print_r($object);
        // exit();
        foreach ($object as $key => $evento) {
            $this->eventos[] = $evento->toArray();
        }
        return $this->eventos ?? [];
    }

    public function get_proposta()
    {
        return new Proposta($this->id_proposta);
    }

}
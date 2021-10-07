<?php
namespace App\Model\Operacao;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Proposta\Proposta;
use App\Model\Captacao\Container;


class VwOperacaoExportacao extends Record
{
    const TABLENAME = 'VwOperacaoExportacao';

    public function get_eventos()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_despacho', '=', $this->id_despacho));
        $repository = new Repository('App\Model\Despacho\DespachoEvento');
        $object = $repository->load($criteria);
        foreach ($object as $key => $evento) {
            $this->eventos[] = $evento->toArray();
        }
        return $this->eventos ?? [];
    }

    public function get_proposta() {
        $proposta = new Proposta($this->id_proposta);
        return $proposta ?? [];
    }

    public function get_containeres()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_despacho', '=', $this->id_despacho ?? $this->id));
        $repository = new Repository('App\Model\Despacho\DespachoContainer');
        $despacho_containeres = $repository->load($criteria);
        // Percorrendo o array com os objetos para pegar os objetos
        foreach ($despacho_containeres as $key => $conteiner_value) {
            $container = new Container($conteiner_value->id_container);
            $container->dimensao = $container->dimensao;
            $containeres[] = $container->toArray();
        }
        return $containeres ?? [];
    }

}

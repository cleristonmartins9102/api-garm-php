<?php

namespace App\Model\Cext;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

class Cext extends Record
{
    const TABLENAME = 'Cext';
    private $custos;
    private $custo_valor_interno;
    private $custo_valor_cliente;
    
    public function getListaByTipo(string $tipo, string $enq) {
        $classificacao = new CextClassificacao();
        $classificacao('classificacao', $tipo);

        $enquadramento = new CextEnquadramento();
        $enquadramento('enquadramento', $enq);

        if (!isset($classificacao->id_cextclassificacao) && $classificacao->id_cextclassificacao === null)
            echo "Sem classificacao $tipo cadastrada";

        if (!isset($enquadramento->id_cextenquadramento) && $enquadramento->id_cextenquadramento === null)
            echo "Sem enquadramento $enquadramento cadastrado";

        $criteria = new Criteria;
        $criteria->add(new Filter('id_classificacao', '=', $classificacao->id_cextclassificacao));
        $criteria->add(new Filter('id_enquadramento', '=', $enquadramento->id_cextenquadramento));
        $repository = (new Repository(Cext::class))->load($criteria);
        foreach ($repository as $key => $cext) {
            $dataFull[] = $cext->toArray();
            $this->custo_valor_interno += $cext->valor;
            $this->custo_valor_cliente += $cext->valor;
        }
        $this->custos = $dataFull ?? [];
    }

    public function get_custo_valor_cliente() {
        return $this->custo_valor_cliente;
    }
    public function get_custo_valor_interno() {
        return $this->custo_valor_interno;
    }
}

<?php
namespace App\Model\Fatura;

use App\Lib\Database\Record;
use App\Model\Moeda\Moeda;
use App\Model\Servico\Servico;
use App\Model\Servico\Predicado;
use App\Model\Fatura\FaturaItemPro;

class FaturaItem extends Record
{
    const TABLENAME = 'FaturaItem';

    public function get_moeda() {
        return new Moeda($this->id_moeda);
    }

    public function get_servico() {
        $predicado = new Predicado($this->id_predicado);
        return (new Servico($predicado->id_servico))->nome;
    }

    public function get_legenda() {
        return new FaturaItemLegenda($this->id_faturaitemlegenda);
    }

    public function get_locked() {
        $imp = (new FaturaItemPro)('id_faturaitem', $this->id_faturaitem);
        return $imp->valor_custo_imposto_locked ?? 'TRUE';
    }
}

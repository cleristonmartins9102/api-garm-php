<?php
namespace App\Model\Fatura;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Servico\Predicado;

class FaturaItemCustom extends Record
{
    const TABLENAME = 'FaturaItemCustom';

    public function get_custom()
    {
        $custom = null;
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicado', '=', $this->id_predicado));
        $repository = new Repository('App\Model\Fatura\FaturaItemCustom');;

        if (count($repository->load($criteria)) > 0) {
            $desc = $repository->load($criteria)[0]->field;
            $desc = explode(",", $desc);
            foreach ($desc as $key => $value) {
                if (strpos($value, '[') || strpos($value, ']')) {
                    $value = trim($value);
                    $value = str_replace('[', '', $value);
                    $value = str_replace(']', '', $value);
                    $custom .= $this->item->{$value};
                } else {
                    $custom .= $value;
                }
            }
            // $predicado = (new Predicado($this->item->id_predicado));
            // $predicado = $predicado->descricao . $custom;
            $predicado = $this->item->descricao . $custom;
        } else {
            // return null;
            // $predicado = (new Predicado($this->item->id_predicado));
            // $predicado = $predicado->descricao;
            $predicado = $this->item->descricao;
        }
        return $predicado;
    }
}

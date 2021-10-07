<?php

namespace App\Control\Processo;

use App\Model\Servico\ItemNecessita;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Mvc\Controller;
use Slim\Http\Response;
use Slim\Http\Request;

class Serdep extends Controller
{
    public function find(Request $request, Response $response, $data ) {
        $data = (object) $data;
        self::openTransaction();
        $itens_nessessarios = new Itens;
        // Interando todos os itens do processo
        $itens_nessessarios->itens_processo = $data->itens;
        foreach ($data->itens as $key => $item) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_item', '=', $item['id_predicado']));
            $necessarios = (new Repository(ItemNecessita::class))->load($criteria);
            foreach ($necessarios as $key => $necessario) {
                if ($necessario->isLoaded()) {
                    $necessario->item = $item;
                    if ($itens_nessessarios->itemFound($necessario)) {
                        $itens_nessessarios->itemFound($necessario)->merge($necessario);
                    } else {
                        $itens_nessessarios->setItens($necessario);
                    }
                }
            }      
        }
        // exit();

        self::closeTransaction();
        return ($itens_nessessarios->getItensNecessarios());
    }
}

class Itens 
{
    public $itens = [];

    public function setItens($item) {
        $this->itens[] = $item;
    }

    public function set_itens_processo($data) {
        $this->itens_processo = $data;
    }

    public function itemFound(ItemNecessita $item_necessita) {
        foreach ($this->itens as $key => $item) {
            if ($item->id_itemnecessario === $item_necessita->id_itemnecessario) {
                return $item;
            }
        }
        return false;
    }

    public function getItensNecessarios() {
        return $this->toArray();
    }

    private function toArray() {
        foreach ($this->itens as $item) {
            if (\in_array_r($item->id_itemnecessario, $this->itens_processo, true)) {
                $itens[] = $item->toArray();
            }
        }
        return $itens ?? [];
    }
}


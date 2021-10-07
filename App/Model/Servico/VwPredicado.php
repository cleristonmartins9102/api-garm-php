<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;

class VwPredicado extends Record
{
    const TABLENAME = "VwPredicado";

    public function allByRegime() {
        if (isset($this->regime)) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_regime', '=', $this->regime));
            $repository = (new Repository(get_class()))->load($criteria);
            return $repository;
        }
    }

  
    public function set_enableUtil() {
        $this->utilizado = 'yes';
    }

    public function get_checkUtilizado() {
        return $this->utilizado;
    }

    public function get_servico()
    {
        return new Servico($this->id_servico);
    }

    public function get_adicional() 
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicadomaster', '=', $this->id_predicado));
        $criteria->add(new Filter('tipo', '=', 'excesso_valormercadoria'));
        $repository = new Repository('App\Model\Servico\ItemCondicional');
        $adicional = $repository->load($criteria);
        if (count($adicional) > 0 ) {
            return new Predicado($adicional[0]->id_predicadocondicionado);
        }
        return new Predicado;
    }

    public function get_itemmaster() 
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicadoslave', '=', $this->id_predicado));
        $repository = new Repository('App\Model\Servico\ItemMaster');
        $adicional = $repository->load($criteria);
        if (count($adicional) > 0 ) {
            return new Predicado($adicional[0]->id_predicadocondicionado);
        }
        return new Predicado;
    }

    // Metodo que verifica se o predicado esta sendo usado, se ele faz parte de algum grupo de contato e se pode ser exluido
    public function checkIsDeletable()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicado', '=', $this->id_predicado));
        $repository_pro = new Repository('App\Model\Proposta\PropostaPredicado');
        $repository_pac = new Repository('App\Model\Servico\PacotePredicado');
        $response_pro = $repository_pro->load($criteria);
        $response_pac = $repository_pac->load($criteria);
        if (count($response_pro) > 0 || count($response_pac) > 0) {
            return true;
        } else {
            return false;
        }
    }
}

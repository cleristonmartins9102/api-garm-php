<?php
namespace App\Model\Proposta;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;
use App\Model\Servico\Predicado;
use App\Model\Servico\PreProAppValor;
use App\Model\Pessoa\Cidade;
use App\Model\Pessoa\Estado;
use App\Model\Proposta\PropostaPredicadoCidade;

class PropostaPredicado extends Record
{
    const TABLENAME = "PropostaPredicado";
    private $cidades = null;
    // const MANYTOMANY = true;

    public function get_predicado()
    {
      return new Predicado($this->id_predicado);
    }

    public function get_appvalor() {
      return new PreProAppValor($this->id_predproappvalor);
    }

    public function addCidade(Array $cidades) {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_propostapredicado', '=', $this->id ?? $this->id_propostapredicado));
      (new PropostaPredicadoCidade)->deleteByCriteria($criteria);
      foreach($cidades as $cidade) {
        if ( $cidade->isLoaded() ) { 
          if ( empty($this->id) ) 
            $this->store();

          $pro_pre_cidade = new PropostaPredicadoCidade;        
          $pro_pre_cidade->id_cidade = $cidade->id_cidade;
          $pro_pre_cidade->id_propostapredicado = $this->id;
          $pro_pre_cidade->store();
        }
      }
    }

      // select * from PropostaPredicadoCidade
    // delete from PropostaPredicadoCidade where 1

    public function get_cidade() {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_propostapredicado', '=', $this->id_propostapredicado));
      return (new Repository('App\Model\Proposta\PropostaPredicadoCidade'::class))->load($criteria);
   }

   public function get_estado() {
     if ( count($cidade = $this->cidade) > 0 ) 
      return $this->cidade[0]->estado;
     return new Estado;
   }
}


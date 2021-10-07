<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;
use App\Model\Servico\Predicado;

class VwPacote extends Record
{
   private $itens;
   const TABLENAME = "VwPacote";

   public function addItem(PacotePredicado $predicado_item) {
      if (!empty($predicado_item)){
         $predicado_item->id_pacote = $this->id;  
         return $predicado_item->store();
      }
   }  

   public function get_nome()
   {
      return (new Predicado($this->id_predicado))->nome;
   }

   public function get_item() {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_pacote', '=', $this->id_pacote));
      $repository = new Repository('App\Model\Servico\PacotePredicado');
      $pacotes_predicado = $repository->load($criteria); 
      foreach ($pacotes_predicado as $key => $pacote_predicado) {
         $item['nome'] = $pacote_predicado->nome;
         $item['predicado'] = $pacote_predicado->nomepredicado->nome;
         $item['id_predicado'] = $pacote_predicado->id_predicado;
         $this->itens[] = $item;
      }
      return $this->itens;
   }
   
   public function deleteItem(Array $item=null)
   {
     if ($item == null) {
       $criteria = new Criteria;
       $criteria->add(new Filter('id_pacote', '=', $this->id_pacote));
       $ind_contato = new PacotePredicado;
       $ind_contato->deleteByCriteria($criteria);
     }
   }
}

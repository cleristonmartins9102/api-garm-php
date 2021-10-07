<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;

class Servico extends Record
{
   const TABLENAME = "Servico";

   public function addPredicado(Predicado $predicado) {
      if (!empty($predicado)){
         $predicado->id_servico = $this->id ?? $this->id_servico;
         $predicado->store();
         //print_r($predicado);
      }
   }

   public function deletePredicado(Array $predicado=null)
   {
     if ($predicado == null) {
       $criteria = new Criteria;
       $criteria->add(new Filter('id_servico', '=', $this->id_servico));
       $repository = new Repository('App\Model\Servico\Predicado');
       $predicados = $repository->load($criteria);
       foreach ($predicados as $key => $predicado) {
          $predObj = new Predicado($predicado->id_predicado);
          if (!$predObj->checkIsDeletable()) {
             $predObj->checkIsDeletable() . '<br>';
             $predObj->delete();
          }
       }
     }
   }

   public function get_predicado() {
      // self::openTransaction();
      $repository = new Repository('App\Model\Servico\Predicado');
      $criteria = new Criteria;
      $criteria->add(new Filter('id_servico', '=', $this->id_servico));
      $object = $repository->load($criteria);
      $predicados = array();
      foreach ($object as $key => $value) {
         $predArray = $value->getData();
         $predArray['in_use'] = $value->checkIsDeletable();
         array_push($predicados, $predArray);
      }
      return $predicados;
   }
}

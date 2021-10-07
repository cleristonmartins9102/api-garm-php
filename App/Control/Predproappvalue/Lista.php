<?php
namespace App\Control\Predproappvalue;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\PreProAppVal;
use App\Model\Servico\Servico;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response, Array $param=null)
    {
      if ($param == null) {
        $param = array( 'sort' => 'nome',
                        'order' => 'asc'
        );
      }
      self::openTransaction();
      $dataFull = array();
      $criteria = parent::criteria($param);
      $repository = new Repository('App\Model\Servico\PreProAppValor');
      $object = $repository->load($criteria);    
      $dataFull['total_count'] = count($object);
      $dataFull['items'] = array();  
      if (count($object) > 0){
        foreach ($object as $key => $value) {
          $predicado = $value->getData();
          $predicado['servico'] = (new Servico($value->id_servico))->nome;
          $dataFull['items'][] = $predicado;
        }
      }
      self::closeTransaction();
      usort($dataFull['items'], function($item1, $item2){
        return $item1['nome'] <=> $item2['nome'];
      });
      return $dataFull;
    }
}

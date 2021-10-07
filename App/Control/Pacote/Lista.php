<?php
namespace App\Control\Pacote;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\Predicado;
use App\Model\Servico\PacotePredicado;
use App\Model\Servico\Pacote;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response, Array $param=null)
    {
      try {
        self::openTransaction();
        $object = (new Pacote)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
  
        $criteria = parent::criteria($param);
        $repository = new Repository('App\Model\Servico\Pacote');
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$pacote) {
          $pacote->nome = $pacote->nome;
          $pacote->complementos = [ 'items' => $pacote->item ];
          $dataFull['items'][] = $pacote->toArray();
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return isset($dataFull) ? $dataFull : null;

    }

    public function bynome(Request $request, Response $response, $nome = null)
    { 
      if ($nome != null){
        self::openTransaction();
        $criteria = new Criteria;
        $criteria->add(new Filter('nome', '=', utf8_encode($nome)));
        $repository = new Repository('App\Model\Servico\Servico');
        $object = $repository->load($criteria);
        foreach ($object as $key => $servico) {
          $servicoArr = $servico->getData();
          $servicoArr['predicados'] = $servico->predicado;
        }
        usort($servicoArr['predicados'], function($item1, $item2) {
          return $item1['nome'] <=> $item2['nome'];
        });
        self::closeTransaction();
        if (!empty($servicoArr)){
          return json_encode($servicoArr);
        }else{
          return null;
        }
      }
    }

    public function byid(Request $request, Response $response, $id = null)
    { 
      if (is_null($id))
        return [];
        self::openTransaction();
        $pacote = new Pacote($id['id']);
        $pacoteArr = $pacote->getData();
        $pacoteArr['predicados'] = $pacote->predicado;
        $pacoteArr = $pacote->getData();
        $pacoteArr['nome'] = $pacote->nome;
        $pacoteArr['predicados'] = $pacote->item;
        self::closeTransaction();
        if (!empty($pacoteArr)){
          return $pacoteArr;
        }else{
          return null;
        }
      
    }

    public function filtered(Request $request, Response $response, Array $filter=null)
    {
      try {
        self::openTransaction();
        $object = (new Pacote)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
  
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Servico\VwPacote');
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$pacote) {
          $pacoteArr = $pacote->getData();
          $pacoteArr['complementos']['items'] = $pacote->item;
          $dataFull['items'][] = $pacoteArr;
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return json_encode(isset($dataFull) ? $dataFull : null);

    }
}

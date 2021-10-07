<?php
namespace App\Control\Servico;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\Predicado;
use App\Model\Servico\Servico;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
  public function all(Request $request, Response $response, array $param = null)
  {
    try {
      if (is_null($param) || count($param) == 0) {
        $param = array(
          'sort' => 'nome',
          'order' => 'asc'
        );
      }
      self::openTransaction();
      $criteria = parent::criteria($param);
      $repository = new Repository('App\Model\Servico\Servico');
      $object = $repository->load($criteria);
      $servico = new Servico;
      $dataFull = array();
      $dataFull['total_count'] = count($servico->all());
      $dataFull['items'] = array();
        
        //Checando se encontrou algum objeto
      if (count($object) > 0) {
          //Percorrendo por cada objeto encontrado
        foreach ($object as $key => $value) {
            //Dados em array do objeto
          $data = $value->getData();
          $data['complementos']['predicados'] = $value->predicado;
          array_push($dataFull['items'], $data);
        }
        usort($dataFull['items'], function ($item1, $item2) {
          return $item1['nome'] <=> $item2['nome'];
        });

      }
      self::closeTransaction();
      return $dataFull;
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function bynome(Request $request, Response $response, Array $servico = null)
  {
   if (is_null($servico))
      return [];
      $servico = (object) $servico;
      self::openTransaction();
      $criteria = new Criteria;
      $criteria->add(new Filter('nome', '=', $servico->nome));
      $repository = new Repository('App\Model\Servico\Servico');
      $servico = $repository->load($criteria);
      if (count($servico) === 0)
        return [];

      $servico = $servico[0];
      $servico->predicados = $servico->predicado;
      // if (isset($dataFull['predicados'])) {
      //   usort($dataFull['predicados'], function ($item1, $item2) {
      //     return $item1['nome'] <=> $item2['nome'];
      //   });
      // }
      self::closeTransaction();
      return $servico->toArray();
  }

  public function byid(Request $request, Response $response, $id = null)
  { 
    if ($id != null) {
      self::openTransaction();
      $object = new Servico($id['id']);
      $dataFull = $object->toArray();
      $dataFull['predicados'] = $object->predicado;
      // foreach ($object as $key => $servico) {
      //   $data = $servico->getData();
        // $data['complementos']['predicados'] = $servico->predicado;
        // array_push($dataFull['items'], $data);
      // }
      self::closeTransaction();
      return $dataFull ?? [];
    }
  }

  public function filtered(Request $request, Response $response, array $filter = null)
  {
    self::openTransaction();
      $criteria = parent::filterColunm($filter);
      $repository = new Repository('App\Model\Servico\Servico');
      $object = $repository->load($criteria);
      $servico = new Servico;
      $dataFull = array();
      $dataFull['total_count'] = count($servico->all());
      $dataFull['items'] = array();
        
        //Checando se encontrou algum objeto
      if (count($object) > 0) {
          //Percorrendo por cada objeto encontrado
        foreach ($object as $key => $value) {
            //Dados em array do objeto
          $data = $value->getData();
          $data['complementos']['predicados'] = $value->predicado;
          array_push($dataFull['items'], $data);
        }
        usort($dataFull['items'], function ($item1, $item2) {
          return $item1['nome'] <=> $item2['nome'];
        });

      }
      self::closeTransaction();
      return json_encode($dataFull);
  }
}

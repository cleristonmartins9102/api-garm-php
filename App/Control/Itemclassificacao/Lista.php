<?php
namespace App\Control\Itemclassificacao;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\ItemClassificacao;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
  public function all(Request $request, Response $response, array $param = null)
  {
    try {
      self::openTransaction();
      $item_classificacao = (new ItemClassificacao)->all();
      foreach ($item_classificacao as $key => $classificacao) {
        $dataFull[] = $classificacao->toArray();        
      }
      self::closeTransaction();

      return $dataFull ?? [];
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function byid(Request $request, Response $response, $id = null)
  { 
    if ($id != null) {
      self::openTransaction();
      $criteria = new Criteria;
      $criteria->add(new Filter('id_servico', '=', $id));
      $repository = new Repository('App\Model\Servico\ItemPadrao');
      $object = $repository->load($criteria);
      foreach ($object as $key => $servico) {
        $dataFull[] = $servico->toArray();
      }
      self::closeTransaction();
      return isset($dataFull) ? json_encode($dataFull) : null;
    }
  }
}

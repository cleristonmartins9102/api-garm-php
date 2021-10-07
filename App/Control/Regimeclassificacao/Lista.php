<?php
namespace App\Control\RegimeClassificacao;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Regime\RegimeClassificacao;

class Lista extends Controller
{
    public function byregime(Request $request, Response $response, array $data)
    {  
        $id_regime = $data['id_regime'];
        self::openTransaction();
        $criteria = new Criteria;
        $criteria->add(new Filter('id_regime', '=', $id_regime));
        $repository = (new Repository(RegimeClassificacao::class))->load($criteria);
        foreach ($repository as $key => $regime_classificacao) {
            $dataFull[] = $regime_classificacao->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }

    public function filtered(Request $request, Response $response, array $filter = null)
    {
      self::openTransaction();
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Terminal\VwTerminal');
        $object = $repository->load($criteria);
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $terminal) {
            $dataFull['items'][] = $terminal->toArray();
        } 
        self::closeTransaction();
        return $dataFull;
    }

    public function byid(Request $request, Response $response, $id = null)
  {
    if ($id != null) {
      self::openTransaction();
      $object = new Terminal($id);
      $dataFull = $object->toArray();
      self::closeTransaction();
      return $dataFull;
    }
  }
}

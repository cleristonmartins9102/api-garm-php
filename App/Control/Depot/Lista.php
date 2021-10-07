<?php
namespace App\Control\Depot;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Depot\VwDepot;
use App\Model\Depot\Depot;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response)
    {   
        self::openTransaction();
        $object = (new VwDepot)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $depot) {
          $dataFull['items'][] = $depot->getData();
        }
        self::closeTransaction();
        return $dataFull;
    }

    public function alldropdown(Request $request, Response $response)
    {   
        self::openTransaction();
        $criteria = new Criteria;
        $criteria->add(new Filter('status', '=', 'ativado'));
        $repository = (new Repository('App\Model\Depot\Status'))->load($criteria);
        if ( count($repository) === 0 ) return;

        $id_status = $repository[0]->id_status;
        $criteria->clean();
        $criteria->add(new Filter('id_depotstatus', '=', $id_status));
        $repository = (new Repository('App\Model\Depot\Depot'))->load($criteria);
        $dataFull = array();
        $dataFull['total_count'] = count($repository);
        $dataFull['items'] = array();
        foreach ($repository as $key => $depot) {
          $depot->id_estado = $depot->individuo->endereco->cidade->estado->id_estado;
          $depot->cidade = $depot->individuo->endereco->cidade->nome;
          $depot->id_cidade = $depot->individuo->endereco->cidade->id_cidade;
          $depot->margem = $depot->margem->margem;
          $depot->removeProperty([
            'created_at',
            'created_by',
            'id_depotstatus',
            'id_individuo',
            'status',
            'updated_at',
            'updated_by'
          ]);

          $dataFull['items'][] = $depot->getData();
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
      $object = new Depot($id);
      $dataFull = $object->toArray();
      self::closeTransaction();
      return $dataFull;
    }
  }
}

<?php
namespace App\Control\Terminal;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Terminal\VwTerminal;
use App\Model\Terminal\Terminal;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response)
    {   
        self::openTransaction();
        $object = (new VwTerminal)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $status) {
          $dataFull['items'][] = $status->getData();
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

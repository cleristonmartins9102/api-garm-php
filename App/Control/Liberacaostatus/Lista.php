<?php
namespace App\Control\Liberacaostatus;

use App\Mvc\Controller;
use App\Model\Liberacao\LiberacaoStatus;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response)
    {   
        self::openTransaction();
        $object = (new LiberacaoStatus)->all();
        $dataFull = array();
        foreach ($object as $key => $porto) {
          $liberacaoArr = $porto->getData();
          $dataFull[] = $liberacaoArr;
        }
        self::closeTransaction();
        return $dataFull ?? null ;
    }

    public function bynome(Request $request, Response $response, $nome = null)
    {
      self::openTransaction();
      $criteria = new Criteria();
      $criteria->add(new Filter('status', '=', $nome));
      $repository = new Repository('App\Model\Liberacao\LiberacaoStatus');
      $lib_status = $repository->load($criteria);
      self::closeTransaction();
      if (count($lib_status) > 0) {
        return $lib_status[0];
      }
    }
}

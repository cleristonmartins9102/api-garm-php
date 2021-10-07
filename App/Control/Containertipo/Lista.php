<?php
namespace App\Control\ContainerTipo;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Captacao\ContainerTipo;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller {
   
    public function all(Request $request, Response $response){
        try{
            self::openTransaction();
            $object = (new ContainerTipo)->all();
            $dataFull = array();
            $dataFull['total_count'] = count($object);
            $dataFull['items'] = array();
            foreach ($object as $key => $cidade) {
              $dataFull['items'] [] = $cidade->getData();
            }
            self::closeTransaction();
            return $dataFull;
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }
    }

    public function alldropdown(Request $request, Response $response){
        try{
            self::openTransaction();
            $object = (new ContainerTipo)->all();
            foreach ($object as $key => $container_tipo) {
              $dataFull[] = [ 'id_containertipo' => $container_tipo->id_containertipo, 'tipo' => $container_tipo->tipo ];
            }
            self::closeTransaction();
            return $dataFull;
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }
    } 
}
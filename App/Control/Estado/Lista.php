<?php
namespace App\Control\Estado;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Model\Pessoa\Endereco;
use App\Model\Pessoa\Estado as Estados;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller {
    private $data;
    public function all(Request $request, Response $response){
        try{
            self::openTransaction();
            $object = (new Estados)->all();
            $dataFull = array();
            $dataFull['total_count'] = count($object);
            $dataFull['items'] = array();
            foreach ($object as $estado) {
              $dataFull['items'][] = $estado->getData(); 
            }
            self::closeTransaction();
            //Converte o array para codigicacao utf8 e para json
            return $dataFull;
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }

    }
}
<?php
namespace App\Control\Cidade;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Pessoa\Endereco;
use App\Model\Pessoa\Cidade as Cidades;
use App\Model\Pessoa\PessoaJuridica;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller {
    private $data;
    private $ns_model = 'App\Model\Pessoa\\';
    public function all(Request $request, Response $response){
        try{
            Transaction::open('zoho');
            $object = (new Cidades)->all();
            $dataFull = array();
            $dataFull['total_count'] = count($object);
            $dataFull['items'] = array();
            foreach ($object as $key => $cidade) {
              $dataFull['items'] [] = $cidade->getData();
            }  
            return $dataFull;
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }

    }
    public function byid(Request $request, Response $response, $id_estado) {
        try{
            self::openTransaction();
            $criteria = new Criteria;
            $criteria->add(new Filter('id_estado', '=', $id_estado));
            $repository = new Repository($this->ns_model.'Cidade');
            $cidades = $repository->load($criteria);
            foreach ($cidades as $cidade) {
              $this->data[] = array('id_cidade' => $cidade->id_cidade,
                                    'id_estado' => $cidade->id_estado,
                                    'nome'      => $cidade->nome,
                                   );
            }
            self::closeTransaction();
            // //Converte o array para codigicacao utf8 e para json
            return json_encode($this->data, JSON_UNESCAPED_UNICODE);
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }

    }
}
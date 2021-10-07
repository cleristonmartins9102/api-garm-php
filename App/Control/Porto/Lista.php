<?php
namespace App\Control\Porto;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Model\Porto\Porto;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
  private $data;
  public function all(Request $reques, Response $response)
  {
    self::openTransaction();
    $object = (new Porto)->all();
    $dataFull = array();
    $dataFull['total_count'] = count($object);
    $dataFull['items'] = array();
    foreach ($object as $key => $porto) {
      $portoVal = $porto->getData();
      $portoVal['cidade'] = $porto->cidade->nome;
      $portoVal['estado'] = strtoupper($porto->cidade->estado->sigla);
      $dataFull['items'][] = $portoVal;
    }
    self::closeTransaction();
    return $dataFull;
  }
}
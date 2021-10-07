<?php

namespace App\Control\Depot;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Depot\Depot;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller {
    public function store(Request $request, Response $response, Array $data)
    { 
      self::openTransaction();
      $result = array();
      $result['message'] = null;
      $result['status'] = 'success';
      $data = (object) $data;
      $depot = new Depot($data->id_depot ?? null);
      $depot->id_individuo = $data->identificador ?? null;    
      $depot->id_depotstatus = $data->status ?? null;
      $depot->id_margem = $data->margem ?? null;       
      $depot->id_depot = $data->id_depot ?? null;       
      $depot->nome = $data->nome ?? null;
      $depot->store();
      self::closeTransaction();
      return json_encode($result); 
    }
}
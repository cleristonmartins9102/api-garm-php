<?php

namespace App\Control\Captacaostatus;

use App\Mvc\Controller;
use App\Model\Captacao\Status;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response)
    {   
        self::openTransaction();
        $object = (new Status)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $key => $porto) {
          $portoVal = $porto->getData();
          $dataFull['items'][] = $portoVal;
        }
        self::closeTransaction();
        return $dataFull;
    }
}

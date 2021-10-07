<?php
namespace App\Control\Terminalstatus;

use App\Mvc\Controller;
use App\Model\Terminal\Status;
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
        foreach ($object as $key => $status) {
          $dataFull['items'][] = $status->getData();
        }
        self::closeTransaction();
        return $dataFull;
    }
}

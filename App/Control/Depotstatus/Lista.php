<?php
namespace App\Control\Depotstatus;

use App\Mvc\Controller;
use App\Model\Depot\Status;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response)
    {   
        self::openTransaction();
        $status = (new Status)->all();
        $dataFull['total_count'] = count($status);
        $dataFull['items'] = array();
        foreach ($status as $status) {
          $dataFull['items'][] = $status->getData();
        }
        self::closeTransaction();
        return $dataFull ?? [];
    }

    public function alldropdown(Request $request, Response $response)
    {   
        self::openTransaction();
        $status = (new Status)->all();
        foreach ($status as $status) {
          $dataFull[] = $status->getData();
        }
        self::closeTransaction();
        return $dataFull ?? [];
    }
}

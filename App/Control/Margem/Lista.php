<?php

namespace App\Control\Margem;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Margem\Margem;

class Lista extends Controller
{
    public function alldropdown(Request $request, Response $response, Array $param=null) {
        self::openTransaction();
        $margens = (new Margem)->all();
        foreach ($margens as $margem) {
            $data[] = $margem->toArray();
        }
        self::closeTransaction();
        return $data;
    }
}

<?php
namespace App\Control\Comissionariotipo;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Comissionario\ComissionarioTipo;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{

    public function all(Request $request, Response $response)
    {
        self::openTransaction();
        $comissionario_tipos = (new ComissionarioTipo)->all();
        foreach ($comissionario_tipos as $key => $comissionario_tipo) {
            $dataFull[] = $comissionario_tipo->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }
}



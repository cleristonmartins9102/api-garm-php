<?php
namespace App\Control\Despachostatus;

use App\Mvc\Controller;
use App\Model\Despacho\DespachoStatus;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response) 
    {
        self::openTransaction();
        $despacho_status = new DespachoStatus;
        foreach ($despacho_status->all() as $key => $status) {
            $despachos_status[] = $status->toArray();
        }
        self::closeTransaction();
        return $despachos_status;
    }
}

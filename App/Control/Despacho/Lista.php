<?php
namespace App\Control\Despacho;

use App\Mvc\Controller;
use App\Model\Despacho\VwDespacho;
use App\Lib\Database\Repository;
use App\Model\Despacho\Despacho;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response, array $param)
    {
        self::openTransaction();
        $object = (new VwDespacho())->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
        $criteria = parent::criteria($param);
        $repository = new Repository('App\Model\Despacho\VwDespacho');
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$despacho) {
            $despacho->complementos = ['eventos' => $despacho->eventos]; 
            $dataFull['items'][] = $despacho->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }

    public function byid(Request $request, Response $response, $id = null) {
        if ($id) {
            self::openTransaction();
            $despacho = new Despacho($id);
            $complementos['containeres'] = [];
            foreach($despacho->container as $key=>$container) {
                $complementos['containeres'][] = $container->toArray();
            }
            $despacho->complementos = $complementos;
            self::closeTransaction();
            return json_encode($despacho->toArray() ?? []);
        }
    }

    public function alldropdown(Request $request, Response $response) {
        self::openTransaction();
        foreach ((new Despacho())->all() as $key => $despacho) {
            $desp = new \stdClass;
            $desp->id_despacho = $despacho->id_despacho;
            $desp->numero = $despacho->numero;
            $desp->due = $despacho->due;
            $despachos[] = $desp;           
        }
        self::closeTransaction();
        return $despachos ?? [];

    }
}

<?php

namespace App\Control\Captacaolote;

use App\Mvc\Controller;
use App\Model\Captacao\VwCaptacaoLote;
use App\Model\Captacao\CaptacaoLote;
use App\Model\Imposto\Imposto;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Lib\Database\Repository;

class Lista extends Controller
{
    public function all(Request $request, Response $response, array $param)
    {
        self::openTransaction();
        $criteria = parent::criteria($param);
        $repository = new Repository('App\Model\Captacao\VwCaptacaoLote');
        $lotes = $repository->load($criteria);
        $dataFull = array();
        $dataFull['total_count'] = count($lotes);
        $dataFull['items'] = array();
        foreach ($lotes as $key => $lote) {
            $dataFull['items'][] = $lote->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }

    public function byid(Request $request, Response $response, $id) {
        self::openTransaction();
        $lote = (new CaptacaoLote($id));
        $lote->captacao = $lote->listaCaptacaoArray;
        self::closeTransaction();
        return $lote->toArray();
    }

    public function alldropdown(Request $request, Response $response)
    {
        self::openTransaction();
        $object = (new CaptacaoLote)->all();
        foreach ($object as $key => $value) {
            $dataFull[] = $value->toArray();
        }
        self::closeTransaction();
        // usort($dataFull, function ($item1, $item2) {
        // return $item2['id_numero'] <=> $item1['id_numero'];
        // });
        return isset($dataFull) ? $dataFull : null;
    }


    private function getImposto($id_imposto): Imposto {
        return new Imposto($id_imposto);
    }

    public function filtered(Request $request, Response $response, Array $filter)
    {
        self::openTransaction();
        $dataFull = array();
        $dataFull['total_count'] = count((new VwCaptacaoLote)->all());
        $dataFull['items'] = array();
        $param['columns'] = (new VwCaptacaoLote())->getColTable();
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Captacao\VwCaptacaoLote');
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$lote) {
        // print_r($lote);
        // exit();
            $dataFull['items'][] = $lote->toArray();
        }
        self::closeTransaction();
        return json_encode($dataFull);
      
    }
}

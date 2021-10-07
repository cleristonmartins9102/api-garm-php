<?php
namespace App\Control\Regime;

use App\Mvc\Controller;
use App\Model\Regime\Regime;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response) {
        self::openTransaction();
        foreach ((new Regime)->all() as $key => $regime) {
            $regimes[] = $regime->toArray();
        }
        self::closeTransaction();
        return $regimes ?? [];
    }

    public function byid(Request $request, Response $response, $id = null) {
        if ($id) {
            self::openTransaction();
            $regime = new Regime($id);
            $regime->removeProperty('id_regime');
            self::closeTransaction();
            return $regime->toArray() ?? [];
        }
    }

    public function bynome(Request $request, Response $response, array $reg = null) {
        if (!is_null($reg)) {
            self::openTransaction();
            $regime = new Regime();
            $regime->regime = $reg['regime'];
            return $regime->searchByName()->toArray();
            self::closeTransaction();
        }
    }
}

<?php
namespace App\Control\Contato;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Contato;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{

    public function all(Request $request, Response $response)
    {
        self::openTransaction();
        $listaContato = (new Contato)->all();
        foreach ($listaContato as $key => $contato) {
            $dataFull[] = $contato->getData();
        }
        self::closeTransaction();
        return json_encode($dataFull);
    }

    public function byid(Request $request, Response $response, $id=null)
    {
        if (is_null($id))
            return 'Sem Id';
        $id = $id['id_contato'];
        self::openTransaction();
        $listaContato = new Contato;
        $listaContato->id_individuo = $id;
        if (isset($listaContato->contato) || count($listaContato->contato) > 0) {
            foreach ($listaContato->contato as $key => $contato) {
                $dataFull[] = $contato->getData();
            }
        }
        self::closeTransaction();
        return isset($dataFull) ? $dataFull : null;
    }
}



<?php
namespace App\Control\Documentotipo;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Documento\TipoDocumento;
use Exception;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function byutilidade(Request $request, Response $response, $utilidade = null)
    {   
        if ($utilidade) {
            self::openTransaction();
            $criteria = new Criteria;
            $criteria->add(new Filter('utilidade', '=', $utilidade));
            $repository = new Repository('App\Model\Documento\TipoDocumento');
            $tipo_documento_obj = $repository->load($criteria);
            foreach ($tipo_documento_obj as $key => $tipo_documento) {
                $tipo_documento_arr[] = $tipo_documento->toArray();
            }
            self::closeTransaction();
            return json_encode($tipo_documento_arr);
        }      
    }
}

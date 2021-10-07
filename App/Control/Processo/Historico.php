<?php
namespace App\Control\Processo;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Processo\ProcessoHistorico;
use App\Model\Proposta\VwProposta;
use App\Model\Pessoa\Individuo;
use stdClass;
use Slim\Http\Response;
use Slim\Http\Request;

class Historico extends Controller
{
    private $data;

    public function all(Request $request, Response $response, $id_processo=null)
    {
        self::openTransaction();  
        $id = 1;
        $criteria = new Criteria;
        $criteria->add(new Filter('id_processo', '=', $id_processo));
        $criteria->setProperty('order', 'created_at desc');
        $repository = new Repository(ProcessoHistorico::class);
        $object = $repository->load($criteria);
        $dataFull = array();
        $dataFull['items'] = array();
        foreach ($object as $idx => &$value) {
            $dataFull['items'][] = $value->getData();
        }
        self::closeTransaction();  
        return json_encode(isset($dataFull) ? $dataFull : null);
    }
}



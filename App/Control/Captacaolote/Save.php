<?php

namespace App\Control\Captacaolote;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\CaptacaoLote;
use App\Model\Captacao\CaptacaoLoteCaptacao;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller{
    public function store(Request $request, Response $response, Array $data) {
        $result = array();
        $result['message'] = null;
        $result['status'] = 'success';
        $data = (object) $data;
        self::openTransaction();
        $captacao_lote = new CaptacaoLote($data->id_captacaolote);

        if (isset($data->itens) and count($data->itens) > 0) {
            $captacao_lote->cleanCaptacoes();
            $criteria = new Criteria;
            $criteria->add(new Filter('id_captacaolote', '=', $captacao_lote->id ?? $captacao_lote->id_captacaolote));
            foreach($data->itens[0] as $captacao_num) {
                if (is_null($captacao_num)) {
                    $result['status'] = 'fail';
                    break;
                }
                $captacao = new Captacao($captacao_num['id_captacao']);
                $captacao_lote->addCaptacao($captacao);
                $criteria->add(new Filter('id_captacao', '<>',  $captacao_num['id_captacao']));
            }
            (new CaptacaoLoteCaptacao)->deleteByCriteria($criteria);
        } else {
            $result['status'] = 'fail';
        }
        self::closeTransaction();
        return json_encode($result);
    }
}
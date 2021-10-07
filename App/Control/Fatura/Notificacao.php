<?php
namespace App\Control\Fatura;

use App\Mvc\Controller;
use App\Model\Fatura\Fatura;
use App\Model\Captacao\Captacao;
use Slim\Http\Response;
use Slim\Http\Request;

class Notificacao extends Controller
{
    public function enviofatura(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {         
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_fatura = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $fatura = new Fatura($id_fatura);
                $fatura->request = $request;
                $fatura->request = $request;
                $resp = $fatura->tracking()->envFat($pk_email, $fatura);
                if ($resp['status'] === 'success') {
                    $fatura->addEvento('enviado_fatura', '', 'fatura');
                    // $captacao->addHistorico('Enviado Fatura');
                }
                self::closeTransaction();
                return $resp;
            }
        }
    }
}

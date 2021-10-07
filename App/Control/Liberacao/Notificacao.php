<?php
namespace App\Control\Liberacao;

use App\Mvc\Controller;
use App\Model\Captacao\Captacao;
use App\Model\Liberacao\Liberacao;
use Slim\Http\Response;
use Slim\Http\Request;

class Notificacao extends Controller
{
    public function soldidta(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
         
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_liberacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $liberacao = new Liberacao($id_liberacao);
                $liberacao->request = $request;
                $liberacao->request = $request;
                $resp = $liberacao->tracking()->solicitarDiDta($pk_email, $liberacao);
                if ($resp['status'] === 'success') {
                    $liberacao->addEvento('soldidta', '', 'liberacao');
                    $liberacao->addHistorico('Enviado Solicitação de DI/DTA');
                }
                self::closeTransaction();
                return $resp;
            }
        }
    }
}

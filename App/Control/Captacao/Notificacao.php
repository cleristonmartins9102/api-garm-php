<?php
namespace App\Control\Captacao;

use App\Mvc\Controller;
use App\Model\Captacao\Captacao;
use Slim\Http\Response;
use Slim\Http\Request;

class Notificacao extends Controller
{
    public function solicitarbl(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
         
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_captacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $captacao = new Captacao($id_captacao);
                $captacao->request = $request;
                $captacao->request = $request;
                $resp = $captacao->tracking()->solicitarbl($pk_email, $captacao);
                if ($resp['status'] === 'success') {
                    $captacao->addEvento('solicitado_bl', '', 'captacao');
                    $captacao->addHistorico('Enviado Solicitação de BL');
                }
                self::closeTransaction();
                return $resp;
            }
        }
    }

    public function solicitarce(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_captacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $captacao = new Captacao($id_captacao);
                $captacao->request = $request;
                $captacao->request = $request;
                $resp = $captacao->tracking()->solicitarCE($pk_email, $captacao);
                if ($resp['status'] === 'success') {
                    $captacao->addEvento('solicitado_ce', '', 'captacao');
                    $captacao->addHistorico('Enviado Solicitação de CE');
                }
                self::closeTransaction();           
                return  $resp;
            }
        }
    }

    public function confrecbl(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_captacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $captacao = new Captacao($id_captacao);
                $captacao->request = $request;
                $captacao->request = $request;
                $resp = $captacao->tracking()->confirmarRecBl($pk_email, $captacao);
                if ($resp['status'] === 'success') {
                    $captacao->addEvento('confrecbl', '', 'captacao');
                    $captacao->addHistorico('Enviado Confirmação de Recebimento de BL');
                }
                self::closeTransaction();
                return $resp;  
            }
        }
    }

    public function confatracacao(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
            // print_r($pk_email);
            // exit();
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_captacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $captacao = new Captacao($id_captacao);
                $captacao->request = $request;
                $captacao->request = $request;
                $resp = $captacao->tracking()->confirmaAtracacao($pk_email, $captacao);
                if ($resp['status'] === 'success') {
                    $captacao->addEvento('confatracacao', '', 'captacao');
                    $captacao->addHistorico('Enviado Confirmação de Atracação');
                }
                self::closeTransaction();
                return $resp;  
            }
        }
    }

    public function presencacarga(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_captacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $captacao = new Captacao($id_captacao);
                $captacao->request = $request;
                $captacao->request = $request;
                $resp = $captacao->tracking()->presencaCarga($pk_email, $captacao);
                if ($resp['status'] === 'success') {
                    $captacao->addEvento('presenca_carga', '', 'captacao');
                    $captacao->addHistorico('Enviado Presença de Carga');
                }
                self::closeTransaction();
                return $resp;  
            }
        }
    }


    public function confcliente(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_captacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $captacao = new Captacao($id_captacao);
                $captacao->request = $request;
                $captacao->request = $request;
                $resp = ($captacao->tracking()->confirmarCliente($pk_email, $captacao));
                if ($resp['status'] === 'success') {
                    $captacao->addEvento('confcliente', '', 'captacao');
                    $captacao->addHistorico('Enviado Confirmação de Cadastro No Terminal');
                }
                self::closeTransaction();
                return $resp;
            }
        }
    }


    public function altdtaatracacao(Request $request, Response $response, Array $pk_email=null)
    {
        if ($pk_email) {
            // $result = self::send($pk_email);
            $result['status'] = 'success';
            if ($result['status'] == 'success') {
                $id_captacao = isset($pk_email['data']['id_app']) ? $pk_email['data']['id_app'] : null;
                self::openTransaction();
                $captacao = new Captacao($id_captacao);
                $captacao->request = $request;
                $captacao->request = $request;
                $resp = ($captacao->tracking()->alteradoDtaAtracacao($pk_email, $captacao));
                if ($resp['status'] === 'success') {
                    $captacao->addEvento('altdtaatracacao', '', 'captacao');
                    $captacao->addHistorico('Enviado Alteração de Data de Atracação');
                }
                self::closeTransaction();
                return $resp;
                
            }
        }
    }
}

<?php

namespace App\Control\Despacho;

use App\Mvc\Controller;
use App\Model\Despacho\Despacho;
use App\Model\Proposta\Proposta;
use App\Model\Captacao\Container;
use App\Model\Despacho\DespachoContainer;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Lib\Tool\Register;

class Save extends Controller
{
    public function store(Request $request, Response $response, array $data)
    {
        $data = (object) $data;
        $result = array();
        $result['message'] = null;
        $result['status'] = 'success';
        self::openTransaction();
        $despacho = new Despacho($data->id_despacho ?? null);

        // Verificando se existe proposta
        if ($data->id_proposta) {
            $despacho->request = $request;
            $despacho->response = $response;
            $despacho->id_proposta = $data->id_proposta;
            $despacho->id_terminal_operacao = $data->id_terminal_operacao;
            $despacho->id_terminal_destino = $data->id_terminal_destino;
            $despacho->due = $data->due ?? null;
            $despacho->bl = $data->bl ?? null;
            $despacho->id_despachante = $data->id_despachante ?? null;
            $despacho->id_depot = $data->id_depot ?? null;
            $despacho->ref_interna = $data->ref_interna;
            $despacho->id_status = $data->id_status;
            // echo $data->id_margem !== 'null';exit();
            $despacho->id_margem = $data->id_margem !== 'null' ? $data->id_margem : NULL ;
            $despacho->updated_at = 'now()';

            // Criando um registro 
            $reg = new Register;
            $reg->add('id_status', 'status');
            $reg->add('id_proposta', 'proposta');
            $reg->add('id_despachante', 'despachante_nome');
            $reg->add('id_terminal_operacao', 'terminal_operacao_nome');
            $reg->add('id_terminal_destino', 'terminal_destino_nome');

            // Gravando captacao
            $resp_save_despacho = $despacho->store($request, $response, $reg);
            if ($data->id_despacho)
                self::historico($resp_save_despacho, $despacho);
        }

        self::listaContaineres($despacho, $data->container['containeres']);
        self::closeTransaction();
        return json_encode($result);
    }

    /**
     * Cadastrando ou recadastrando os conteineres do despacho
     * @param Despacho $despacho Despacho já instanciado
     * @param Array $containeres Lista de containeres a serem cadastrados
     */
    private function listaContaineres(Despacho $despacho = null, array $containeres = null)
    {
        if (!is_null($despacho) && !is_null($containeres)) {
            // Apagar todos os containeres do despacho
            $despacho->deleteContainer();
            $containeres = (object) $containeres;
            foreach ($containeres as $key => $container_val) {
                $container = new Container();
                $container_val = (object) $container_val;
                if ($container_val->codigo && $container_val->tipo_container != null) {
                    $container->codigo = $container_val->codigo;
                    $container->id_containertipo = $container_val->tipo_container;
                    $despacho->addContainer($container);
                }
            }
        }
    }
}

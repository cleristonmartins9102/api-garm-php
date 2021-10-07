<?php
namespace App\Model\Despacho;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Proposta\Proposta;
use App\Model\Captacao\Container;
use App\Model\Pessoa\Individuo;
use App\Model\Terminal\Terminal;
use App\Model\Depot\Depot;
use Domain\Despacho\OpDespacho;

class Despacho extends Record implements OpDespacho
{
    const TABLENAME = 'Despacho';

    public function __construct($id_despacho = null)
    {
        parent::__construct($id_despacho);
        if (!isset($this->id_despacho)) {
            $this->numero = $this->getLast() + 1;
        }
    }

    /**
     * Deleta os containeres do despacho, caso for passado um id, deleta apenas o container, caso contrário deleta todos os containeres 
     *@param Number $id_container ID do container a ser removido
     */
    public function deleteContainer($id_container = null)
    {
        $criteria = new Criteria;
        if (is_null($id_container)) {
            $criteria->add(new Filter('id_despacho', '=', $this->id_despacho ?? $this->id));
        } else {
            $criteria->add(new Filter('id_despacho', '=', $id_container));
        }
        $despacho = new DespachoContainer;
        $despacho->deleteByCriteria($criteria, new Container);
        // exit();
    }

    public function get_status(): DespachoStatus
    {
        return (new DespachoStatus($this->id_status))->status;
    }

    public function get_depot(): Depot {
        return new Depot($this->id_depot);
    }

    public function addContainer(Container $container = null)
    {  
        if (!is_null($container)) {
            $container->store();
            // Relacionando o recem cadastrado container com a captacao
            $despachoContainer = new DespachoContainer();
            $despachoContainer->id_container = $container->id;
            $despachoContainer->id_despacho = $this->id;
            $despachoContainer->store();
        }
    }

    public function get_despachante(): Individuo
    {
        return new Individuo($this->id_despachante);
    }

    public function get_despachante_nome(): string
    {
        return (new Individuo($this->id_despachante))->nome;
    }

    public function get_terminal_operacao_nome()
    {
        return (new Terminal($this->id_terminal_operacao))->nome;
    }

    public function get_terminal_destino_nome()
    {
        return (new Terminal($this->id_terminal_destino))->nome;
    }

    public function get_terminal(): Terminal
    {
        return new Terminal($this->id_terminal);
    }

    public function liberarFaturamento()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_despacho', '=', $this->id_captacao ?? $this->id));
        $criteria->add(new Filter('evento', '=', 'g_fatura'));
        $evento = new DespachoEvento;
        $evento->deleteByCriteria($criteria);
    }

    public function get_proposta()
    {
        return new Proposta($this->id_proposta);
    }

    public function addEvento($evento = null, $app_forward = null, $app = null): void
    {
        $despacho_evento = new DespachoEvento();
        $despacho_evento->id_despacho = $this->id_despacho;
        $despacho_evento->evento = $evento;
        $despacho_evento->store();
    }

    public function get_eventos()
    { 
        $criteria = new Criteria;
        $criteria->add(new Filter('id_despacho', '=', $this->id_despacho));
        $repository = new Repository('App\Model\Despacho\DespachoEvento');
        $object = $repository->load($criteria);
        foreach ($object as $key => $evento) {
            $this->eventos[] = $evento->toArray();
        }
        return $this->eventos ?? [];
    }

    public function get_container()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_despacho', '=', $this->id_despacho ?? $this->id));
        $repository = new Repository('App\Model\Despacho\DespachoContainer');
        $despacho_containeres = $repository->load($criteria);
        // Percorrendo o array com os objetos para pegar os objetos
        foreach ($despacho_containeres as $key => $conteiner_value) {
            $container = new Container($conteiner_value->id_container);
            $container->dimensao = $container->dimensao;
            $containeres[] = $container;
        }
        return $containeres ?? [];
    }

    public function get_listacontainer()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_despacho', '=', $this->id_despacho ?? $this->id));
        $repository = new Repository('App\Model\Despacho\DespachoContainer');
        $despacho_containeres = $repository->load($criteria);
        // Percorrendo o array com os objetos para pegar os objetos
        foreach ($despacho_containeres as $key => $conteiner_value) {
            $container = new Container($conteiner_value->id_container);
            $container->dimensao = $container->dimensao;
            $containeres[] = $container;
        }
        return $containeres ?? [];
    }

    public function get_qtdcontainer()
    {
        $containeres = self::get_container();
        $dimensao[20] = 0;
        $dimensao[40] = 0;
        // Percorrendo o array com os objetos containeres para verificar a dimensao
        foreach ($containeres as $key => $container) {
            // Verificando a dimensao
            if ($container->dimensao == 20) {
                $dimensao[20]++;
            } else {
                $dimensao[40]++;
            }
        }
        return $dimensao ?? null;
    }

    public function addHistorico($ocorrencia) {
        $historico = new DespachoHistorico;
        $historico->request = $this->request;
        $historico->response = $this->response;
        $historico->ocorrencia = $ocorrencia;
        $historico->id_despacho = $this->id_despacho  ?? $this->id;
        $historico->store();
    }
}

<?php

namespace App\Model\Captacao;

use App\Lib\Database\Record;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Individuo;
use App\Model\Proposta\Proposta;
use App\Model\Porto\Porto;
use App\Model\Terminal\Terminal;
use App\Model\Documento\Upload;
use App\Model\Documento\TipoDocumento;
use App\Model\Liberacao\Liberacao;
use App\Model\Pessoa\GrupoDeContato;
use App\Model\Processo\ProcessoPredicado;
use App\Model\Rule\ChargeRule;
use Domain\Captacao\OpCaptacao;

class Captacao extends Record 
{
    use BodyMail;
    use SubjectEmail;

    const TABLENAME = "Captacao";
    private $eventos = [];

    public function addContainer(Container $container = null)
    {
        if ($container) {
            $container->store();
            // Relacionando o recem cadastrado container com a captacao
            $captacaoContainer = new CaptacaoContainer;
            $captacaoContainer->id_container = $container->id;
            $captacaoContainer->id_captacao = $this->id;
            $captacaoContainer->store();
        }
    }

    public function addDocumento(Upload $upload = null)
    {
        if ($upload) {
            // Salvando o upload
            $upload->save();

            // Inserindo o documento na tabela CaptacaoUpload
            $captacaoUpload = new CaptacaoUpload;
            $captacaoUpload->id_captacao = $this->id;
            $captacaoUpload->id_upload = $upload->id_upload;
            $captacaoUpload->store();
        }
    }

    public function addBreakBulk(array $break_bulk)
    {
        $this->break_bulk = 'sim';
        $break_bulk = (object) $break_bulk;
        $break_b = new CaptacaoBreakBulk;
        $break_b('id_captacao', $this->id_captacao);
        $break_b->id_captacao = $this->id ?? $this->id_captacao;
        $break_b->peso = $break_bulk->pesoBruto;
        $break_b->metro_cubico = $break_bulk->metroCubico;
        $break_b->store();
    }

    public function get_break_bulk_info()
    {
        $this->break_bulk = 'sim';
        $break_b = new CaptacaoBreakBulk;
        $break_b('id_captacao', $this->id_captacao);
        return ($break_b->isLoaded() ? $break_b->toArray() : false);
    }

    public function get_historico($tipo = null)
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        if ($tipo)
            $criteria->add(new Filter('tipo', '=', $tipo));
        $criteria->setProperty('order', 'created_at desc');
        $object = (new Repository(CaptacaoHistorico::class))->load($criteria);
        $dataFull = array();
        foreach ($object as $idx => &$value) {
            $value->modulo = 'Captação';
            $dataFull[] = $value->getData();
        }
        return isset($dataFull) ? $dataFull : null;
    }

    /**
     * Metodo para buscar o extrato do terminal
     * @return Upload
     */
    public function get_extrato_terminal(): Upload
    {
        $tipo_documento = (new TipoDocumento)('nome', 'extrato terminal');
        if (!$tipo_documento->isLoaded())
            return 'Sem tipo de documento extrato de terminal definido';

        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $captacao_documentos = (new Repository(CaptacaoUpload::class))->load($criteria);

        if (count($captacao_documentos) === 0)
            return 'Captação sem documentos anexados';

        foreach ($captacao_documentos as $key => $documento) {
            if ($documento->upload->tipo_documento->id_tipodocumento === $tipo_documento->id_tipodocumento)
                return $documento->upload;
        }
        return new Upload;
    }

    /**
     * Metodo para pegar o body
     * @param String $body_name Nome do body a ser buscado "bodySolBL"
     */
    public function bodymail(string $body_name)
    {
        return $this->body($body_name, $this);
    }

    /**
     * Metodo para retornar todos os grupos de contato relacionado do despachante e o importador da proposta
     */
    public function get_grupodecontato()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_adstrito', '=', $this->proposta->id_cliente));
        $criteria->add(new Filter('id_coadjuvante', '=', $this->id_despachante));
        $grupos = (new Repository(GrupoDeContato::class))->load($criteria);
        $grus = [];
        foreach ($grupos as $key => $grupo) {
            $gr = new \StdClass;
            $gr->nome = $grupo->grupo_nome;
            $gr->contatos = $grupo->contato_to_array;
            $grus[] = $gr;
        }
        return $grus;
    }

    /**
     * Metodo para retornar todos os grupos de contato relacionado do despachante e o importador da proposta
     */
    public function get_allgrupodecontato()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_adstrito', '=', $this->proposta->id_cliente));
        $criteria->add(new Filter('id_coadjuvante', '=', $this->id_despachante));

        $cri = new Criteria;
        $cri->add(new Filter('id_adstrito', '=', $this->proposta->id_cliente));
        $cri->add(new Filter('id_coadjuvante', '=', $this->proposta->id_coadjuvante));
        $criteria->add($cri, $cri::OR_OPERATOR);
        $grupos = (new Repository(GrupoDeContato::class))->load($criteria);
        $grus = [];
        foreach ($grupos as $key => $grupo) {
            $gr = new \StdClass;
            $gr->nome = $grupo->grupo_nome;
            $gr->contatos = $grupo->contato_to_array;
            $grus[] = $gr;
        }
        return $grus;
    }

    /**
     * Metodo para buscar o iserv
     * @return Upload
     */
    public function get_iserv(): Upload
    {
        $tipo_documento = (new TipoDocumento)('nome', 'iserv');
        if (!$tipo_documento->isLoaded())
            return 'Sem tipo de documento ISERV definido';

        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $captacao_documentos = (new Repository(CaptacaoUpload::class))->load($criteria);
        if (count($captacao_documentos) === 0)
            return new Upload;

        foreach ($captacao_documentos as $key => $documento) {
            if ($documento->upload->tipo_documento->id_tipodocumento === $tipo_documento->id_tipodocumento)
                return $documento->upload;
        }
        return new Upload;
    }



    /**
     * Metodo para retornar a data prevista atracação anterior
     */
    public function get_previous_dta_prevista_atracacao()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $criteria->add(new Filter('ocorrencia', 'LIKE', "%Alterado data prevista atraca%"));
        $criteria->setProperty('order', 'created_at DESC');
        $criteria->setProperty('limit', 1);
        $repo = (new Repository(CaptacaoHistorico::class))->load($criteria);
        if (count($repo) > 0) {
            $texto = 'Alterado data prevista atracação de ';
            $dta = substr($repo[0]->ocorrencia, strlen($texto));
            $pos = strpos($dta, 'para');
            return substr($dta, 0, $pos);
        } else {
            return false;
        }
    }



    /**
     * Metodo para adicionar um novo evento na captação
     * @param string $evento
     * @param string $app_forward
     * @param string $app
     */
    public function addEvento($evento = null, $app_forward = null, $app = null): void
    {
        if (!is_null($evento) && !is_null($app_forward) && !is_null($app)) {
            $captacao_evento = new CaptacaoEvento;
            $captacao_evento->id_captacao = $this->id_captacao;
            $captacao_evento->id_fatura = isset($app->idBase) ? ($app->idBase === 'id_fatura' ? $app->id : null) : null;
            $captacao_evento->id_processo = isset($app->idBase) ? ($app->idBase === 'id_processo' ? $app->id : null) : null;
            $captacao_evento->id_liberacao = isset($app->idBase) ? ($app->idBase === 'id_liberacao' ? $app->id : null) : null;
            $captacao_evento->id_forward = $app_forward;
            $captacao_evento->id_forward = $app_forward;
            $captacao_evento->evento = $evento;
            $captacao_evento->store();
        }
    }

    public function get_eventos()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = new Repository('App\Model\Captacao\CaptacaoEvento');
        $object = $repository->load($criteria);
        foreach ($object as $key => $evento) {
            $this->eventos[] = $evento->toArray();
        }
        return $this->eventos;
    }


    public function liberarFaturamento()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao ?? $this->id));
        $criteria->add(new Filter('evento', '=', 'g_fatura'));
        $evento = (new CaptacaoEvento);
        $evento->deleteByCriteria($criteria);
    }

    /**
     * Metodo que verifica se a Captação já foi confirmado o cadastro no parceiro
     * @return Array Repositorio com o historico
     */
    public function checkIfEnviadoAoTerminal()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao ?? $this->id));
        $criteria->add(new Filter('ocorrencia', 'LIKE', "%Enviado ao Terminal%"));
        return (new Repository(CaptacaoHistorico::class))->load($criteria);
    }

    /**
     * Metodo que verifica se a Captação já foi confirmado o cadastro no parceiro
     * @return Array Repositorio com o historico
     */
    public function checkIfConfirmado(): array
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao ?? $this->id));
        $criteria->add(new Filter('ocorrencia', 'LIKE', "%Confirmado%"));
        return (new Repository(CaptacaoHistorico::class))->load($criteria);
    }

    // select * from CaptacaoEvento where evento LIKE '%conf%'

    public function get_proposta()
    {
        return new Proposta($this->id_proposta);
    }

    public function get_despachante(): Individuo
    {
        return new Individuo($this->id_despachante);
    }

    public function get_cliente_cnpj()
    {
        return $this->proposta
            ->cliente->identificador;
    }

    public function get_despachante_nome(): string
    {
        return (new Individuo($this->id_despachante))->nome;
    }

    public function get_transportadora_nome(): Individuo
    {
        return (new Individuo($this->id_transportadora))->nome;
    }

    public function get_porto()
    {
        return new Porto($this->id_porto);
    }

    public function get_status(): string
    {
        return (new Status($this->id_status))->status;
    }

    public function get_terminal(): Terminal
    {
        return new Terminal($this->id_terminal_atracacao);
    }

    public function get_terminal_nome(): string
    {
        return (new Terminal($this->id_terminal_atracacao))->nome ?? '';
    }

    public function get_terminal_redestinacao(): Terminal
    {
        return new Terminal($this->id_terminal_redestinacao);
    }

    public function get_terminal_redestinacao_nome(): string
    {
        return (new Terminal($this->id_terminal_redestinacao))->individuo->nome;
    }

    public function get_notificacao()
    {
        $notificacoes = [];
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = new Repository('App\Model\Captacao\CaptacaoTracking');
        $notificacoesObj = $repository->load($criteria);
        foreach ($notificacoesObj as $key => $notificacao) {
            $notificacoes[] = $notificacao->toArray();
        }
        return $notificacoes;
    }

    public function tracking(): CaptacaoTracking
    {
        $captacao_tracking = new CaptacaoTracking;
        $captacao_tracking->id_captacao = $this->id_captacao;
        return $captacao_tracking;
    }

    public function get_container()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = new Repository('App\Model\Captacao\CaptacaoContainer');
        $object = $repository->load($criteria);
        $containeres = array();
        // Verificando se encontrou containeres
        if (count($object) > 0) {
            // Percorrendo o array com os objetos para pegar os objetos
            foreach ($object as $key => $conteiner_value) {
                $container = new Container($conteiner_value->id_container);
                $container->dimensao = $container->dimensao;
                $container->tipo = $container->tipo;
                $containeres[] = $container;
            }
        }
        return $containeres;
    }

    /**
     * Metodo para buscar todos os containeres de 20
     * @return String Lista de containeres concatenadas
     */
    public function get_container20(): string
    {
        $containeres = $this->container;
        $cont = '';
        foreach ($containeres as $idx => $container) {
            if ($container->dimensao === '20') {
                if ($idx > 0)
                    $cont .= "/";
                $cont .= $container->codigo;
            }
        }
        return $cont;
    }

    /**
     * Metodo para buscar todos os containeres de 40
     * @return String Lista de containeres concatenadas
     */
    public function get_container40(): string
    {
        $containeres = $this->container;
        $cont = '';
        foreach ($containeres as $idx => $container) {
            if ($container->dimensao === '40') {
                if ($idx > 0)
                    $cont .= "/";
                $cont .= $container->codigo;
            }
        }
        return $cont;
    }



    public function get_listacontainer()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = new Repository('App\Model\Captacao\CaptacaoContainer');
        $object = $repository->load($criteria);
        $containeres = array();
        // Verificando se encontrou containeres
        if (count($object) > 0) {
            // Percorrendo o array com os objetos para pegar os objetos
            foreach ($object as $key => $conteiner_value) {
                $container = new Container($conteiner_value->id_container);
                $container->dimensao = $container->dimensao;
                $container->tipo = $container->tipo;
                $containeres[] = $container->toArray();
            }
        }
        return $containeres;
    }

    public function get_qtdcontainer()
    {
        $containeres = $this->get_container();
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

    public function get_documento()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = new Repository('App\Model\Captacao\CaptacaoUpload');
        $object = $repository->load($criteria);
        $documentos = array();

        // Verificando se encontrou documentos
        if (count($object) > 0) {
            $documentos = array();
            // Percorrendo o array com os objetos para pegar os objetos
            foreach ($object as $key => $captacao_upload_value) {
                $id_upload = $captacao_upload_value->id_upload;
                $upload = new Upload($id_upload);
                $upload->id_tipodocumento = $upload->tipo_documento->id_tipodocumento;
                $upload->tipodocumento = $upload->tipo_documento->nome;
                $upload->removeProperty(['id_bucket', 'nome_sistema', 'localizacao', 'validado']);
                $documentos[] = $upload->toArray();
            }
        }
        return $documentos;
    }

    public function addHistorico($ocorrencia)
    {
        $historico = new CaptacaoHistorico;
        $historico->request = $this->request;
        $historico->response = $this->response;
        $historico->ocorrencia = $ocorrencia;
        $historico->id_captacao = $this->id_captacao  ?? $this->id;
        $historico->store();
    }

    public function checkIsDDC(): bool
    {
        if (!is_null($this->liberacao->id_liberacao) && $this->liberacao->tipo_operacao === 'DDC') {
            return true;
        } else {
            return false;
        }
    }

    public function get_liberacao()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = (new Repository(Liberacao::class))->load($criteria);
        if (count($repository) > 0)
            return $repository[0];
        return [];
    }

    /**
     * Metodo para buscar os itens do processo para a captação
     */
    public function get_itensProcessoArray()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $itens_processo = (new Repository(ProcessoPredicado::class))->load($criteria);
        $itens = [];
        foreach ($itens_processo as $item) {
            $itens[] = $item->toArray();
        }
        return $itens;
    }

    /**
     * Metodo que verifica se a captação pertence a um lote
     * @return int Retorna o id do lote
     */
    public function isInLote()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = (new Repository(CaptacaoLoteCaptacao::class))->load($criteria);
        if (count($repository) === 0)
            return false;

        // Retorna o número do lote
        $lote = new CaptacaoLote($repository[0]->id_captacaolote);
        return $lote;
    }
}

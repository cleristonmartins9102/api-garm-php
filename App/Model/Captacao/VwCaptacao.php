<?php
namespace App\Model\Captacao;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Individuo;
use App\Model\Proposta\Proposta;
use App\Model\Porto\Porto;
use App\Model\Terminal\Terminal;
use App\Model\Documento\Upload;
use App\Model\Documento\TipoDocumento;
use App\Model\SafeData;


class VwCaptacao extends Record implements SafeData
{
    const TABLENAME = "VwCaptacao";
    private $eventos = [];

    public function isItLockedEdit() {
        $locked = false;
        $eventos = self::get_eventos();
        foreach($eventos as $evento) {
            if ( $evento['evento'] == 'g_liberacao' )
                $locked = true;
        }
        return $locked;
    }

    public function addContainer(Container $container=null) 
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

    public function addDocumento(Upload $upload=null) 
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

    public function addBreakBulk(array $break_bulk) {
        $break_bulk = (object) $break_bulk;
        $break_b = new CaptacaoBreakBulk;
        $break_b->id_captacao = $this->id ?? $this->id_captacao;
        $break_b->peso = $break_bulk->pesoBruto;
        $break_b->metro_cubico = $break_bulk->metroCubico;
        $break_b->store();
    }

    public function get_break_bulk_info() {
        $this->break_bulk = 'sim';
        $break_b = new CaptacaoBreakBulk;
        $break_b('id_captacao', $this->id_captacao);
        return ($break_b->isLoaded() ? $break_b->toArray() : false);
    }

        /**
     * Metodo para retornar a data prevista atracação anterior
     */
    public function get_previous_dta_prevista_atracacao() {
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
     * Metodo para buscar o extrato do terminal
     * @return Upload
     */
    public function get_extrato_terminal(): Upload {
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

    public function get_eventos()
    {
        $this->eventos = [];
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = new Repository('App\Model\Captacao\CaptacaoEvento');
        $object = $repository->load($criteria);
        foreach ($object as $key => $evento) {
            $this->eventos[] = $evento->toArray();
        }
        return $this->eventos;
    }

    public function get_proposta() 
    {
        return new Proposta($this->id_proposta);
    }

    public function get_despachante() 
    {
        return new Individuo($this->id_despachante);
    }

    public function get_porto() 
    {
        return new Porto($this->id_porto);
    }

    public function get_status() 
    {
        return new Status($this->id_status);
    }

    public function get_terminal_atracacao() 
    {
        return new Terminal($this->id_terminal_atracacao);
    }
    public function get_terminal_redestinacao() 
    {
        return new Terminal($this->id_terminal_redestinacao);
    }

    public function get_tracking()
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


    public function send_notificacao()
    {
        $captacao_notificacao = new CaptacaoTracking;
        $captacao_notificacao->id_captacao = '22222';
        $captacao_notificacao->solicitarbl();
    }

    /**
     * Metodo para buscar os trakings
     * @return array CaptacaoTrackings
     */
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
                $containeres [] = $container->toArray();
            }
            
        }
        return $containeres;
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
                $documentos[] = $upload->toArray();
            }
            
        }
        return $documentos;
    }

    public function checkIfGerLiberacao() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao), $criteria::AND_OPERATOR);
        $criteria->add(new Filter('evento', '=', 'g_liberacao'));
        $eventos = (new Repository(CaptacaoEvento::class))->load($criteria);
        if (count($eventos) > 0) 
            return true;
        return false;
    }

    public function addHistorico($ocorrencia)
    {
        $historico = new CaptacaoHistorico;
        $historico->ocorrencia = $ocorrencia;
        $historico->id_captacao = $this->id_captacao;
        $historico->store();
    }
}

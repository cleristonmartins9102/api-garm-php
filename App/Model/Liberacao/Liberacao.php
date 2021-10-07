<?php
namespace App\Model\Liberacao;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Processo\Processo;
use App\Model\Captacao\Captacao;
use App\Model\Liberacao\LiberacaoDocumento;
use App\Model\Documento\Upload;
use App\Model\Shared\Guard;

class Liberacao extends Guard
{
    use BodyMail;
    use SubjectEmail;
    
    const TABLENAME = "Liberacao";

    public function get_status()
    {
        return (new LiberacaoStatus($this->id_liberacaostatus))->status;
    }

    public function get_captacao()
    {
        return new Captacao($this->id_captacao);
    }

    public function get_tipo_documento() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_liberacao', '=', $this->id_liberacao));
        $lib = (new Repository(LiberacaoDocumento::class))->load($criteria);
        if (count($lib) > 0) {
            return !is_null($lib[0]->id_docdi) ? 'di' : (!is_null($lib[0]->id_docdta) ? 'dta' : 'não informado');          
        } else {
            return 'não informado';
        }
    }

    public function get_historico($tipo = null) {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_liberacao', '=', $this->id_liberacao));
        if ( $tipo )
            $criteria->add(new Filter('tipo', '=', $tipo));
        $criteria->setProperty('order', 'created_at desc');
        $repository = new Repository('App\Model\Liberacao\LiberacaoHistorico');
        $object = $repository->load($criteria);
        $dataFull = array();
        foreach ($object as $idx => &$value) {
            $value->modulo = 'Liberação';
            $dataFull[] = $value->getData();
        }
        return isset($dataFull) ? $dataFull : null;
    }

     /**
     * Metodo para adicionar um novo evento na liberacao
     * @param string $evento
     * @param string $app_forward
     * @param string $app
     */
    public function addEvento($evento = null, $app_forward = null, $app = null): void
    {
        if (!is_null($evento) && !is_null($app_forward) && !is_null($app)) {
            $liberacao_evento = new LiberacaoEvento;
            $liberacao_evento->id_liberacao = $this->id_liberacao;
            $liberacao_evento->id_fatura = isset($app->idBase) ? ($app->idBase === 'id_fatura' ? $app->id : null) : null;
            $liberacao_evento->id_processo = isset($app->idBase) ? ($app->idBase === 'id_processo' ? $app->id : null) : null;
            $liberacao_evento->id_forward = $app_forward;
            $liberacao_evento->id_forward = $app_forward;
            $liberacao_evento->evento = $evento;
            $liberacao_evento->store();
        }
    }

    public function get_eventos()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_liberacao', '=', $this->id_captacao));
        $repository = new Repository('App\Model\Liberacao\LiberacaoEvento');
        $object = $repository->load($criteria);
        foreach ($object as $key => $evento) {
            $this->eventos[] = $evento->toArray();
        }
        return $this->eventos ?? [];
    }

    public function deleteDocumentos() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_liberacao', '=', $this->id_liberacao));
        $lib = (new LiberacaoDocumento)->deleteByCriteria($criteria);
    }

    public function addHistorico($ocorrencia)
    {
        $historico = new LiberacaoHistorico();
        $historico->ocorrencia = $ocorrencia;
        $historico->request = $this->request;
        $historico->response = $this->response;
        $historico->id_liberacao = $this->id_liberacao ?? $this->id;
        $historico->store();
    }

    public function getProcesso() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = (new Repository(Processo::class))->load($criteria);
        return count($repository) > 0 ? (new Processo($repository[0]->id_processo)) : [];
    }

    public function hasProcesso() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacao', '=', $this->id_captacao));
        $repository = (new Repository(Processo::class))->load($criteria);
        return count($repository) === 0 ? false : true;
    }

        /**
     * Metodo para pegar o body
     * @param String $body_name Nome do body a ser buscado "bodySolDiDta"
     */
    public function bodymail(string $body_name) {
        return $this->body($body_name, $this);
    }

    public function tracking()
    {
        $liberacao_tracking = new LiberacaoTracking;
        $liberacao_tracking->id_liberacao = $this->id_liberacao;
        return $liberacao_tracking;
    }

}

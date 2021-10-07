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
use App\Control\Sendemail\Boxmail;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\SubjectEmail;
use App\Model\Aplicacao\AplicacaoModulo;
use App\Model\Modulo\Modulo;
use App\Model\Shared\EmailCredencial;

class CaptacaoTracking extends Record
{
    use BoxMail;
    use BodyMail;
    use SubjectEmail;

    const TABLENAME = "CaptacaoTracking";

    public function set_id_captacao($id_captacao = null)
    {
        $this->id_captacao = $id_captacao;
    }

    /**
     * Metodo para enviar email do tipo solicitacao de BL
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function solicitarBL(array $pk_email, Captacao $captacao)
    {
        $this->evento = 'solicitado_bl';
        $tbody = $pk_email['body'];
        $mail_body = self::bodySolBl($captacao, true);
        if (!$mail_body === true)
            return $mail_body;
        
        $mail_body = str_replace('#TBODY#', $tbody, $mail_body);
        return $this->send($pk_email['to'], self::subSolicitarBl($captacao), $mail_body, $this->getCredencial());
    }

    /**
     * Metodo para enviar email do tipo solicitacao de CE
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function solicitarCE(array $pk_email, Captacao $captacao)
    {
        $this->evento = 'solicitado_ce';
        $tbody = $pk_email['body'];
        $mail_body = self::bodySolCE($captacao, true);
        if (!$mail_body === true)
            return $mail_body;
        
        $mail_body = str_replace('#TBODY#', $tbody, $mail_body);
        return $this->send($pk_email['to'], self::subSolicitarCE($captacao), $mail_body, $this->getCredencial());
    }


    /**
     * Metodo para enviar email do tipo solicitacao de CE
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function confirmarCliente(array $pk_email, Captacao $captacao)
    {
        $this->evento = 'confirmado_cliente';
        $tbody = $pk_email['body'];
        $mail_body = self::bodyConfirmarCliente($captacao, true);
        if (!$mail_body === true)
            return $mail_body;

        $mail_body = str_replace('#TBODY#', $tbody, $mail_body);
        return $this->send($pk_email['to'], self::subConfCliente($captacao), $mail_body, $this->getCredencial());
    }

    /**
     * Metodo para enviar email do tipo solicitacao de CE
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function confirmarRecBl(array $pk_email, Captacao $captacao)
    {
        $this->evento = 'confirmado_recebimento_bl';
        $tbody = $pk_email['body'];
        $mail_body = self::bodyConfRecBL($captacao, true);
        if (!$mail_body === true)
            return $mail_body;

        $mail_body = str_replace('#TBODY#', $tbody, $mail_body);
        return $this->send($pk_email['to'], self::subConfRecBL($captacao), $mail_body, $this->getCredencial());
    }

    /**
     * Metodo para enviar email do tipo solicitacao de CEr
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function alteradoDtaAtracacao(array $pk_email, Captacao $captacao)
    {
        $this->evento = 'alterado_dta_atracacao';
        $tbody = $pk_email['body'];
        $mail_body = self::bodyAlteradoDtaAtracacao($captacao, true);
        if (!$mail_body === true)
            return $mail_body;
        
        $mail_body = str_replace('#TBODY#', $tbody, $mail_body);
        return $this->send($pk_email['to'], self::subAltDtaAtracacao($captacao), $mail_body, $this->getCredencial());
    }

    /**
     * Metodo para enviar email do tipo solicitacao de CE
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function confirmaAtracacao(array $pk_email, Captacao $captacao)
    {

        $this->evento = 'confirmado_atracacao';
        $tbody = $pk_email['body'];
        $mail_body = self::bodyConfirmarAtracacao($captacao, $pk_email, true);
        if (!$mail_body === true)
            return $mail_body;

        $mail_body = str_replace('#TBODY#', $tbody, $mail_body);
        return $this->send($pk_email['to'], self::subConfAtracacao($captacao), $mail_body, $this->getCredencial());
    }

    /**
     * Metodo para enviar email de presença de carga
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function presencaCarga(array $pk_email, Captacao $captacao)
    {
        $this->evento = 'presenca_carga';
        $tbody = $pk_email['body'];
        $this->id_captacao = $captacao->id_captacao;

        $mail_body = self::bodyPresencaCarga($captacao, true);

        if (!$mail_body === true)
            return $mail_body;

        $mail_body = str_replace('#TBODY#', $tbody, $mail_body);
        return $this->send($pk_email['to'], self::subPresencaDeCarga($captacao), $mail_body, $this->getCredencial());
    }

    /**
     * Metodo para buscar as credenciais para envio de email
     */
    private function getCredencial()
    {
        $modulo = (new Modulo)('nome', 'captacao');
        if ($modulo->isLoaded()) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_modulo', '=', $modulo->id_modulo));
            $app_modulo = (new Repository(AplicacaoModulo::class))->load($criteria);
            if (count($app_modulo) > 0)
                return $app_modulo[0]->credencial->isLoaded() ? $app_modulo[0]->credencial : (new EmailCredencial);
        }
    }
}

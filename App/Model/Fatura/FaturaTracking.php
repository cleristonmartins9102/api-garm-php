<?php
namespace App\Model\Fatura;

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
use App\Model\Fatura\Captacao;
use App\Model\Fatura\SubjectEmail;
use App\Model\Aplicacao\AplicacaoModulo;
use App\Model\Modulo\Modulo;
use App\Model\Shared\EmailCredencial;

class FaturaTracking extends Record
{
    use BoxMail;
    use BodyMail;
    use SubjectEmail;

    const TABLENAME = "FaturaTracking";
    
    public function set_id_fatura($id_fatura=null)
    {
        $this->id_fatura = $id_fatura;
    }
    
    /**
     * Metodo para enviar email do tipo solicitacao de BL
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function envFat(array $pk_email, Fatura $fatura)
    {   
        $this->evento = 'enviado_fatura';
        $mail_body = self::enviarFatura($fatura);
        if (!$mail_body === true)
            return $mail_body;
        
        // return $this->send($pk_email['to'], self::subEnvFatura($fatura), $mail_body, $this->getCredencial());
        return $this->send($pk_email['to'], self::subEnvFatura($fatura), $mail_body, $this->getCredencial());
    }

    /**
     * Metodo para buscar as credenciais para envio de email
     */
    private function getCredencial() {
        $modulo = (new Modulo)('nome', 'fatura');
        if ($modulo->isLoaded()) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_modulo', '=', $modulo->id_modulo));
            $app_modulo = (new Repository(AplicacaoModulo::class))->load($criteria);
            if (count($app_modulo) > 0) 
                return $app_modulo[0]->credencial->isLoaded() ? $app_modulo[0]->credencial : (new EmailCredencial);
            
        }
    }
}

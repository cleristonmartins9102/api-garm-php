<?php
namespace App\Model\Liberacao;

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
use App\Model\Aplicacao\AplicacaoModulo;
use App\Model\Modulo\Modulo;
use App\Model\Shared\EmailCredencial;


class LiberacaoTracking extends Record
{
    use BoxMail;
    use BodyMail;
    use SubjectEmail;

    const TABLENAME = "LiberacaoTracking";
    
    public function set_id_liberacao($id_liberacao=null)
    {
        $this->id_liberacao = $id_liberacao;
    }
    
    /**
     * Metodo para enviar email do tipo solicitacao de BL
     * @param $mail Todo o conteudo do email, destinatario, assunto, corpo, etc.
     */
    public function solicitarDiDta(array $pk_email, Liberacao $liberacao)
    {   
        $this->evento = 'solicitado_di_dta';
        $mail_body = self::bodySolDiDta($liberacao);
        if (!$mail_body === true)
            return $mail_body;
                
        return $this->send($pk_email['to'], $this->subSolDiDta($liberacao), $mail_body, $this->getCredencial());
    }

        /**
     * Metodo para buscar as credenciais para envio de email
     */
    private function getCredencial() {
        $modulo = (new Modulo)('nome', 'liberacao');
        if ($modulo->isLoaded()) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_modulo', '=', $modulo->id_modulo));
            $app_modulo = (new Repository(AplicacaoModulo::class))->load($criteria);
            if (count($app_modulo) > 0) 
                return $app_modulo[0]->credencial->isLoaded() ? $app_modulo[0]->credencial : (new EmailCredencial);
            
        }
    }
}

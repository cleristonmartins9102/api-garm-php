<?php
namespace App\Control\Sendemail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Model\Shared\EmailCredencial;

trait BoxMail {
    /**
     * Metodo para enviar o email
     * @param Array | string $to contatos destinatários 
     * @param string $subject Assunto
     * @param string $body Corpo
     * @param EmailCredencial $credencial Credenciais da conta de email da aplicacao
     */
    public function send($to, $subject, $body, EmailCredencial $credencial)
    {  
            try {
                if (!$credencial->isLoaded()) {
                    $result['status'] = false;
                    return $result['message'] = 'Credencial não encontrada';
                }

                if (is_array($to)) {
                    $email = null;
                    $arr = [];
                    foreach ($to as $contato) {
                        $contato = str_replace('[', '', $contato);
                        $contato = str_replace(']', '', $contato);
                        $contato = str_replace('"', '', $contato);
                        $arr = array_merge($arr, explode(',', $contato));
                    }
                }
                $to = $arr;
                    
                $result = array();
                $result['message'] = null;
                $result['status'] = 'success';
                $mail = new PHPMailer(true);
                foreach ($to as $i => $dest) {
                    if (is_string($dest))
                        $mail->addAddress($dest);
                }
                //Server settings
                $mail->SMTPDebug = 2;  
                $mail->CharSet = 'UTF-8';
                              // Enable verbose debug output
                $mail->isSMTP();                                      // Set mailer to use SMTP
                $mail->Host = 'SMTP.office365.com';  // Specify main and backup SMTP servers
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->Username = $credencial->email;                 // SMTP username
                $mail->Password = $credencial->senha;                           // SMTP password
                $mail->SMTPSecure = '';                            // Enable TLS encryption, `ssl` also accepted
                $mail->Port = 587;                                    // TCP port to connect to
                $mail->SMTPDebug = 0;
                //Recipients
                $mail->setFrom($credencial->email, 'Mailer');
                // print_r($mail);exit();

                // $mail->addAddress('ellen@example.com');               // Name is optional
                // $mail->addReplyTo('info@example.com', 'Information');
                // $mail->addCC('cc@example.com');
                // $mail->addBCC('bcc@example.com');
            
                //Attachments
                // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
                // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            
                //Content
                $mail->isHTML(true);                                  // Set email format to HTML
                $mail->Subject = $subject;
                $mail->MsgHTML($body);
                $mail->send();
                $result['message'] = 'Message has been sent';
                return $result;
            } catch (Exception $e) {
                $result['status'] = false;
                $result['message'] = $mail->ErrorInfo;
                return $result;
            }
    }
}


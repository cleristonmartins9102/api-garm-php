<?php

namespace App\Model\Liberacao;

use App\Model\Liberacao\Liberacao;

/**
 * Trait para gerenciamento dos corpos dos emails para captacao 
 */
trait BodyMail
{
  
          /**
     * Body para solicitação de DI DTA
     * @param Liberacao $liberacao Liberação ao qual vai ser solicitado a DI/DTA
     */
    public function bodySolDiDta(Liberacao $liberacao) {
        // if (!self::validate($liberacao) === true and $captacao->nome_navio)
        //     return self::validate($captacao);

        // $libe->dta_atracacao =  date('d/m/Y', strtotime($captacao->dta_atracacao));
        $body = "<table class='m_130013103931174352content' align='center' cellpadding='0' cellspacing='0' border='0'
        style='width:600px'>
        <tbody>
            <tr>
                <td>
                    <table cellpadding='0' cellspacing='0' border='0'
                        style='border-collapse:collapse;min-width:100%;width:100%'>
    
                        <tbody>
                            <tr>
                                <td>
                                    <span class='m_130013103931174352preheader'> </span> </td> </tr> <tr>
                                <td>
                                    <table cellpadding='0' cellspacing='0' border='0' height='92' width='100%'
                                        style='min-width:100%;width:100%'>
                                        <tbody>
                                            <tr>
                                                <td style='background:rgb(11,90,128)'
                                                    valign='top' style='border-radius:6px 6px 0 0'>
                                                    <table align='center' cellpadding='0' cellspacing='0' border='0'
                                                        style='border-collapse:collapse;min-width:100%;width:100%'>
                                                        <tbody>
                                                            <tr>
                                                                <th style='padding:28px 0;text-align:center'>
                                                                    <center style='width:100%'>
                                                                        <a style='display:inline-block;text-decoration:none;outline:none'
                                                                            href='https://www.Company_Name.com.br'
                                                                            target='_blank'
                                                                            data-saferedirecturl='https://www.google.com/url?q=https://www.Company_Name.com.br'>
                                                                            <img border='0'
                                                                                style='float:none;text-align:center;outline:none;text-decoration:none;clear:both;display:block'
                                                                                src='http://Company_Name-img.s3.amazonaws.com/logo_b.png'
                                                                                alt='Company_Name' width='133' height='42'
                                                                                align='middle' class='CToWUd'>
                                                                        </a>
                                                                    </center>
                                                                </th>
                                                                <th style='width:0;padding:0!important'></th>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td bgcolor='#F3F2F5' style='padding:18px'>
                    <table style='width:100%' bgcolor='' border='0' cellpadding='0' cellspacing='0'>
                        <tbody>
                            <tr>
                                <td style='font-size:13px'>
                                    <table style='width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        <tbody>
                                            <tr>
                                                <td
                                                    style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Prezados
                                                    <br><br>
                                                    <span style='color:#eb3b5a'>Despachante / Importador,</span>
                                                    <br>
                                                    Por gentileza encaminhar DI/ DTA averbada para o email:<a href='mail:liberacao@Company_Name.com.br'>liberacao@Company_Name.com.br</a>.
                                                    <br><br>
                                                    Nos mantemos à disposição.
                                                    <br><br>
                                                    Atenciosamente
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
    
                        </tbody>
                    </table>
    
                </td>
            </tr>
            <tr>
                <td bgcolor='#F3F2F5'>
                    <hr noshade='' size='1' height='1px'
                        style='height:1px;background-color:#ffffff;width:75%;border-top:1px solid #ffffff;border-left:none;border-right:none;border-bottom:none'>
                    <table cellpadding='0' cellspacing='0' border='0'
                        style='background-color:#f3f2f5;border-collapse:collapse;width:100%'>
    
                        <tbody>
                            <tr>
                                <td style='padding-top:18px'>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style='font-family:Arial,sans-serif;font-size:12px;line-height:18px;text-align:center;padding:0 6% 24px 6%;color:#b6b1bd;text-align:center;font-family:Arial,Helvetica,sans-serif;text-decoration:none'>
                                    <span class='m_130013103931174352appleLinksGrey'>
                                        Company_Name Logística Ltda, Av. Cassiano Ricardo, 601, sala 61 e 63, Pq. Res. Aquarius,
                                        São José dos Campos – SP <br>
                                    </span>
                                </td>
                            </tr>
            </tr>
        </tbody>
    </table>
    </td>
    </tr>
    <tr>
        <td>
            <div style='display:none;white-space:nowrap;font:15px courier;line-height:0'>
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
            </div>
        </td>
    </tr>
    </tbody>
    </table>";
      return $body;
    }

    /**
     * Metodo para retornar o body 
     * @param string $body_name Nome do metodo do body a ser buscado
     * @param Captacao $captacao Captação a ser contruida o body
     */
    private function body(string $body_name, Liberacao $liberacao) {
        if (method_exists($this, $body_name)) 
            return \call_user_func(array($this, $body_name), $liberacao);

        return "Body {$body_name} não existe";
    }

    /**
     * Metodo para válidar se a captação possui as inforções necessárias para gerar o body
     * @param Captacao $captacao
     */
    private function validate(Captacao $captacao) {
        if (!isset($captacao->container) and count($captacao->container) === 0)
            return 'Faltando containeres';
        
        if (!isset($captacao->terminal->individuo->nome) and empty($captacao->terminal->individuo->nome))
            return 'Faltando nome do terminal de atracação';

        if (!isset($captacao->dta_prevista_atracacao) and empty($captacao->dta_prevista_atracacao))
            return 'Faltando data prevista de atracação';

        if (!isset($captacao->bl) and empty($captacao->bl))
            return 'Faltando o número do BL';

        if ($captacao->previous_dta_prevista_atracacao === false)
            return 'Sem data prevista de atracação anterior';

        return true;
    }
    
}

<?php

namespace App\Model\Captacao;

use App\Model\Captacao\Captacao;

/**
 * Trait para gerenciamento dos corpos dos emails para captacao 
 */
trait BodyMail
{
    /**
     * Body para solicitação de bl e ce
     * @param Captacao $captacao Captação ao qual vai ser solicitado o BL
     */
    public function bodySolBL(Captacao $captacao, $full = false) {
        if (!self::validate($captacao) === true)
            return self::validate($captacao);

        // if ($captacao->status->status)
        $listaCoitainer = null;
        foreach ($captacao->container as $key => $container) {
            $listaCoitainer .= "<tr>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->codigo}</td>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->dimensao}</td>";
            $listaCoitainer .= "</tr>";
        }

        $captacao->dta_prevista_atracacao =  date('d/m/Y', strtotime($captacao->dta_prevista_atracacao));
        $tbody = "<tbody> 
                    <tr>
                        <td
                            style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                            Prezado(as),<br><br>Identificamos os processos mencionados abaixo que tem previsão de atracação no dia {$captacao->dta_prevista_atracacao}
                        </td>
                    </tr>
                    <tr>
                    <td style='padding: 18px'>
                        <table style='width:100%;'>
                            <tr>
                                <td
                                    style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    BL: {$captacao->bl}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Terminal de Redestinação: {$captacao->terminal_redestinacao_nome}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                <td style='padding: 18px'>
                    <table cellpadding='0' cellspacing='0' borderborder='0' height='60'
                        width='100%'>
                    <tr style='background-color: rgb(11,90,128)'>
                        <td style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                            Contêineres
                        </td>
                        <td style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                            Dimensão
                        </td>
                    </tr>
                    ${listaCoitainer}
                </td>
            </tr>
            </table>
            </td>
            </tr>
            </tbody>";
        if (!$full) return $tbody;
        
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
                                    <span class='m_130013103931174352preheader'></span>
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
                                    <table style='padding: 10px; width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        #TBODY#
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
     * Body para solicitação de bl
     * @param Captacao $captacao Captação ao qual vai ser solicitado o BL
     */
    public function bodyConfRecBL(Captacao $captacao, $full = false) {
        if (!self::validate($captacao) === true)
            return self::validate($captacao);

        // if ($captacao->status->status)
        $listaCoitainer = null;
        foreach ($captacao->container as $key => $container) {
            $listaCoitainer .= "<tr>";
            $listaCoitainer .= "<td style='font-family: Arial, sans-serif; font-size: 12px;padding-left:5px;'>{$container->codigo}</td>";
            $listaCoitainer .= "<td style='font-family: Arial, sans-serif; font-size: 12px;padding-left:5px;'>{$container->dimensao}</td>";
            $listaCoitainer .= "</tr>";
        }
        $captacao->dta_prevista_atracacao =  date('d/m/Y', strtotime($captacao->dta_prevista_atracacao));
        $tbody = " <tbody>
                        <tr>
                            <td style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                Prezado(as),<br><br>Estamos providenciando o cadastro junto ao terminal e retornamos em breve.<br><br>Atenciosamente
                            </td>
                        </tr>
                    </tbody>";
        if (!$full) return $tbody;

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
                                    <span class='m_130013103931174352preheader'>                   
                                    </span>
                                </td>
                            </tr>
    
                            <tr>
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
                                    <table style='padding: 10px; width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        #TBODY#
                                       
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
                                        Company_Name Logística Ltda, Av. Cassiano Ricardo, 601, sala 61 e 63, Pq. Res. Aquarius, São José dos Campos – SP                      <br>
                                    </span>
                                </td>
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
     * Body para solicitação de ce master t
     * @param Captacao $captacao Captação ao qual vai ser solicitado o BL
     */
    public function bodySolCE(Captacao $captacao, $full = false) {
        if (!self::validate($captacao) === true)
            return self::validate($captacao);

        // if ($captacao->status->status)
        $listaCoitainer = null;

        foreach ($captacao->container as $key => $container) {
            $listaCoitainer .= "<tr>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->codigo}</td>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->dimensao}</td>";
            $listaCoitainer .= "</tr>";
        }
        $captacao->dta_prevista_atracacao =  date('d/m/Y', strtotime($captacao->dta_prevista_atracacao));
        $tbody = "<tbody> 
                            <tr>
                                <td
                                    style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Prezado(as),<br><br>Identificamos os processos mencionados abaixo que tem previsão de atracação no dia {$captacao->dta_prevista_atracacao}
                                    <br><br>
                                    Por gentileza, encaminhar o extrato de CE Master.
                                </td>
                            </tr>
                            <tr>
                            <td style='padding: 18px'>
                                <table style='width:100%;'>
                                    <tr>
                                        <td
                                            style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                            BL: {$captacao->bl}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                        <td style='padding: 18px'>
                            <table cellpadding='0' cellspacing='0' borderborder='0' height='60'
                                width='100%'>
                            <tr style='background-color: rgb(11,90,128)'>
                                <td style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Contêineres
                                </td>
                                <td style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Dimensão
                                </td>
                            </tr>
                            ${listaCoitainer}
                        </td>
                    </tr>
                    </table>
                    </td>
                    </tr>
                    </tbody>";
        if (!$full) return $tbody;

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
                                    <span class='m_130013103931174352preheader'></span>
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
                                    <table style='padding: 10px;width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        #TBODY#
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
     * Body para solicitação de ce master
     * @param Captacao $captacao Captação ao qual vai ser solicitado o BL
     */
    public function bodyConfirmarCliente(Captacao $captacao, $full = false) {
        if (!self::validate($captacao) === true and $captacao->nome_navio)
            return self::validate($captacao);

        // if ($captacao->status->status)
        $listaCoitainer = null;
        foreach ($captacao->container as $key => $container) {
            $listaCoitainer .= "<tr>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->codigo}</td>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->dimensao}</td>";
            $listaCoitainer .= "</tr>";
        }
        $captacao->dta_prevista_atracacao =  date('d/m/Y', strtotime($captacao->dta_prevista_atracacao));
        $tbody = "<tbody>
                            <tr>
                                <td
                                    style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Prezado(as),
                                    <br>
                                    <br>
                                    Cadastro solicitado ao terminal {$captacao->terminal_redestinacao_nome}

                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 18px'>
                                    <table style='width:100%;'>
                                        <tr>
                                            <td
                                                style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                Importador: {$captacao->proposta->cliente->nome}

                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                BL: {$captacao->bl}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 18px'>
                                    <table cellpadding='0' cellspacing='0' borderborder='0' height='60'
                                        width='100%'>
                                        <tr style='background-color: rgb(11,90,128)'>
                                            <td
                                                style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                Contêineres
                                            </td>
                                            <td
                                                style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                Dimensão
                                            </td>
                                        </tr>
                                        ${listaCoitainer}
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    O navio {$captacao->nome_navio} está previsto para atracar dia {$captacao->dta_prevista_atracacao}
                                    <br><br>
                                    Atenciosamente,

                                </td>
                            </tr>
                    </table>
                    </td>
                    </tr>
                    </tbody>";
        if (!$full) return $tbody;

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
                                    <span class='m_130013103931174352preheader'></span>
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
                                    <table style='padding: 10px;width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        #TBODY#
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
     * Body para solicitação de ce master
     * @param Captacao $captacao Captação ao qual vai ser solicitado o BL
     */
    public function bodyConfirmarAtracacao(Captacao $captacao, $pk_email=null, $full = false) {
        if (!self::validate($captacao) === true and $captacao->nome_navio)
            return self::validate($captacao);
        
        $divergencia = null;
        if ($pk_email) {
            if (isset($pk_email['ocorrencia'])) {
                $divergencia = "<table>
                            <tr>
                            <td style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                Divergência(s):
                            </td>
                            </tr>";
                $oc = '';
                foreach($pk_email['ocorrencia'] as $ocorrencia) {
                    $cap_historico = new CaptacaoHistorico($ocorrencia);
                    $oc .= "<tr>
                                <td
                                    style='padding: 0 10px;color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    {$cap_historico->ocorrencia}
                                </td>
                            </tr>";
                }
                $divergencia .= $oc . "</table>";
            }
        }
        // if ($captacao->status->status)
        $listaCoitainer = null;
        foreach ($captacao->container as $key => $container) {
            $listaCoitainer .= "<tr>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->codigo}</td>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->dimensao}</td>";
            $listaCoitainer .= "</tr>";
        }

        $texto_presenca_carga = $captacao->extrato_terminal->isLoaded() ? 'Segue  em anexo a presença de carga' : 'Navio em operação, estaremos enviando a presença de carga em breve.<br>';
    
        $extrato_terminal = $captacao->extrato_terminal->isLoaded() ?
        "<tr>
            <td bgcolor='#45aaf2'
                style='min-width:50%;width:50%;height:42px;background-repeat:repeat-x;background-position:bottom;background-color:#4b6584;border-radius:100px'
                align='left' valign='middle'>
                <table style='min-width:100%;width:100%' cellpadding='0'
                    cellspacing='0' border='0' bgcolor=''>

                    <tbody>
                        <tr>
                            <td
                                style='display:min-width:100%;width:100%;font-size:6px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                <a href='{$captacao->extrato_terminal->url}'
                                    title='Donwload Extrato do Terminal'
                                    style='display:block;padding:6px 6px 6px 24px;font-size:16px;font-weight:bold;color:#fff;text-align:left;text-decoration:none;font-family:Arial,Helvetica,sans-serif'
                                    target='_blank'>
                                    Extrato do Terminal
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>" : null;
        $captacao->dta_atracacao =  date('d/m/Y', strtotime($captacao->dta_atracacao));
        $tbody = "<tbody style='padding: 80px;'>
                            <tr>
                                <td
                                    style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Prezado(as),
                                    <br><br>
                                    {$texto_presenca_carga}
                                </td>
                            </tr>
                            <tr>
                                    <td style='padding: 18px'>
                                        <table style='width:100%;'>
                                            <tr>
                                                <td
                                                    style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Importador: {$captacao->proposta->cliente->nome}

                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    BL: {$captacao->bl}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Data Atracação: {$captacao->dta_atracacao}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Terminal: {$captacao->terminal_redestinacao_nome}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style='color:#524c61;font-size:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Navio: {$captacao->nome_navio}
                                                </td>
                                            </tr>
                                            <tr><td height='30'></td></tr>
                                            <tr>
                                                <td class='navio'>
                                                    {$divergencia}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 18px'>
                                        <table cellpadding='0' cellspacing='0' borderborder='0' height='60'
                                            width='100%'>
                                            <tr style='background-color: rgb(11,90,128)'>
                                                <td
                                                    style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Contêineres
                                                </td>
                                                <td
                                                    style='color: #eee;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Dimensão
                                                </td>
                                            </tr>
                                            ${listaCoitainer}
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                <td style='font-size:13px'>
                                <table style='width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                    cellspacing='0'>

                                    <tbody>
                                        <tr>
                                            <td
                                                style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                            
                                                Após a averbação da DI, solicitamos que uma cópia seja encaminhada A/C
                                                <a href='mail:liberacao@Company_Name.com.br'>liberacao@Company_Name.com.br</a>.
                                                <br><br>
                                                Demais informações podem ser encontradas em nosso IService do link.
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style='padding:18px 12px'>



                                                <table style='min-width:50%;width:50%' cellpadding='0' cellspacing='0'
                                                    border='0' bgcolor=''>
                                                    <tbody>
                                                        <tr>
                                                            <td bgcolor='#0cd573'
                                                                style='min-width:50%;width:50%;height:42px;background-repeat:repeat-x;background-position:bottom;background-color:#eb3b5a;border-radius:100px'
                                                                align='left' valign='middle'>
                                                                <table style='min-width:100%;width:100%' cellpadding='0'
                                                                    cellspacing='0' border='0' bgcolor=''>

                                                                    <tbody>
                                                                        <tr>
                                                                            <td
                                                                                style='min-width:100%;width:100%;font-size:6px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                                                <a href='{$captacao->iserv->url}' title='Donwload ISERV'
                                                                                    style='display:block;padding:6px 6px 6px 24px;font-size:16px;font-weight:bold;color:#fff;text-align:left;text-decoration:none;font-family:Arial,Helvetica,sans-serif'
                                                                                    target='_blank'>
                                                                                    Iserv

                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style='height:10px'></td>
                                                        </tr>
                                                        {$extrato_terminal}
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style='color:#524c61;line-height:24px;font-size:16px;padding:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                    Nos mantemos a disposição para qualquer necessidade. 
                                                    <br><br>
                                                    Atenciosamente,
                                            </td>
                                            </tr>
                                    </tbody>
                                </table>
                            </td>
                                </tr>
                        </table>
                    </td>
                    </tr>
                    </tbody>";
                 
        if (!$full) return $tbody;

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
                                    <span class='m_130013103931174352preheader'></span>
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
                                <td style='font-size:13px;'>
                                    <table style='padding: 10px; padding: 10px;width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        #TBODY#
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
     * Body para solicitação de ce master
     * @param Captacao $captacao Captação ao qual vai ser solicitado o BL
     */
    public function bodyPresencaCarga(Captacao $captacao, $full = false) {
        if (!self::validate($captacao) === true and $captacao->nome_navio)
            return self::validate($captacao);
        
        // if ($captacao->status->status)
        $listaCoitainer = null;
        foreach ($captacao->container as $key => $container) {
            $listaCoitainer .= "<tr>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->codigo}</td>";
            $listaCoitainer .= "<td style='color: #524c61;font-size:16px;padding-left:4px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>{$container->dimensao}</td>";
            $listaCoitainer .= "</tr>";
        }
        $captacao->dta_atracacao =  date('d/m/Y', strtotime($captacao->dta_atracacao));
        $tbody = "<tbody>
                            <tr>
                                <td
                                    style='color:#524c61;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Prezados(as)
                                    <br><br>
                                    Segue o link com a presença de carga.
                                    <br>
                                    Caso necessitem de algum auxílio, favor nos contatar.
                                </td>
                            </tr>
                            <tr>
                                <td style='padding:18px 12px'>
                                    <table style='min-width:50%;width:50%' cellpadding='0' cellspacing='0'
                                        border='0' bgcolor=''>
                                        <tbody>
                                            <tr>
                                                <td bgcolor='#0cd573'
                                                    style='min-width:50%;width:50%;height:42px;background-repeat:repeat-x;background-position:bottom;background-color:#eb3b5a;border-radius:100px'
                                                    align='left' valign='middle'>
                                                    <table style='min-width:100%;width:100%' cellpadding='0'
                                                        cellspacing='0' border='0' bgcolor=''>

                                                        <tbody>
                                                            <tr>
                                                                <td
                                                                    style='min-width:100%;width:100%;font-size:6px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                                                    <a href='{$captacao->iserv->url}' title='Donwload ISERV'
                                                                        style='display:block;padding:6px 6px 6px 24px;font-size:16px;font-weight:bold;color:#fff;text-align:left;text-decoration:none;font-family:Arial,Helvetica,sans-serif'
                                                                        target='_blank'>
                                                                        Extrato do Terminal
                                                                    </a>
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
                                <td
                                    style='color:#524c61;line-height:24px;font-size:16px;padding:16px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                                    Atenciosamente,
                                </td>
                            </tr>
                        </tbody>";
        if (!$full) return $tbody;

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
                                    <span class='m_130013103931174352preheader'></span> </td> </tr> <tr>
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
                                    <table style=' padding: 10px;width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        #TBODY#
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
     * Body para informar alteração da data de atracação
     * @param Captacao $captacao Captação ao qual vai ser solicitado o BL
     */
    public function bodyAlteradoDtaAtracacao(Captacao $captacao, $full = false) {
        if (!self::validate($captacao) === true and $captacao->navio_nome)
            return self::validate($captacao);

        // if ($captacao->status->status)
        $listaCoitainer = null;
        foreach ($captacao->container as $key => $container) {
            $listaCoitainer .= "<tr>";
            $listaCoitainer .= "<td style='font-family: Arial, sans-serif; font-size: 12px;padding-left:5px;'>{$container->codigo}</td>";
            $listaCoitainer .= "<td style='font-family: Arial, sans-serif; font-size: 12px;padding-left:5px;'>{$container->dimensao}</td>";
            $listaCoitainer .= "</tr>";
        }

        $captacao->dta_prevista_atracacao =  date('d/m/Y', strtotime($captacao->dta_prevista_atracacao));
        $tbody = "<tbody>
                    <tr>
                        <td style='color:#524c61;padding: 10px;line-height:24px;font-size:16px;padding:18px;font-family:Source Sans Pro LIGHT,Arial,Helvetica,sans-serif'>
                            Prezado(as),
                            <br>
                            <br>
                            Notar que, houve alteração na data de atracação.
                            <br><br>
                            <div style='font-weight: bold; font-size:14px'>
                            ETA Atualizado: {$captacao->dta_prevista_atracacao}
                            <br> 
                            ETA Anterior: {$captacao->previous_dta_prevista_atracacao}
                            <br> 
                            Navio: {$captacao->nome_navio}
                            <br> 
                            Terminal: {$captacao->terminal_redestinacao_nome}
                            </div>
                            <br><br>
                            Atenciosamente
                        </td>
                    </tr>

             </tbody>";
        if (!$full) return $tbody;

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
                                    <span class='m_130013103931174352preheader'>
                                     
                                    </span>
                                </td>
                            </tr>
    
                            <tr>
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
                                    <table style='padding: 10px; width:100%;border-radius:6px' bgcolor='#FFFFFF' border='0' cellpadding='0'
                                        cellspacing='0'>
                                        #TBODY#
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
                                        Company_Name Logística Ltda, Av. Cassiano Ricardo, 601, sala 61 e 63, Pq. Res. Aquarius, São José dos Campos – SP                      <br>
                                    </span>
                                </td>
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
    private function body(string $body_name, Captacao $captacao) {
        if (method_exists($this, $body_name)) 
            return \call_user_func(array($this, $body_name), $captacao);

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

        if (!isset($captacao->terminal_redestinacao_nome) and empty(($captacao->terminal_redestinacao_nome)))
            return 'Faltando nome do terminal de redesticação';

        if (!isset($captacao->dta_prevista_atracacao) and empty($captacao->dta_prevista_atracacao))
            return 'Faltando data prevista de atracação';

        if (!isset($captacao->bl) and empty($captacao->bl))
            return 'Faltando o número do BL';

        if ($captacao->previous_dta_prevista_atracacao === false)
            return 'Sem data prevista de atracação anterior';

        return true;
    }
    
}

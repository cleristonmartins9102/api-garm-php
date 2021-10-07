<?php
namespace App\Model\Liberacao;

use App\Lib\Database\Record;

class LiberacaoHistorico extends Record
{
    const TABLENAME = "LiberacaoHistorico";

    public function addHistorico($ocorrencia)
    {
        echo 222;exit();
        $historico = new LiberacaoHistorico();
        $historico->ocorrencia = $ocorrencia;
        $historico->id_liberacao = $this->id_liberacao;
        $historico->store();
    }
}

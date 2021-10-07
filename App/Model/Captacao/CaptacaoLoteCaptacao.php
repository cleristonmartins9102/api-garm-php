<?php

namespace App\Model\Captacao;

use App\Lib\Database\Record;

class CaptacaoLoteCaptacao extends Record
{
    const MANYTOMANY = 'true';
    const TABLENAME = 'CaptacaoLoteCaptacao';

    public function get_captacao() {
        return new Captacao($this->id_captacao);
    }
}

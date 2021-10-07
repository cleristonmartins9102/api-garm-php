<?php
namespace App\Model\Operacao;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Captacao\Captacao;
use App\Model\Documento\Upload;
use App\Model\Liberacao\Liberacao;

class VwOperacao extends Record
{
    const TABLENAME = "VwOperacao";

    public function get_liberacao()
    {
        return new Liberacao($this->id_operacao);
    }
}

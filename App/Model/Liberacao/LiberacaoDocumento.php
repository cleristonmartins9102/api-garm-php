<?php
namespace App\Model\Liberacao;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Documento\Upload;

class LiberacaoDocumento extends Record
{
    const MANYTOMANY = 'true';
    const TABLENAME = "LiberacaoDocumento";

    public function get_nomeoriginal_upload() {
        return (new Upload($this->id_upload))->nome_original;
    }

    public function get_tipo_documento() {
        return (new Upload($this->id_upload))->id_tipodocumento;
    }
}


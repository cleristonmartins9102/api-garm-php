<?php
namespace App\Model\Captacao;

use App\Lib\Database\Record;
use App\Model\Documento\Upload;

class CaptacaoUpload extends Record
{
    const MANYTOMANY = 'true';
    const TABLENAME = "CaptacaoUpload";

    public function get_upload() {
        return new Upload($this->id_upload);
    }
}

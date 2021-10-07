<?php
namespace App\Model\Documento;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Aws\Bucket;
use App\Model\Aws\BucketFolder;

/**
 *
 */
class Upload extends Record
{
  const TABLENAME = "Upload";

  public function save()
  {
     // Relacionando o upload com o tipo de documento no banco de dados
     $documentoObj = new UploadDocumento;
     $documentoObj->id_upload = $this->id;
     $documentoObj->id_tipodocumento = $this->id_tipodocumento;
     $documentoObj->removeProperty('id_tipodocumento');

     $documentoObj->store();
     // Apagando a propriedade id_tipodocumento ante de salvar, pois o upload não possue esse campo
     self::removeProperty('id_tipodocumento');
     $this->store();
  }

  public function get_tipo_documento()
  { 
    $bucket = new Bucket($this->id_bucket);
    return (new TipoDocumento($this->id_tipodocumento));
  }

  public function get_bucket() {
    return new Bucket($this->id_bucket);
  }
}

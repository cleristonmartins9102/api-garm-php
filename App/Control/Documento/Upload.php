<?php
namespace App\Control\Documento;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Documento\Upload as UploadModel;
use App\Model\Aws\Bucket;
use Exception;
use Aws\S3\S3Client;
use App\Model\Documento\TipoDocumento;

class Upload extends Controller
{
  private $data;


  public function save($files, array $info_up)
  {
    if (!isset($info_up['tipo_up']))
      return [];

    self::openTransaction();
    $bucket = new Bucket;
    $bucket('utilidade', $info_up['tipo_up']);

    if (!isset($bucket->bucket)) {
      echo 'Bucket não encontrado';
      exit();
    }

    $region = $bucket->region;
    $bucket_name = "Company_Name.{$info_up['tipo_up']}";

    $tipo_documento = new TipoDocumento;
    if (isset($info_up['tipo_doc'])) {
      $bucket->folder = $info_up['tipo_doc'];
      $bucket_name .= "/{$info_up['tipo_doc']}";
      $tipo_documento('nome', str_replace('_', ' ', $info_up['tipo_doc']));
    } elseif (isset($info_up['tipo_up'])) {
      $tipo_documento('nome', str_replace('_', ' ', $info_up['tipo_up']));
    }


    if (empty($tipo_documento->id_tipodocumento))
      return 'Tipo de Documento não encontrado';



    try {
      // dispara exceção caso não tenha dados enviados
      if (empty($files['file'])) {
        throw new Exception("File not uploaded", 1);
      }

      // cria o objeto do cliente, necessita passar as credenciais da AWS
      $clientS3 = self::s3($region);

      $random = mt_rand(1000000000000, 100000000000000);
      $file_name = $files['file']->getClientFilename();
      $uploadFileName = "${random}#${file_name}";

      $config = array(
        'Bucket' => $bucket_name,
        'Key'    => $uploadFileName,
        'SourceFile' => $files['file']->file,
      );

      if ($info_up['tipo_doc'] !== 'proposta')
          $config['ACL'] = 'public-read';


      // método putObject envia os dados pro bucket selecionado (no caso, teste-marcelo
      $response = $clientS3->putObject($config);

   
     
      
      $upload_model = new UploadModel;
      $upload_model->id_bucket = $bucket->id_bucket;
      $upload_model->nome_original = $file_name;
      $upload_model->nome_sistema = $uploadFileName;
      $upload_model->localizacao = $bucket_name;
      $upload_model->id_tipodocumento = $tipo_documento->id_tipodocumento;
      $upload_model->token = md5(uniqid(""));
      $upload_model->url = $response['ObjectURL'];


      // Salvando as informações do arquivo
      $upload_model->store();

      $res = [
        'fileNameOri' => $file_name,
        'fileNameUnix' => $uploadFileName,
        'directory'   => $bucket_name,
        'id'          => $upload_model->id
      ];
      self::closeTransaction();

      return json_encode($res);
    } catch (Exception $e) {
      echo "Erro > {$e->getMessage()}";
    }
  }

  public function download($token)
  {
    self::openTransaction();
    $criteria = new Criteria;
    $criteria->add(new Filter('token', '=', $token));
    $file = (new Repository('App\Model\Documento\Upload'))->load($criteria);

    // Verificando se encontrou o arquivo no banco de dados
    if (count($file) == 0) {
      echo json_encode(array('error' => 'file not found'));
      exit();
    }

    $client = self::s3($file[0]->bucket->region);
    // print_r($file[0]->nome_sistema);exit();
    // Get the object.
    $result = $client->getObject([
      'Bucket' => $file[0]->localizacao,
      'Key'    => $file[0]->nome_sistema,
    ]);


    header('Content-Description: File Transfer');
    //this assumes content type is set when uploading the file.
    header('Content-Type: ' . $result['ContentType']);
    header('Content-Disposition: attachment; filename=' . $file[0]->nome_original);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    echo $result['Body'];
    self::closeTransaction();
  }


  private function s3(string $region): S3Client
  {
    // cria o objeto do cliente, necessita passar as credenciais da AWS
    $clientS3 = S3Client::factory(array(
      'version' => 'latest',
      'region' => $region,
      'key'    => getenv('AWS_KEY'),
      'secret' => getenv('AWS_SECRET')
    ));
    return $clientS3;
  }
}

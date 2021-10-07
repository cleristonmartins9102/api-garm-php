<?php
namespace App\Model\Liberacao;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Captacao\Captacao;
use App\Model\Documento\Upload;

class VwLiberacao extends Record
{
    const TABLENAME = "VwLiberacao";
    private $containeres = [];

    public function get_container()
    {
        $containeres = (new Captacao($this->id_captacao))->container;
        foreach ($containeres as $key => $container) {
            $this->containeres[] = $container->toArray();
        }
        return $this->containeres;
    }

    public function get_captacao()
    {
        return new Captacao($this->id_captacao);
    }

    public function get_eventos()
    {
        $eventos = [];
        $criteria = new Criteria;
        $criteria->add(new Filter('id_liberacao', '=', $this->id_liberacao));
        $repository = new Repository('App\Model\Liberacao\LiberacaoEvento');
        $object = $repository->load($criteria);
        foreach ($object as $key => $evento) {
            $eventos[] = $evento->toArray();
        }
        return $eventos;
    }


    public function get_anexo()
    {   
        $criteria = new Criteria;
        $criteria->add(new Filter('id_liberacao', '=', $this->id_liberacao));
        $repository = new Repository('App\Model\Liberacao\LiberacaoDocumento');
        $object = $repository->load($criteria);
        $documentos = array();

        // Verificando se encontrou documentos
        if (count($object) > 0) {
            $documentos = array();
            // Percorrendo o array com os objetos para pegar os objetos
            foreach ($object as $key => $lib_uploda) {
                $id_upload = $lib_uploda->id_upload;
                $upload = new Upload($id_upload);
                if ($upload->tipoDocumento !== null) {
                    $upload->id_tipodocumento = $upload->tipo_documento->id_tipodocumento;
                    $upload->tipodocumento = $upload->tipo_documento->nome;
                }
                $documentos[] = $upload->toArray();
            }
            
        }
        return $documentos;
    }
}

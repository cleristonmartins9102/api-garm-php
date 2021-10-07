<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

class Contato extends Record
{
  const TABLENAME = "Contato";

  public function get_contato()
  {
    $iContato = new IndividuoContato;
    $iContato->id_individuo = $this->id_individuo;
    $listContatosObj = $iContato->contatos;
    
    foreach ($listContatosObj as $key => $contato) {
      $dataFull[] = new Contato($contato->id_contato);
    }
    return  isset($dataFull)?$dataFull:null;
  }

  public function get_empresa()
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_contato', '=', $this->id_contato));
    $repository = new Repository('App\Model\Pessoa\IndividuoContato');
    $ind_contato = $repository->load($criteria);
    return new Individuo($ind_contato[0]->id_individuo);
  }

  // Metodo que verifica se o contato esta sendo usado, se ele faz parte de algum grupo de contato e se pode ser exluido
  public function checkIsDeletable()
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_contato', '=', $this->id_contato));
    $repository = new Repository('App\Model\Pessoa\GrupoContato');
    $response = $repository->load($criteria);
    if (count($response) > 0) {
      return 'true';
    } else {
      return 'false';
    }
  }

  public function clean() {
    unset($this->data['id_contato']);
    unset($this->data['ddi']);
    unset($this->data['ddd']);
    unset($this->data['telefone']);
    unset($this->data['created_at']);
    unset($this->data['updated_at']);
  }
}

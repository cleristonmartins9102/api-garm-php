<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Lib\Tool\Register;

class Individuo extends Record
{
  const TABLENAME = "Individuo";
  private $papeis;
  private $endereco;
  private $contatos = array();

  public function __construct($id = null) {
    parent::__construct($id);
    self::checkZero();
  }

  public function get_pessoa()
  {
      $pessoa = array();
      $criteria = new Criteria;
      $criteria->add(new Filter('id_individuo', '=', $this->id_individuo));
      $repo = new Repository("App\Model\Pessoa\\{$this->tipo}");
      return $repo->load($criteria)[0] ?? [];
  }

  public function addContato(Contato $contato)
  { 
    $contato->store();
    $ic = new IndividuoContato;
    $ic->id_individuo = intval($this->id_individuo ?? $this->id);
    $ic->id_contato = intval($contato->id_contato ?? $contato->id);
    return $ic->store();
  }

  public function upContato(Contato $contato)
  {
    $contato->store();
  }

  public function get_contato()
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_individuo', '=', $this->id_individuo));
    $repo = new Repository('App\Model\Pessoa\IndividuoContato');
    $vinculos = $repo->load($criteria);
    foreach ($vinculos as $vinculo) {
      $contato = new Contato($vinculo->id_contato);
      $contato->in_use = $contato->checkIsDeletable();
      $this->contatos[] = $contato->getData();
    }
    return $this->contatos;
  }

  public function addPapel(Papel $papel)
  {
    //Verifica se o objeto esta vazio
    if (!empty($papel)){
      $pp = new IndividuoPapel;
      $pp->id_individuo = intval($this->id);
      $pp->id_papel = $papel->id_papel;
      return $pp->store();
    }
  }

  public function deletePapel(Array $papel=null)
  {
    if ($papel == null) {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_individuo', '=', $this->id_individuo));
      $ind_papel = new IndividuoPapel;
      $ind_papel->deleteByCriteria($criteria);
    }
  }

  public function deleteContato(Array $contato=null)
  {
    if ($contato == null) {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_individuo', '=', $this->id_individuo));
      $repository = new Repository('App\Model\Pessoa\IndividuoContato');
      $ic = $repository->load($criteria);
      foreach ($ic as $key => $ind_contato) {
        $contato = new Contato($ind_contato->id_contato);
        // Verificando se o contato esta em uso no grupo de contato
        if (!$contato->checkIsDeletable()) {
          $contato->delete();
        }
      }
      $ind_contato = new IndividuoContato;
      $ind_contato->deleteByCriteria($criteria);
    }
  }

  public function get_papel()
  {
    $papeis = array();
    $criteria = new Criteria;
    $criteria->add(new Filter('id_individuo', '=', $this->id_individuo));
    $repo = new Repository('App\Model\Pessoa\IndividuoPapel');
    $vinculos = $repo->load($criteria);
    foreach ($vinculos as $key => $value) {
      $papeis[] = new Papel($value->id_papel);
    }
    return $papeis;
  }

  public function checkZero() {
    $zero = null;
    if (strlen($this->id_individuo) < 14) {
      $qtd_zeros = 14 - strlen($this->id_individuo);
      for ($i=1; $i<=$qtd_zeros; $i++) {
        $zero .= "0";
      }
    }
    $this->identificador = $zero.$this->id_individuo;
  }

  public function set_endereco(Endereco $endereco)
  {
    //Verifica se o objeto esta vazio
    if (!empty($endereco)){
      $this->endereco = $endereco;
      $this->endereco->store();
      $this->id_endereco = $this->endereco->id;
    }
  }

  public function get_endereco()
  {
    $this->endereco = new Endereco($this->id_endereco);
    return $this->endereco;
  }
}
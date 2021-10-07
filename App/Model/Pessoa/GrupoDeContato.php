<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;

class GrupoDeContato extends Record
{
  private $contatos = [];

  const VWTABLENAME = 'VwGrupoDeContato';
  const TABLENAME = 'GrupoDeContato';

  public function addContato(GrupoContato $grupo = null)
  {
    $grupo->store();
  }

  public function get_grupo() 
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_grupodecontato', '=', $this->id_individuo));
    return (new Repository('App\Model\Pessoa\GrupoDeContato'))->load($criteria);
  }

  public function get_grupo_nome() {
    return (new GrupoDeContatoNome($this->id_nome))->nome;
  }

  public function get_contatos() 
  { 
    $criteria = new Criteria;
    $criteria->add(new Filter('id_grupodecontato', '=', $this->id_grupodecontato));
    $grupo_contato = (new Repository('App\Model\Pessoa\GrupoContato'))->load($criteria);

    if (count($grupo_contato) > 0) {
      foreach ($grupo_contato as $key => $grupo) {
        $this->contatos[] = new Contato($grupo->id_contato);
      }
    }

    return $this->contatos;
  }

  /**
   * Metodo percorre o array dos objetos contato e transforma eles em array
   * @return array Retorna um arrau com os arrays de contato
   */
  public function get_contato_to_array(): array {
    $contatos = [];
    foreach(self::get_contatos() as $contato) {
      $contato->clean();
      $contatos[] = $contato->toArray();
    }
    return $contatos;
  }

  /**
   * Metodo para limpar o objeto
   */
  public function clean() {
    unset($data['id_nome']);
  }
}

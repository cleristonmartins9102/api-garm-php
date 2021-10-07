<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;

class VwGrupoDeContato extends Record
{
  private $contatos;

  const TABLENAME = 'VwGrupoDeContato';

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

  public function get_contatosDoGrupo() 
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_grupodecontato', '=', $this->id_grupodecontato));
    $grupo_contato = (new Repository('App\Model\Pessoa\GrupoContato'))->load($criteria);
    if (count($grupo_contato) > 0) {
      foreach ($grupo_contato as $key => $grupo) {

        $contato = new Contato($grupo->id_contato);
        $contatoArr = $contato->toArray();
        $contatoArr['empresa'] = $contato->empresa->id_individuo . ' - ' . $contato->empresa->nome;
        $this->contatos[] = $contatoArr;
      }
    }
    return $this->contatos;
  }
}

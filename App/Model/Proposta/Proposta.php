<?php

namespace App\Model\Proposta;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Proposta\PropostaPredicado;
use App\Model\Vendedor\Vendedor;
use App\Model\Qualificacao\Qualificacao;
use App\Model\Pessoa\GrupoDeContato;
use App\Model\Pessoa\Individuo;
use App\Model\Documento\Upload;
use App\Model\Servico\PreProAppValor;
use App\Model\Regime\Regime;
use App\Model\Servico\PacotePredicado;
use App\Model\Servico\Pacote;
use App\Model\Margem\Margem;
use App\Model\Depot\Depot;

use App\Model\Regime\RegimeClassificacao;
use App\UserCase\Proposta\PropostaTerminais\GetPropTerminais;

/**
 *
 */
class Proposta extends Record
{
  const TABLENAME = "Proposta";

  public function addPredProposta(PropostaPredicado $predicado)
  {
    // if ( empty($predicado->id) ) 
      $predicado->store();
  }

  public function get_vendedor()
  {
    return new Vendedor($this->id_vendedor);
  }

  public function get_qualificacao()
  {
    return new Qualificacao($this->id_qualificacao);
  }

  public function get_cliente()
  {
    return new Individuo($this->id_cliente);
  }

  /**
   * Metdodo que busca o grupo de contato que foi escolhido na proposta
   */
  public function get_grupodecontato()
  {
    return new GrupoDeContato($this->id_contato);
  }
  
  /**
   * Metdodo que busca todos os grupos de contato entre coadjuvante e principal
   */
  public function get_allgrupodecontato()
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_adstrito', '=', $this->id_cliente));
    $criteria->add(new Filter('id_coadjuvante', '=', $this->id_coadjuvante));
    $grupos = (new Repository(GrupoDeContato::class))->load($criteria);
    $grus = [];
    foreach ($grupos as $key => $grupo) {
      $gr = new \StdClass;
      $gr->nome = $grupo->grupo_nome;
      $gr->contatos = $grupo->contato_to_array;
      $grus[] = $gr;
    }
    return $grus;
  }

  public function get_parentela()
  {
    $parentesFull = [];
    $a = false;
    while ($a === false) {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_filho', '=', $this->id_proposta));
      $repository = new Repository('App\Model\Proposta\PropostaParentela');
      $parentes = $repository->load($criteria);
      if (count($parentes) == 0) {
        $a = true;
      } else {
        $parentesFull[] = $parentes[0]->getData();
        // print_r($parentes[0]->id_pai);exit();

        $this->id_proposta = $parentes[0]->id_pai;
      }
    }
    return $parentesFull;
  }

  public function get_servico($dimensao = null)
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_proposta', '=', $this->id_proposta));

    $cri_dimensao = new Criteria;
    if (!is_null($dimensao)) {
      $cri_dimensao->add(new Filter('dimensao', '=', $dimensao));
    } else {
      $cri_dimensao->add(new Filter('dimensao', '=', 40));
      $cri_dimensao->add(new Filter('dimensao', '=', 20), $cri_dimensao::OR_OPERATOR);
      $cri_dimensao->add(new Filter('dimensao', '=', 'ambos'), $criteria::OR_OPERATOR);
      $cri_dimensao->add(new Filter('dimensao', 'is', null), $criteria::OR_OPERATOR);
    }

    $criteria->add($cri_dimensao);
    $repository = new Repository('App\Model\Proposta\PropostaPredicado');
    $object = $repository->load($criteria);
    return $object;
  }

  /**
   * @param null $param
   * @param null $dimensao
   * @param null $margem
   * @param null $depot
   * @param null $cidade
   */
  // public function servicoById($id = null, $dimensao = null, $margem = null)
  public function servicoById($id = null, $dimensao = null, $margem = null, Depot $depot = null)
  {
    $cri_margem = new Criteria;

    $mar = new Margem;
    $mar('margem', 'ambas');
    $cri_margem->add(new Filter('id_margem', '=', $mar->id_margem));

    if (!is_null($margem) and is_numeric($margem)) {
      $mar('id_margem', $margem);
      $cri_margem->add(new Filter('id_margem', '=', $mar->id_margem), $cri_margem::OR_OPERATOR);
    }
    $criteria = new Criteria;
    $criteria->add(new Filter('id_proposta', '=', $this->id_proposta));
    $criteria->add(new Filter('id_predicado', '=', $id));

    // Caso for despacho, faça a busca usando os critérios como id_depot e id_cidade
    if ( $depot && $depot->isLoaded() ) {
      $criteria->add(new Filter('id_depot', '=', $depot->id_depot));
      
      // Buscar Cidade
      $cri_cidade = new Criteria;
      $cri_cidade->add(new Filter('id_propostapredicado', '=', 'PropostaPredicado.id_propostapredicado', false));
      $cri_cidade->add(new Filter('id_cidade', '=', $depot->individuo->endereco->cidade->id_cidade));
      $rep_cidade = new Repository('App\Model\Proposta\PropostaPredicadoCidade');
    
      $criteria->add(new Filter('EXISTS(' . $rep_cidade->dump($cri_cidade) . ')'));
    }
    
    $cri_dimensao = new Criteria;

    if ($dimensao && $dimensao !== 'ambos') {
      $cri_dimensao->add(new Filter('dimensao', '=', $dimensao));
    }
    $cri_dimensao->add(new Filter('dimensao', '=', 'ambos'), $cri_dimensao::OR_OPERATOR);

    $criteria->add($cri_dimensao);
    $criteria->add($cri_margem);
    
    $repository = new Repository('App\Model\Proposta\PropostaPredicado');
    $object = $repository->load($criteria);

    if ( count($object) > 0 ) 
      return $object;

    // Recursivo caso nao encontre ele busca novamente sem o depot
    if ( !is_null($depot) ) 
      $object = $this->servicoById($id, $dimensao, $margem);

    if ( count($object) > 0 ) 
      return $object;

      
    /**
     * Verificando se o servico foi encontrado, caso não, 
     * ele verifica se o servico faz parte de algum pacote, 
     * caso sim ele faz um recursivo com o pacote como parametro
    */
    if (count($object) === 0) {
      $criteria->clean();
      $criteria->add(new Filter('id_predicado', '=', $id));
      $pacote_predicado = (new Repository(PacotePredicado::class))->load($criteria);
      foreach($pacote_predicado as $pacote) {
        if ( $depot && $depot->isLoaded() ) {
          $object = $this->servicoById((new Pacote($pacote->id_pacote))->id_predicado, $dimensao, $margem, $depot);
        } else {
          $object = $this->servicoById((new Pacote($pacote->id_pacote))->id_predicado, $dimensao, $margem);
        }
        if ( is_array($object) && count($object) > 0 ) break;
      }
    }
    return ( is_array($object) and count($object) > 0 ) ? $object : new PreProAppValor();
  }

  public function servico_periodo(string $id_predicado, string $dimensao) {
    return count($this->servicoById($id_predicado, $dimensao)) > 0 
      ? (!is_null($this->servicoById($id_predicado, $dimensao)[0]->franquia_periodo) 
        ? [ "valor" => $this->servicoById($id_predicado, $dimensao)[0]->franquia_periodo ] 
        : [ "valor" => "nfp", "legend" => "item não possue franquia perido definido" ])  
      : [ "valor" => "nip", "legend" => "item não esta em proposta" ];
  }

  public function predicado($id = null)
  {
    if ($id) {
      echo $id;
      // return new PropostaPredicado($id);
    }
  }

  public function get_anexo_proposta()
  {
    return new Upload($this->id_doc_proposta);
  }

  public function get_anexo_aceite()
  {
    return new Upload($this->id_aceite);
  }

  public function get_regime()
  {
    return new Regime($this->id_regime);
  }

  public function get_regime_classificacao()
  {
    return new RegimeClassificacao($this->id_regimeclassificacao);
  }


  public function get_byRegime()
  {
    if ($this->id_regime) {

      // Buscando o id do regime
      $criteria = new Criteria;
      $criteria->add(new Filter('regime', '=', $this->id_regime));
      
      $repository = (new Repository('App\Model\Regime\Regime'))->load($criteria);
      $regime = count($repository) > 0 ? $repository[0]->id_regime : null;

      // SELECT * FROM Proposta WHERE (tipo <> 'modelo' AND id_regime = '1') and id_proposta IN (select id_proposta from PropostaTerminal where id_terminal=4)

      // Limpando os critérios da busca do id do regime
      $criteria->clean();
      // $criteria->add(new Filter('status', '=', 'ativa'));
      $criteria->add(new Filter('tipo', '<>', 'modelo'));
      $criteria->add(new Filter('id_regime', '=', $regime));
      $repository = (new Repository(get_class()))->load($criteria);
      return $repository;
    }
  }

  public function get_byRegTer()
  {
    if ($this->id_regime) {

      // Buscando o id do regime
      $criteria = new Criteria;
      $criteria->add(new Filter('regime', '=', $this->id_regime));  
      $repository = (new Repository('App\Model\Regime\Regime'))->load($criteria);

      $regime = count($repository) > 0 ? $repository[0]->id_regime : null;


      // Limpando os critérios da busca do id do regime
      $criteria->clean();
      // $criteria->add(new Filter('status', '=', 'ativa'));
      $criteria->add(new Filter('tipo', '<>', 'modelo'));
      $criteria->add(new Filter('id_regime', '=', $regime));

      $criteriaPropostaTerminal = new Criteria;
      $criteriaPropostaTerminal->addColunm('id_proposta');
      if (!is_array($this->terminal)) {
        $this->terminal = [ $this->terminal ];
      }
      foreach($this->terminal as $terminal) {
        $criteriaPropostaTerminal->add(new Filter('id_terminal', '=', $terminal));
      }
      
      $repoProTer = (new Repository(GetPropTerminais::class))->dump($criteriaPropostaTerminal);

      $fullCriteria = new Criteria;
      $fullCriteria->add(new Filter('id_proposta', 'IN', "({$repoProTer})", false), $criteria::OR_OPERATOR);
      
      $notInCriteria = new Criteria;
      $notInCriteria->addColunm('id_proposta');
      $notInCriteria->add(new Filter('id_proposta', '<>', 0));
      $repoProTer = (new Repository(GetPropTerminais::class))->dump($notInCriteria);

      $fullCriteriaWithNotIN = new Criteria;
      $fullCriteriaWithNotIN->add(new Filter('id_proposta', 'NOT IN', "({$repoProTer})", false));
      
      $fullCriteria->add($fullCriteriaWithNotIN, $criteria::OR_OPERATOR);

      $criteria->add($fullCriteria);
      $repository = (new Repository(get_class()))->load($criteria);
      return $repository;
    }
  }
}

// SELECT * FROM Proposta WHERE (tipo <> 'modelo' AND id_regime = '1' AND (id_proposta IN (SELECT id_proposta FROM PropostaTerminal WHERE (id_terminal = '3')) OR (id_proposta NOT IN (SELECT id_proposta FROM PropostaTerminal WHERE (id_proposta <> 0)))))

// SELECT count(*) FROM Proposta WHERE (tipo <> 'modelo' AND id_regime = '1') 
// SELECT count(*) FROM Proposta WHERE (tipo <> 'modelo' AND id_regime = '1') AND (id_proposta NOT IN (SELECT id_proposta FROM PropostaTerminal WHERE (id_proposta <> 0)))
// SELECT count(*) FROM Proposta WHERE (tipo <> 'modelo' AND id_regime = '1' AND (id_proposta IN (SELECT id_proposta FROM PropostaTerminal WHERE (id_terminal = '3'))))
// SELECT count(*) FROM Proposta WHERE (tipo <> 'modelo' AND id_regime = '1' AND (id_proposta IN (SELECT id_proposta FROM PropostaTerminal WHERE (id_terminal = '3')) OR (id_proposta NOT IN (SELECT id_proposta FROM PropostaTerminal WHERE (id_proposta <> 0)))))


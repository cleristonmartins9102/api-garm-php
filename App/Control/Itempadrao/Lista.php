<?php
namespace App\Control\Itempadrao;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\VwItemPadrao;
use App\Model\Servico\ItemPadrao;

use App\Model\Processo\Processo;
use App\Model\Servico\Predicado;
use App\Model\Servico\Servico;
use App\Model\Servico\ServicoMaster;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
  public function all(Request $request, Response $response, array $param = null)
  {
    try {
      self::openTransaction();
      $itens_padroes = (new VwItemPadrao)->all();
      $dataFull['total_count'] = count($itens_padroes);
      foreach ($itens_padroes as $key => $item) {
        $dataFull['items'][] = $item->toArray();
      }
      self::closeTransaction();
      return $dataFull ?? [];
    } catch (\Exception $e) {
      echo $e->getMessage();
    }
  }

  public function ofprocesso(Request $request, Response $response)
  { 
    try {
      self::openTransaction();
      $criteria = new Criteria;
      $criteria->add(new Filter('modulo', '=', 'processo'));
      $itens_padroes = (new Repository(VwItemPadrao::class))->load($criteria);
      $dataFull['total_count'] = count($itens_padroes);
      foreach ($itens_padroes as $key => $item) {
        $dataFull['items'][] = $item->toArray();
      }
      self::closeTransaction();
      return $dataFull ?? [];
    } catch (\Exception $e) {
      echo $e->getMessage();
    }
  }

  public function offatura(Request $request, Response $response)
  {
    try {
      self::openTransaction();
      $criteria = new Criteria;
      $criteria->add(new Filter('modulo', '=', 'fatura'));
      $itens_padroes = (new Repository(VwItemPadrao::class))->load($criteria);
      $dataFull['total_count'] = count($itens_padroes);
      foreach ($itens_padroes as $key => $item) {
        $dataFull['items'][] = $item->toArray();
      }
      self::closeTransaction();
      return $dataFull ?? [];
    } catch (\Exception $e) {
      echo $e->getMessage();
    }
  }

  public function byid(Request $request, Response $response, $id)
  { 
    try {
      self::openTransaction();
      $criteria = new Criteria;
      $criteria->add(new Filter('id_itempadrao', '=', $id));
      $itens_padroes = (new Repository(ItemPadrao::class))->load($criteria);
      foreach ($itens_padroes as $key => $item) {
        unset($item->item);
        $dataFull[] = $item;
      }
      self::closeTransaction();
      return $dataFull ?? [];
    } catch (\Exception $e) {
      echo $e->getMessage();
    }
  }

  public function byoperacao(Request $request, Response $response, $data = null)
  { 
    self::openTransaction();

    if ($data->processo)
      $processo = new Processo($data->processo);
    $itens = [ 'commom' => null, 'all' => null ];
    if ( $processo->isDespacho() ) {
      $itens = $this->getItem(
        (new Processo($data->processo))->valor_mercadoria,
        'processo', 
        (new Processo($data->processo))->movimentacao->proposta->regime->regime,
        (new Processo($data->processo))->id_despacho,
        $data->dias_consumo,
        $data->dta_inicio,
        $itens
      );
      return ($itens);
    }

    if ($lotes = $processo->isLote()) {
      foreach ($lotes as $lote) {
        $liberacao = $lote->captacao->liberacao;
        $itens = $this->getItem(
            $liberacao->valor_mercadoria, 
            'processo', 
            $lote->captacao->proposta->regime->regime,
            $lote->captacao->id_captacao,
            $data->dias_consumo,
            $data->dta_inicio,
            $itens
        );
      };
      return ($itens);
    } else {
      $itens = $this->getItem(
        (new Processo($data->processo))->captacao->liberacao->valor_mercadoria,
        'processo', 
        (new Processo($data->processo))->captacao->proposta->regime->regime,
        (new Processo($data->processo))->id_captacao,
        $data->dias_consumo,
        $data->dta_inicio,
        $itens
      );
      return ($itens);
    }

    self::closeTransaction();
  }

  /**
   * Metodo que busca os itens padroes
   * @param String $valor_mercadoria, $modulo
   */
  private function getItem($valor_mercadoria, $modulo, $regime, $operacao, $dias_consumo, $dta_inicio, array $itens) {
      $servico_master = new ItemPadrao;
      $servico_master->modulo = $modulo;
      $servico_master->regime = $regime;
      $servico_master->id_operacao = $operacao;
      $servico_master->dias_consumo = $dias_consumo;
      $servico_master->valor_mercadoria = $valor_mercadoria;
      if ($modulo !== 'fatura') {
        $servico_master->dta_inicio = $dta_inicio;
      }
      $servicos = $servico_master->servByOperacao();
      $captacao = [ 'captacao' => $operacao ] ;
      foreach ($servicos as $item) {
        $item['id_captacao'] = $operacao;
        $captacao['itens'][]= $item;
      }
      $itens['all'][] = $captacao;
      return isset($itens) ? $itens : null;
  }

  /**
   * Metodo para organizar os itens e fazer uma junção dos itens que são comuns entre todas as captacoes caso for lote
   */
  private function commomItem(array $itens) {
    $item_collection = []; // Coleção de itens que já foram verificados
    foreach ($itens['all'] as $captacao => &$item) {
      foreach ($item as $key => $ite) {
        if (count($item_collection) > 0)
          if ($this->checkItemIsInCollection($item_collection, $ite)) {
            $itens['common'][] = $item[$key]; 
            unset($item[$key]);
          }
      }
      $item_collection[$captacao] = $item;
    }
    print_r($itens);
  }

  /**
   * Metodo para verificar se o iten atual existe nos itens anteriores
   * @param array $item_collection Coleção de itens já verificado
   * @param array $item item atual
   */
  private function checkItemIsInCollection(array $item_collection, array $item_currency) {
    $in_all = false; // Variavel que confirma que o item pertence a todos os outros
    foreach ($item_collection as $captacao => $itens) {
      foreach ($itens as $item) {
        if ($item['id_predicado'] === $item_currency['id_predicado']); {
          $in_all = true;
          break;
        }
        $in_all = false;
      }
    }
   return $in_all;
  }

}

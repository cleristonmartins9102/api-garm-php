<?php
namespace App\Model\Fatura;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

use App\Model\Processo\Processo;
use App\Model\Cext\Cext;
use App\Model\Proposta\PropostaPredicado;
use App\Model\Comissionario\Comissao;
use App\Model\Comissionario\Comissionario;
use App\Model\Comissionario\FaturaComissoesDesativadas;
use App\Model\Captacao\Captacao;
use App\Model\Despacho\Despacho;
use App\Model\Pessoa\Individuo;
use App\Model\Documento\Upload;


class VwFatura extends Record
{
    const TABLENAME = 'VwFatura';
    private $eventos = [];

    public function get_processo() {
        return new Processo($this->id_processo);
    }
    public function calcValotTotal()
    {      
        $valor_total = 0;
        $valor_custo = 0;
        foreach ($this->allItens as $key => $item) {
            // Calculando os valores dos itens
            $valor_total += is_numeric($item['valor_item']) ?  $item['valor_item'] : 0;
            // Calculando os valores custos dos itens
            $valor_custo += is_numeric($item['valor_custo']) ?  $item['valor_custo'] : 0;
        }
        $this->valor = round($valor_total, 2);
        $this->valor_custo = round($valor_custo, 2);
        
        // Calculando comissoes
        self::get_comissoes();
    }
     /**
     * Metodo para buscar se a comissão do despachante esta desativada
     */
    public function buscaStatusComissaoDespachante() {
        if (!$this->despachante_comissionado instanceof Comissionario && (!isset($this->despachante_comissionado->id_comissionario) or is_null($this->despachante_comissionado->id_comissionario)))
            return;
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura ?? $this->id));
        $criteria->add(new Filter('id_comissionario', '=', $this->despachante_comissionado->id_comissionario));
        $repository = (new Repository(FaturaComissoesDesativadas::class))->load($criteria);
        if (count($repository) === 0) 
            return true;
        return false;
    }

    public function get_comissoes() {
        $comissao = new Comissao;
        $comissao->ativar_despachante = self::buscaStatusComissaoDespachante();

        $comissao->byFatura($this);
        
        $this->comissao = [ 
                'valor_comissao_total' => $comissao->get_valor_total(),
                'despachante' => $comissao->get_valor_despachante(),
                'vendedor'    => $comissao->get_valor_vendedor()
        ];
        // Propriedade para mostrar o valor das comissões na View
        $this->vl_com_tot = $comissao->get_valor_total();

        // $this->margem_comissao = (($this->valor - $comissao->get_valor_total()) / $this->valor) * 100;
        return $this->comissao;
    }

    // Metodo para buscar se existe um despachante comissionario, se não existir, retorna um array vazio.
    public function get_despachante_comissionado() {
        $movimentacao = $this->processo->movimentacao;
        if ($movimentacao instanceof Captacao or $movimentacao instanceof Despacho) {
            $despachante = $movimentacao->despachante;
            $comissionario = new Comissionario;
            $comissionario('id_comissionado', $despachante->id_individuo);
            if (isset($comissionario->id_comissionario) and !is_null($comissionario->id_comissionario)) {
              return $comissionario;
            } else {
              return [];
            }
        }
    }

    public function get_documento()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura));
        $object = (new Repository(FaturaDocumento::class))->load($criteria);
        $documentos = array();

        // Verificando se encontrou documentos
        if (count($object) > 0) {
            $documentos = array();
            // Percorrendo o array com os objetos para pegar os objetos
            foreach ($object as $key => $captacao_upload_value) {
                $id_upload = $captacao_upload_value->id_upload;
                $upload = new Upload($id_upload);
                $upload->id_tipodocumento = $upload->tipo_documento->id_tipodocumento;
                $upload->tipodocumento = $upload->tipo_documento->nome;
                $upload->removeProperty(['id_bucket', 'nome_sistema', 'localizacao', 'validado']);
                $documentos[] = $upload->toArray();
            }
        }
        return $documentos;

    }

          /**
     * Metodo para adicionar um novo evento na captação
     * @param string $evento
     * @param string $app_forward
     * @param string $app
     */
    public function get_evento()
    {
        // $this->eventos[] = $this->captacao->eventos;
        // $this->eventos[] = $this->captacao->liberacao->eventos;
        // $this->eventos[] = $this->processo->eventos;
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura));
        $repository = new Repository(FaturaEvento::class);
        $object = $repository->load($criteria);
        foreach ($object as $key => $evento) {
            // print_r($evento->toArray());
            // exit();
            $this->eventos[] = $evento->toArray();
        }

        
        return $this->eventos;
    }
    
    public function get_allItens()
    {
        $this->valor_imposto_c = 0;
        $this->valor_despesa_c = 0;

        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura ?? $this->id));
        $repository = new Repository('App\Model\Fatura\FaturaItem');
        foreach ($repository->load($criteria) as $key => $item) {
            $item->unidade = (new PropostaPredicado($item->id_propostapredicado))->unidade;
            // Verificando se o item é despesa ou imposto
            if (($item->servico === 'impostos' || $item->servico === 'Impostos')) {
                $this->valor_imposto_c += $item->valor_item;
            } else {
                $this->valor_despesa_c += is_numeric($item->valor_item) ? $item->valor_item : 0;
            }
            $item->moeda = $item->moeda->moeda;
            $item->servico = $item->servico;
            $itens[] = $item->toArray();
        }
        $this->itens = $itens ?? [];
        return $this->itens;
    }

    public function get_valor_lucro() {
        return ($this->valor - ($this->imposto_interno_valor + $this->valor_custo + $this->comissoes['valor_comissao_total']));
    }

    public function get_imposto_interno() {
        return $this->custo_extra->custo_valor_interno . '%';
    }

    public function get_imposto_interno_valor() {
        return round((($this->valor * $this->custo_extra->custo_valor_interno) / 100), 2);
    }

    public function get_custo_extra()
    {
        $cext = new Cext();
        $cext->getListaByTipo('fatura', 'interno'); 
        return $cext;
    }

    public function get_margem_lucro() {
      
        $val_com_imposto_interno = $this->valor - (($this->valor * $this->custo_extra->custo_valor_interno) / 100);
        if ((int) $val_com_imposto_interno === 0) {
           return 0;
       } else {    
           return round(($this->valor_lucro / $this->valor) * 100, 2);
       }
    }

    public function get_captacao() {
        return new Captacao($this->id_captacao);//
    }


    public function get_cliente() {
        return (new Individuo($this->id_cliente))->isLoaded() ? new Individuo($this->id_cliente) : null;
    }

    public function get_modelo_nome() {
        return (new FaturaModelo($this->id_faturamodelo ?? null))->isLoaded() ? (new FaturaModelo($this->id_faturamodelo))->nome : null;
    }

       /**
     * Metodo que verifica se a fatura é uma complementar
     */
    public function isComplementar() {
        $fat = (new FaturaComplementar)('id_faturacomplementar', $this->id ?? $this->id_fatura);
        $this->isC = !is_array($fat) ? $fat->isLoaded() : false;
        return $this->isC;
    }

    /**
     * Metodo que verifica se a fatura é cheia, casos complementares
     */
    public function isCheia() {
        return $this->cheia === 'sim' ? true : false;
    }
}

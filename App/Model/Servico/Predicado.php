<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;
use App\Model\Regime\Regime;
use App\Model\Fatura\FaturaItemValorLoteIntegral;
use App\Model\Fatura\FaturaItemValorLoteIntegraleRateado;

class Predicado extends Record
{
    const TABLENAME = "Predicado";

    public function allByRegime()
    {
        if (isset($this->id_regime)) {
            $criteria = new Criteria;
            $criteria->add(new Filter('regime', '=', 'ambos'));
            $regime_ambos = (new Repository('App\Model\Regime\Regime'))->load($criteria);
            $criteria->clean();
            if (count($regime_ambos)) {
                $criteria->add(new Filter('id_regime', '=', $regime_ambos[0]->id_regime));
            }
            $criteria->add(new Filter('id_regime', '=', $this->id_regime), $criteria::OR_OPERATOR);
            $repository = (new Repository(get_class()))->load($criteria);
            return $repository;
        }
    }


    public function set_enableUtil()
    {
        $this->utilizado = 'yes';
    }

    public function get_checkUtilizado()
    {
        return $this->utilizado;
    }


    /**
     * Metodo que verifica se o predicado é necessário estar na proposta ou não
     */
    public function get_checkNeedInProposta()
    {
        // Verifica se possui um modulo definido
        if (isset($this->modulo)) {
            $criteria = new Criteria;
            $id_modulo = null;
            // Verificando se o modulo foi passado por nome
            if (is_string($this->modulo)) {
                $criteria->add(new Filter('nome', '=', $this->modulo));
                $repository = new Repository('App\Model\Aplicacao\Modulo');
                if ($repository->load($criteria) > 0) {
                    $id_modulo = $repository->load($criteria)[0]->id_modulo;
                }
            } elseif (is_numeric($this->modulo)) { // Verifica se o modulo foi passado por id
               $id_modulo = $this->modulo;
            }
            // Se passou pelas validações acima
            if (!is_null($id_modulo)) {
                $criteria->clean();
                $criteria->add(new Filter('id_predicado', '=', $this->id));
                $criteria->add(new Filter('id_modulo', '=', $id_modulo));
                $repository = new Repository('App\Model\Servico\PreNoProInMod');
                if ($repository->load($criteria) > 0) {
                    return false;
                } else {
                    return true;
                }
            } else {
                echo 'Sem modulo definido';
                exit();
            }
        }
    }

    /**
     * Metodo que verifica se o predicado é um pacote, se for ele retorna o pacote
     * @return PacotePredicado retorna o objeto pacote
     */
    public function isPacote() {
        if ($this->servico->nome === 'Pacote') {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_predicado', '=', $this->id_predicado));
            $pacote = (new Repository(Pacote::class))->load($criteria); 
        }

        return count($pacote) > 0 ? $pacote[0] : 0;
    }

    public function get_servico()
    {
        return new Servico($this->id_servico);
    }

    public function get_adicional()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicadomaster', '=', $this->id_predicado));
        $criteria->add(new Filter('tipo', '=', 'excesso_valormercadoria'));
        $repository = new Repository('App\Model\Servico\ItemCondicional');
        $adicional = $repository->load($criteria);
        if (count($adicional) > 0) {
            return new Predicado($adicional[0]->id_predicadocondicionado);
        }
        return new Predicado;
    }

    // public function get_itemmaster()
    // {
    //     $criteria = new Criteria;
    //     $criteria->add(new Filter('id_predicadoslave', '=', $this->id_predicado));
    //     $repository = new Repository('App\Model\Servico\ItemMaster');
    //     $adicional = $repository->load($criteria);
    //     if (count($adicional) > 0) {
    //         return new Predicado($adicional[0]->id_predicadocondicionado);
    //     }
    //     return new Predicado;
    // }

    public function get_itemmaster()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicadoslave', '=', $this->id_predicado));
        $repository = new Repository('App\Model\Servico\ItemMaster');
        $master = $repository->load($criteria);
        if (count($master) > 0) {
            return new Predicado($master[0]->id_predicadomaster);
        }
        return new Predicado;
    }

    public function get_regime() {
        return new Regime($this->id_regime);
    }

    // Metodo que verifica se o predicado esta sendo usado, se ele faz parte de algum grupo de contato e se pode ser exluido
    public function checkIsDeletable()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicado', '=', $this->id_predicado));
        $repository_pro = new Repository('App\Model\Proposta\PropostaPredicado');
        $repository_pac = new Repository('App\Model\Servico\PacotePredicado');
        $response_pro = $repository_pro->load($criteria);
        $response_pac = $repository_pac->load($criteria);
        if (count($response_pro) > 0 || count($response_pac) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Metodo que verifica que tipo de cobranca é feita para esse predicado
     * Se é cobrado um valor integral, rateado, ou integral + rateado
     * @return string Retorna o tipo de cobrança
     */
    public function isProrate():string {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_predicado', '=', $this->id_predicado));
        // $act = FaturaItemValorLoteIntegraleRateado::class;
        // echo constant($act . '::TABLENAME');
        // exit();
        $repository = (new Repository(FaturaItemValorLoteIntegraleRateado::class))->load($criteria);
        if ( count($repository) > 0 )
            return 'int_e_rat';

        $repository = (new Repository(FaturaItemValorLoteIntegral::class))->load($criteria);
        if ( count($repository) > 0 )
            return 'int';

        return 'rat';
    }
}

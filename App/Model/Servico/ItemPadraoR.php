<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;
use App\Model\Captacao\Captacao;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Fatura\Calculo\Armazenagem;
use App\Lib\Tool\Condition;
use App\Lib\Tool\Conditioner;
use App\Model\Despacho\Despacho;
use App\Model\Regime\Regime;
use App\Model\Fatura\Calculo\CalculoItem;
use App\Model\Aplicacao\Modulo;
use App\Lib\Tool\StoreDate;

/**
 * Essa classe é para em casos de processos do tipo armazenagem, pegar os itens do tipo armazenagem periodo e lancalos
 */
class ItemPadraoR extends Record
{
    const TABLENAME = 'ItemPadrao';
}

<?php

namespace App\Model\Fatura;

use App\Lib\Database\Record;
use App\Model\Processo\Processo;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Servico\Pacote;
use App\Model\Servico\Predicado;
use App\Model\Fatura\Calculo\CalcAppValor;
use App\Model\Pessoa\Individuo;
use App\Model\Moeda\Moeda;
use App\Model\Processo\ProcessoPredicado;
use App\Model\Servico\ItemPadrao;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Lib\Tool\Register;
use App\Model\Proposta\PropostaPredicado;
use App\Model\Cext\Cext;
use App\Model\Comissionario\Comissao;
use App\Model\Documento\Upload;
use App\Model\Comissionario\Comissionario;
use App\Model\Comissionario\FaturaComissoesDesativadas;

use App\Model\Captacao\Captacao;
use DateTime;

class FaturaDocumento extends Record
{
    const MANYTOMANY = 'true';
    const TABLENAME = 'FaturaDocumento';
}

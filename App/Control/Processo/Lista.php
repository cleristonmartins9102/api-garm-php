<?php

namespace App\Control\Processo;

use App\Infra\Factor\FactorReportLine;
use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Liberacao\Liberacao;
use App\Model\Operacao\VwOperacao;
use App\Model\Liberacao\LiberacaoDocumento;
use App\Model\Liberacao\LiberacaoStatus;
use App\Model\Processo\Processo;
use App\Model\Processo\VwProcesso;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\CaptacaoLote;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
  public function all(Request $request, Response $response, array $param = null)
  {
    try {
      self::openTransaction();
      $object = (new VwProcesso)->all();
      $dataFull = array();
      $dataFull['total_count'] = count($object);
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      $criteria = parent::criteria($param);
      $repository = new Repository('App\Model\Processo\VwProcesso');
      $object = $repository->load($criteria);
      foreach ($object as $idx => &$processo) {
        $complementos['itens'] = $processo->itens;
        if ($processo->isLote()) {
          $complementos['eventos'] = $processo->lote->eventos;
          $complementos['contêiner'] = (new CaptacaoLote($processo->lote->id_captacaolote))->container;
        } else {
          $complementos['contêiner'] = $processo->movimentacao->listacontainer;
          $complementos['eventos'] = $processo->eventos;

          // print_r($processo->movimentacao);
          // $complementos['eventos'] = $processo->movimentacao->eventos;
        }
        $processo->complementos = $complementos;
        $dataFull['items'][] = $processo->toArray();
      }
      self::closeTransaction();
    } catch (Exception $e) {
      echo $e->getMessage();
    }
    return isset($dataFull) ? $dataFull : null;
  }

  public function alldropdown(Request $request, Response $response)
  {
    try {
      self::openTransaction();
      $object = (new VwProcesso)->all();
      foreach ($object as $idx => &$processo) {
        $dataFull[] = $processo->toArray();
      }
      self::closeTransaction();
    } catch (Exception $e) {
      echo $e->getMessage();
    }
    return isset($dataFull) ? $dataFull : null;
  }

  public function byid(Request $request, Response $response, $id_processo = null)
  {
    try {
      self::openTransaction();
      $processo_obj = new Processo($id_processo);
      $processo_obj->isLote = false;
      $processo_obj->isDespacho = false;
      if (empty($processo_obj->id_despacho)) {
        // $processo_obj->id_proposta = $processo_obj->movimentacao->id_proposta;
        if ($lote = $processo_obj->isLote()) {
          $processo_obj->isLote = true;
          $processo_obj->dta_inicio = $processo_obj->isLote()[0]->captacao->dta_atracacao;
          $processo_obj->dta_final = $processo_obj->isLote()[0]->captacao->liberacao->dta_saida_terminal ??  $processo_obj->dta_final;
          $movimentacao = $processo_obj->isLote()[0]->captacao;
        } else {
          $movimentacao = $processo_obj->movimentacao;
          if (isset($movimentacao->id_captacao)) {
            $processo_obj->dta_inicio = $movimentacao->dta_atracacao;
            $processo_obj->valor_mercadoria = $movimentacao->liberacao->valor_mercadoria;
            if (is_null($processo_obj->dta_final))
              $processo_obj->dta_final = $movimentacao->liberacao->dta_saida_terminal;
          }
        }
      } else {
        $movimentacao = $processo_obj->movimentacao;
        $processo_obj->isDespacho = true;
      }

      // Busca os itens da proposta
      $proposta_servico = $movimentacao->proposta->servico;
      $prop_servicos = [];
      foreach ($proposta_servico as $key => $servico) {
        $prop_servicos[] = $servico->toArray();
        if (!is_null($servico->predicado->itemmaster->id));
        $prop_servicos[] = $servico->predicado->itemmaster->toArray();
      }
      $processo_obj->servico_proposta = $prop_servicos;
      $processo_obj->regime = $movimentacao->proposta->regime->regime;
      $processo_obj->id_regime = $movimentacao->proposta->regime->id_regime;

      $processo_obj->locked = false;
      if ($processo_obj->isFaturado())
        $processo_obj->locked = ($processo_obj->fatura[0]->enviadaParaCliente() and $processo_obj->fatura[0]->isCheia());

      $processo_arr = $processo_obj->toArray();

      $processo_arr['itens'] = $processo_obj->itens;

      self::closeTransaction();
    } catch (Exception $e) {
      echo $e->getMessage();
    }
    return json_encode(isset($processo_arr) ? $processo_arr : null);
  }

  public function bystatus(Request $request, Response $response, \stdClass $statusObj = null)
  {
    self::openTransaction();
    $criteria = new Criteria;
    $criteria->add(new Filter('status', '=', 'aguardando DI/DTA'));
    $id_liberacaostatus = (new Repository('App\Model\Liberacao\LiberacaoStatus'))->load($criteria);
    $criteria->clean();
    $criteria->add(new Filter('id_liberacaostatus', '!=', $id_liberacaostatus[0]->id_liberacaostatus));
    $liberacaoObj = (new Repository('App\Model\Liberacao\VwLiberacao'))->load($criteria);
    $dataFull['total_count'] = count($liberacaoObj);
    $dataFull['items'] = array();
    foreach ($liberacaoObj as $key => $liberacao) {
      $liberacaoArr = $liberacao->getData();
      $liberacaoArr['complementos']['documento'] = $liberacao->anexo;
      $liberacaoArr['complementos']['contêiner'] = $liberacao->container;
      $liberacaoArr['complementos']['relevantes'][] = [
        'dta_atracacao' => $liberacao->dta_atracacao,
        'dta_saida_terminal' => $liberacao->dta_saida_terminal,
      ];
      $dataFull['items'][] = $liberacaoArr;
    }
    self::closeTransaction();
    print_r($dataFull);
  }

  public function filtered(Request $request, Response $response, array $filter)
  {
    try {
      self::openTransaction();
      $dataFull = array();
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      $param['columns'] = (new VwProcesso())->getColTable();
      $criteria = parent::filterColunm($filter);
      $repository = new Repository('App\Model\Processo\VwProcesso');
      $object = $repository->load($criteria);
      $dataFull['total_count'] = count($repository->load(parent::filterColunm($filter, false)));

      foreach ($object as $idx => $processo) {
        $complementos['itens'] = $processo->itens;
        if ($processo->isLote()) {
          $complementos['eventos'] = $processo->lote->eventos;
          $complementos['contêiner'] = (new CaptacaoLote($processo->lote->id_captacaolote))->container;
        } else {
          $complementos['contêiner'] = $processo->movimentacao->listacontainer;
          $complementos['eventos'] = $processo->eventos;

          // print_r($processo->movimentacao);
          // $complementos['eventos'] = $processo->movimentacao->eventos;
        }
        $processo->complementos = $complementos;
        $dataFull['items'][] = $processo->toArray();
      }
      self::closeTransaction();
      return json_encode(isset($dataFull) ? $dataFull : null);
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function download(Request $request, Response $response, string $filter)
  {
    $header = [
      'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFF'],
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
      ],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => [
          'argb' => '0B5A80',
        ],
      ],
    ];
    $style = [
      'font' => [
        'bold' => false,
        'color' => ['argb' => '000000'],
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
      ],
    ];
    $filter = json_decode(base64_decode($filter), true);
    $processos = json_decode($this->filtered($request, $response, $filter), true);

    // CREATE A NEW SPREADSHEET + SET METADATA
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()
      ->setCreator('Company_Name');
    // ->setLastModifiedBy('YOUR NAME')
    // ->setTitle('Demo Document')
    // ->setSubject('Demo Document')
    // ->setDescription('Demo Document')
    // ->setKeywords('demo php spreadsheet')
    // ->setCategory('demo php file');

    // NEW WORKSHEET
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório de Procesos');
    self::openTransaction();
    if (isset($processos['items']) and count($processos['items']) > 0) {
      $sheet->getStyle('A1:F1')->applyFromArray($header);
      $sheet->getRowDimension('1')->setRowHeight(30);
      $sheet->setCellValue("A1", 'Número')->getColumnDimension('A')->setWidth(30);
      $sheet->setCellValue("B1", 'Regime')->getColumnDimension('B')->setWidth(30);
      $sheet->setCellValue("C1", 'Cliente')->getColumnDimension('C')->setWidth(50);
      $sheet->setCellValue("D1", 'IMO')->getColumnDimension('D')->setWidth(10);
      $sheet->setCellValue("E1", 'DDC')->getColumnDimension('E')->setWidth(10);
      $sheet->setCellValue("F1", 'Status')->getColumnDimension('F')->setWidth(20);
      $r = 2;
      foreach ($processos['items'] as $idx => $processo) {
        $processo_obj = (object) $processo;
        $data = [
          $processo_obj->identificador,
          $processo_obj->regime_legenda,
          $processo_obj->cliente_nome,
          $processo_obj->imo,
          $processo_obj->tipo_operacao,
          $processo_obj->status
        ];
        $factor = new FactorReportLine($sheet);
        $row = $factor->create($r, $data, $style);
        $r += $row['highRow'];
        $sheet = $row['sheet'];
        // break;
        // if ($idx == 4) break;
      }
      // exit();
      // exit();
      // Bordar geral
      // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

      $r +=  3;
      // $margem_lucro = round(($valor_lucro / $valor_total) * 100, 2) . '%';
      // $valor_total = 'R$' . number_format( $valor_total , 2, ',', '.');
      // $valor_lucro = 'R$' . number_format( $valor_lucro , 2, ',', '.');
      // $valor_custo = 'R$' . number_format( $valor_custo , 2, ',', '.');
      // $comissao_total = 'R$' . number_format( $comissao_total , 2, ',', '.');
      // $valor_imposto_interno = 'R$' . number_format( $valor_imposto_interno , 2, ',', '.');

      $sheet->getRowDimension($r)->setRowHeight(30);
      $sheet->setCellValue("A{$r}", 'Total de Processos:')->getStyle("A{$r}:B{$r}")
        ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
      $sheet->getStyle("A{$r}")->applyFromArray($header);

      $sheet->getStyle("B{$r}")
        ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
      $sheet->setCellValue("B{$r}", $processos['total_count']);

      // $idx = $idx + 1;
      // $sheet->getRowDimension($idx)->setRowHeight(30);
      // $sheet->setCellValue("A{$idx}", 'Valor Total:')->getStyle("A{$idx}:B{$idx}")
      //     ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
      // $sheet->getStyle("A{$idx}")->applyFromArray($header);
      // $sheet->getStyle("B{$idx}")
      //     ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
      // $sheet->setCellValue("B{$idx}", $valor_total);

    }

    // OUTPUT
    $writer = new Xlsx($spreadsheet);


    // OR FORCE DOWNLOAD
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Relatório de Processos.xlsx"');
    header('Cache-Control: max-age=0');
    header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    $writer->save('php://output');

    self::closeTransaction();
  }
}

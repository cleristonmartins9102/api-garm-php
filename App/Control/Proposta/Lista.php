<?php

namespace App\Control\Proposta;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Proposta\VwProposta;
use App\Model\Proposta\Proposta;
use App\Model\Pessoa\Contato;
use App\Model\Pessoa\Individuo;
use stdClass;
use Slim\Handlers\Strategies\RequestResponse;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\Servico\Predicado;
use App\UserCase\Proposta\PropostaTerminais\GetPropTerminais;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
  private $data;

  public function comum(Request $request, Response $response, array $param)
  {
    try {
      self::openTransaction();
      $criteria = parent::criteria($param);
      $criteria->add(new Filter('tipo', '<>', 'modelo'));
      $repository = new Repository('App\Model\Proposta\VwProposta');

      $object = $repository->load($criteria);

      $dataFull = array();
      $dataFull['total_count'] = count((new Proposta())->all());
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();

      foreach ($object as $idx => &$proposta) {
        $dataModificated = array();
        //Verificando se têm mais de um servico
        if (count($proposta->servico) > 0) {
          foreach ($proposta->servico as $key => $servico) {
            $servicoArr = $servico->getData();
            $servicoArr['predicado'] = $servico->predicado->nome;
            $dataModificated['complementos']['serviços'][] = $servicoArr;
          }
        }
        // Verificando se tem chave proposta preenchida
        if (isset($proposta->id_doc_proposta)) {
          $anexo = $proposta->anexo_proposta;
          $anexo->removeProperty(['id_bucket', 'localizacao', 'nome_sistema', 'url', 'validado']);

          $folder_idx = (strpos($proposta->anexo_proposta->localizacao, '/') + 1);
          if ($folder_idx)
            $folder = substr($proposta->anexo_proposta->localizacao, $folder_idx);

          $anexo->tipo = 'proposta';
          $dataModificated['complementos']['documento'][] = $anexo->toArray();
        }

        if (isset($proposta->id_aceite)) {
          $anexo = $proposta->anexo_aceite;
          $anexo->tipo = 'aceite';
          $dataModificated['complementos']['documento'][] = $anexo->toArray();
        }

        $clienteInfo = $proposta->cliente->getData();
        if ($proposta->id_contato > 0) {;
          $contato = (new Contato($proposta->id_contato))->getData();
          $clienteInfo = $proposta->cliente->getData();
          $clienteInfo['contato'] = $contato['nome'];
        }
        // Verificando parentela
        $criteria->clean();
        $dataModificated['complementos']['cliente'][] = $clienteInfo;
        // $dataModificated['complementos']['parente'][] = $proposta->parentela;
        $dataModificated['complementos']['vendedor'][] = $proposta->vendedor->getData();
        $dataFull['items'][] = array_merge($proposta->getData(), $dataModificated);
      }


      self::closeTransaction();
      return json_encode(isset($dataFull) ? $dataFull : null);
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function servico(Request $request = null, Response $response = null, $id)
  {
    self::openTransaction();
    $proposta = new Proposta($id['id_proposta']);
    $servicos = $proposta->servico;
    foreach ($servicos as $key => $servico) {
      $servico->appvalor = $servico->appvalor->nome;
      $predicado = new Predicado($servico->id_predicado);
      if (!is_null($predicado->itemmaster->id_predicado)) {
        $item = $predicado->itemmaster;
        $item->appvalor = $servico->appvalor->nome;
        $serv['items'][] = $item->toArray();
      }

      $serv['items'][] = $servico->toArray();
    }
    $predicados_total = (new Predicado)->all();
    foreach($predicados_total as $key => $item) {
      foreach($serv['items'] as $pred) {
        // print_r($pred);exit();
        if ($item->id_predicado === $pred['id_predicado'])
          $item->appvalor = $pred['appvalor'];
      }
      $data[] = $item->toArray();
    }
    usort($data, function ($item1, $item2) {
      return $item1['nome'] <=> $item2['nome'];
    });
    self::closeTransaction();
    return [ 'items' => $data] ?? [];
  }

  public function modelo(Request $request, Response $response, array $param)
  {
    try {
      self::openTransaction();
      $criteria = parent::criteria($param);
      $criteria->add(new Filter('tipo', '=', 'modelo'));
      $repository = new Repository('App\Model\Proposta\VwProposta');
      $object = $repository->load($criteria);
      $dataFull = array();
      $dataFull['total_count'] = count($object);
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      foreach ($object as $idx => &$value) {
        $dataModificated = array();
        //Verificando se têm mais de um servico
        if (count($value->servico) > 1) {
          foreach ($value->servico as $key => $servico) {
            $servicoArr = $servico->getData();
            $servicoArr['predicado'] = $servico->predicado->nome;
            $dataModificated['complementos']['serviços'][] = $servicoArr;
          }
        }
        // Verificando se tem chave proposta preenchida
        if (isset($value->id_doc_proposta)) {
          $anexo = $value->anexo_proposta->getData();
          $anexo['tipo'] = $value->anexo_proposta->tipo_documento->nome;
          $dataModificated['complementos']['documento'][] = $anexo;
        }
        if (isset($value->id_aceite)) {
          $anexo = $value->anexo_aceite->getData();
          $anexo['tipo'] = isset($value->anexo_aceite->tipo_documento) ? $value->anexo_aceite->tipo_documento->nome : null;
          $dataModificated['complementos']['documento'][] = $anexo;
        }
        $clienteInfo = $value->cliente->getData();
        if ($value->id_contato > 0) {;
          $contato = (new Contato($value->id_contato))->getData();
          $clienteInfo = $value->cliente->getData();
          $clienteInfo['contato'] = $contato['nome'];
        }
        $dataModificated['complementos']['cliente'][] = $clienteInfo;
        $dataModificated['complementos']['vendedor'][] = $value->vendedor->getData();
        $dataFull['items'][] = array_merge($value->getData(), $dataModificated);
      }

      self::closeTransaction();
      return json_encode(isset($dataFull) ? $dataFull : null);
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function alldropdown(Request $request, Response $response)
  {
    self::openTransaction();
    $criteria = new Criteria;
    $criteria->add(new Filter('status', '=', 'ativa'));
    $criteria->add(new Filter('tipo', '!=', 'modelo'));
    $repository = new Repository('App\Model\Proposta\Proposta');
    $object = $repository->load($criteria);
    $dataFull = array();
    $dataFull['total_count'] = count($object);
    $dataFull['items'] = array();
    foreach ($object as $key => $value) {
      $dataFull['items'][] = $value->getData();
    }
    foreach ($dataFull['items'] as $key => &$proposta) {
      $proposta['nome_cliente'] = (new Individuo($proposta['id_cliente']))->getData()['nome'];
    }
    self::closeTransaction();
    usort($dataFull['items'], function ($item1, $item2) {
      return $item2['id_proposta'] <=> $item1['id_proposta'];
    });
    return isset($dataFull) ? $dataFull : null;
  }

  public function byregime(Request $request, Response $response, array $regime)
  {
    self::openTransaction();
    $proposta = new Proposta;
    $proposta->id_regime = $regime['regime'];
    foreach ($proposta->byRegime as $key => $proposta) {
      $proposta->nome_cliente = $proposta->cliente->nome;
      $proposta->regime_classificacao = $proposta->id_regimeclassificacao ? " ({$proposta->regime_classificacao->classificacao})" : '';
      if ($proposta->dta_validade >= date('Y-m-d')) {
        $propostas[] = $proposta->toArray();
      }
    };
    self::closeTransaction();
    return $propostas ?? [];
  }

  public function byregter(Request $request, Response $response, $param)
  {
    self::openTransaction();
    $proposta = new Proposta;
    $proposta->id_regime = $param['regime'];
    $proposta->terminal = $param['terminal'];
    foreach ($proposta->byRegTer as $key => $proposta) {
      $proposta->nome_cliente = $proposta->cliente->nome;
      $proposta->regime_classificacao = $proposta->id_regimeclassificacao ? " ({$proposta->regime_classificacao->classificacao})" : '';
      if ($proposta->dta_validade >= date('Y-m-d')) {
        $propostas[] = $proposta->toArray();
      }
    };
    self::closeTransaction();
    return $propostas ?? [];
  }

  public function byid(Request $request, Response $response, $id)
  {
    self::openTransaction();
    $proposta = new Proposta($id);
    $dataFull = array();
    $dataFull['total_count'] = 1;
    $dataFull['items'] = array();
    $dataModificated['complementos'] = array();
    //Verificando se têm mais de um servico
    if (count($proposta->servico) > 0) {
      foreach ($proposta->servico as $key => $servico) {
        $servico->id_estado = $servico->estado->id ?? $servico->estado->id_estado;
        $cid = [];
        if ( count($cidades = $servico->cidade) > 0 ) {
          foreach($cidades as $cidade) {
            $cid[] = $cidade->id_cidade;
          }
        }
        $servico->cidade = $cid;
        $servicoArr = $servico->getData();
        $dataModificated['complementos']['documento']['proposta'] = $proposta->anexo_proposta->nome_original;
        $dataModificated['complementos']['documento']['aceite'] = $proposta->anexo_aceite->nome_original;
        $servicoArr['predicado'] = $servico->predicado->nome;
        $dataModificated['complementos']['serviços'][] = $servicoArr;
      }
    }
    $dataModificated['complementos']['cliente'][] = $proposta->cliente->getData();
    $dataModificated['complementos']['vendedor'][] = $proposta->vendedor->getData();
    $proposta->qualificacao = $proposta->qualificacao->nome;
    
    $getPropTerminais = new GetPropTerminais();
    $proposta->terminal = $getPropTerminais->get($id);
    $dataFull['items'][] = array_merge($proposta->getData(), $dataModificated);
    self::closeTransaction();
    return json_encode($dataFull);
  }

  public function find(Request $request, Response $response, $id = null)
  {
    if ($id != null) {
      Transaction::open('zoho');
      $object = new Individuo($id);
      Transaction::close();
      if (!empty($object->nome)) {
        return json_encode($this->prepare($object));
      } else {
        return null;
      }
    }
  }
  public function filtered(Request $request, Response $response, array $filter)
  {
    self::openTransaction();
    $criteria = parent::filterColunm($filter);
    $repository = new Repository('App\Model\Proposta\VwProposta');
    $object = $repository->load($criteria);
    $dataFull = array();
    $dataFull['total_count'] = count($repository->load(parent::filterColunm($filter, false)));

    $dataFull['items'] = array();
    $dataModificated['complementos'] = array();
    foreach ($object as $idx => &$value) {
      $dataModificated = array();
      //Verificando se têm mais de um servico
      if (count($value->servico) > 1) {
        foreach ($value->servico as $key => $servico) {
          $servicoArr = $servico->getData();
          $servicoArr['predicado'] = $servico->predicado->nome;
          $dataModificated['complementos']['serviços'][] = $servicoArr;
        }
      }
      // Verificando se tem chave proposta preenchida
      if (isset($value->id_doc_proposta)) {
        $anexo = $value->anexo_proposta->getData();
        // $anexo['tipo'] = $value->anexo_proposta->tipo_documento->getData()['nome'];
        $dataModificated['complementos']['documento'][] = $anexo;
      }
      if (isset($value->id_aceite)) {
        $anexo = $value->anexo_aceite->getData();
        $anexo['tipo'] = isset($value->anexo_aceite->tipoDocumento) ? $value->anexo_aceite->tipoDocumento->getData()['nome'] : null;
        $dataModificated['complementos']['documento'][] = $anexo;
      }
      $clienteInfo = $value->cliente->getData();
      if ($value->id_contato > 0) {;
        $contato = (new Contato($value->id_contato))->getData();
        $clienteInfo = $value->cliente->getData();
        $clienteInfo['contato'] = $contato['nome'];
      }
      $dataModificated['complementos']['cliente'][] = $clienteInfo;
      $dataModificated['complementos']['vendedor'][] = $value->vendedor->getData();
      $dataFull['items'][] = array_merge($value->getData(), $dataModificated);
    }
    self::closeTransaction();
    return json_encode(isset($dataFull) ? $dataFull : null);
  }

  public function itens(Request $request, Response $response, $id_proposta = null)
  {
    self::openTransaction();
    $proposta = new Proposta($id_proposta);
    $itens = $proposta->servico;
    foreach ($itens as $key => $item) {
      $item_arr[] = $item->toArray();
    }
    self::closeTransaction();
    return json_encode($item_arr);
  }

  public function download(Request $request, Response $response, string $filter) {
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
    $row = [
        'font' => [
            'bold' => false,
            'color' => ['argb' => '000000'],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];

    $filter = json_decode(base64_decode($filter), true);
    $propostas = json_decode($this->filtered($request, $response, $filter), true);

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
    $sheet->setTitle('Relatório de Propostas');
    self::openTransaction();
    if ( isset($propostas['items']) and count($propostas['items']) > 0) {
        $sheet->getStyle('A1:J1')->applyFromArray($header);
        $sheet->getRowDimension('1')->setRowHeight(30);
        $sheet->setCellValue("A1", 'Número')->getColumnDimension('A')->setWidth(30);
        $sheet->setCellValue("B1", 'Importador')->getColumnDimension('B')->setWidth(50);
        $sheet->setCellValue("C1", 'Tipo')->getColumnDimension('C')->setWidth(20);
        $sheet->setCellValue("D1", 'Data de Emissão')->getColumnDimension('D')->setWidth(20);
        $sheet->setCellValue("E1", 'Data de Validade')->getColumnDimension('E')->setWidth(20);
        $sheet->setCellValue("F1", 'Prazo de Pagamento')->getColumnDimension('F')->setWidth(10);
        $sheet->setCellValue("G1", 'Regime')->getColumnDimension('G')->setWidth(20);
        $sheet->setCellValue("H1", 'Qualificação')->getColumnDimension('H')->setWidth(20);
        $sheet->setCellValue("I1", 'Status')->getColumnDimension('I')->setWidth(20);
        $sheet->setCellValue("J1", 'Vendedor')->getColumnDimension('J')->setWidth(30);
        $contagem = [
            'tipo' => [
              'nova' => 0,
              'renovação' => 0
            ],
            'regime' => [
              'importação' => 0,
              'transporte' => 0,
              'exportação' => 0,
              'ambos' => 0
            ],
            'qualificacao' => [
              'marítimo' => 0,
              'aéreo' => 0,
              'rodoviário' => 0
            ],
            'status' => [
              'ativa' => 0,
              'inativa' => 0,
              'cancelada' => 0
            ]
        ];
        $line = 1;
        foreach ($propostas['items'] as $idx => $proposta) {
            // if ( $proposta['tipo'] === 'modelo' )
            //   continue;
            $line++;
            $proposta_obj = (object) $proposta;

            $proposta = new Proposta($proposta_obj->id_proposta);

            // if ( $proposta_obj->tipo === 'modelo' )
            //   $contagem['tipo']['modelo']++;

            if ( $proposta_obj->tipo === 'nova' )
              $contagem['tipo']['nova']++;

            if ( $proposta_obj->tipo === 'renovação' )
              $contagem['tipo']['renovação']++;

            if ( $proposta_obj->regime === 'Importação' )
              $contagem['regime']['importação']++;

            if ( $proposta_obj->regime === 'Exportação' )
              $contagem['regime']['exportação']++;

            if ( $proposta_obj->regime === 'transporte' )
              $contagem['regime']['transporte']++;

            if ( $proposta_obj->regime === 'ambos' )
              $contagem['regime']['ambos']++;

            if ( $proposta_obj->qualificacao === 'maritimo' )
              $contagem['qualificacao']['marítimo']++;

            if ( $proposta_obj->qualificacao === 'aereo' )
              $contagem['qualificacao']['aéreo']++;

            if ( $proposta_obj->qualificacao === 'rodoviario' )
              $contagem['qualificacao']['rodoviário']++;

            if ( $proposta_obj->status === 'ativa' )
              $contagem['status']['ativa']++;

            if ( $proposta_obj->status === 'inativa' )
              $contagem['status']['inativa']++;

            if ( $proposta_obj->status === 'cancelada' )
              $contagem['status']['cancelada']++;

            $sheet->setCellValue("A{$line}", $proposta_obj->numero)->getStyle("A{$line}")->applyFromArray($row);
            $sheet->setCellValue("B{$line}", $proposta_obj->cliente)->getStyle("B{$line}")->applyFromArray($row);
            $sheet->setCellValue("C{$line}", $proposta_obj->tipo)->getStyle("C{$line}")->applyFromArray($row);
            $sheet->setCellValue("D{$line}", date('d/m/Y', strtotime($proposta_obj->dta_emissao)))->getStyle("D{$line}")->applyFromArray($row);
            $sheet->setCellValue("E{$line}", date('d/m/Y', strtotime($proposta_obj->dta_validade)))->getStyle("E{$line}")->applyFromArray($row);
            $sheet->setCellValue("F{$line}", $proposta_obj->prazo_pagamento)->getStyle("F{$line}")->applyFromArray($row);
            $sheet->setCellValue("G{$line}", $proposta_obj->regime)->getStyle("G{$line}")->applyFromArray($row);
            $sheet->setCellValue("H{$line}", $proposta_obj->qualificacao)->getStyle("H{$line}")->applyFromArray($row);
            $sheet->setCellValue("I{$line}", $proposta_obj->status)->getStyle("I{$line}")->applyFromArray($row);
            $sheet->setCellValue("J{$line}", $proposta->vendedor->nome)->getStyle("J{$line}")->applyFromArray($row);
        }
        // Bordar geral
        // $sheet->getStyle("A1:F{$line}")->applyFromArray($allRow);

        // $margem_lucro = round(($valor_lucro / $valor_total) * 100, 2) . '%';
        // $valor_total = 'R$' . number_format( $valor_total , 2, ',', '.');
        // $valor_lucro = 'R$' . number_format( $valor_lucro , 2, ',', '.');
        // $valor_custo = 'R$' . number_format( $valor_custo , 2, ',', '.');
        // $comissao_total = 'R$' . number_format( $comissao_total , 2, ',', '.');
        // $valor_imposto_interno = 'R$' . number_format( $valor_imposto_interno , 2, ',', '.');
        $line++;
        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Total de Propostas:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);

        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $propostas['total_count']);

        $line++;
        $line++;
        foreach ($contagem['tipo'] as $tipo=>$qtd) {
          $sheet->getRowDimension($line)->setRowHeight(30);
          $sheet->setCellValue("A{$line}", ucfirst($tipo))->getStyle("A{$line}:B{$line}")
              ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
          $sheet->getStyle("A{$line}")->applyFromArray($header);

          $sheet->getStyle("B{$line}")
              ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
          $sheet->setCellValue("B{$line}", $qtd);
          $line++;
        }
        $line++;
        foreach ($contagem['regime'] as $regime=>$qtd) {
          $sheet->getRowDimension($line)->setRowHeight(30);
          $sheet->setCellValue("A{$line}", ucfirst($regime))->getStyle("A{$line}:B{$line}")
              ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
          $sheet->getStyle("A{$line}")->applyFromArray($header);

          $sheet->getStyle("B{$line}")
              ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
          $sheet->setCellValue("B{$line}", $qtd);
          $line++;
        }
        $line++;
        foreach ($contagem['qualificacao'] as $qualificacao=>$qtd) {
          $sheet->getRowDimension($line)->setRowHeight(30);
          $sheet->setCellValue("A{$line}", ucfirst($qualificacao))->getStyle("A{$line}:B{$line}")
              ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
          $sheet->getStyle("A{$line}")->applyFromArray($header);

          $sheet->getStyle("B{$line}")
              ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
          $sheet->setCellValue("B{$line}", $qtd);
          $line++;
        }
        $line++;
        foreach ($contagem['status'] as $status=>$qtd) {
          $sheet->getRowDimension($line)->setRowHeight(30);
          $sheet->setCellValue("A{$line}", ucfirst($status))->getStyle("A{$line}:B{$line}")
              ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
          $sheet->getStyle("A{$line}")->applyFromArray($header);

          $sheet->getStyle("B{$line}")
              ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
          $sheet->setCellValue("B{$line}", $qtd);
          $line++;
        }
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
      header('Content-Disposition: attachment;filename="Relatório de Propostas.xlsx"');
      header('Cache-Control: max-age=0');
      header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Cache-Control: cache, must-revalidate');
      header('Pragma: public');
      $writer->save('php://output');

      self::closeTransaction();
}
}

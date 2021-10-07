<?php

namespace App\Control\Captacao;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Aplicacao\Aplicacao;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\VwCaptacao;
use App\Model\Proposta\VwProposta;
use App\Model\Proposta\Proposta;
use App\Model\Pessoa\Individuo;
use stdClass;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
  private $data;

  public function all(Request $request, Response $response, array $param)
  {
    try {
      self::openTransaction();
      $object = (new VwCaptacao)->all();
      $dataFull = array();
      $dataFull['total_count'] = count($object);
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      $criteria = parent::criteria($param);
      $repository = new Repository('App\Model\Captacao\VwCaptacao');
      $object = $repository->load($criteria);
      foreach ($object as $idx => $captacao) {
        // if ($captacao->notificacao)
        $captacao->consent = $captacao->isItLockedEdit() ? 'r' : null;
        $proposta = new Proposta($captacao->id_proposta);
        $captacao->complementos = [
          'notificacao' => $captacao->notificacao,
          'eventos' => $captacao->eventos,
          'proposta' => [$proposta->toArray()],
          'container' => $captacao->container,
          'documentos' => $captacao->documento
        ];
        $captacao->containeres20 = '';
        $captacao->containeres40 = '';
        $captacao->containeresQtd20 = 0;
        $captacao->containeresQtd40 = 0;

        // Verificando quantidade de containeres de 20 e 40, criando as propriedades dos mesmos.
        foreach ($captacao->container as $key => $container) {
          if ($container['dimensao'] == 20) {
            $captacao->containeresQtd20++;
            $captacao->containeres20 .= $captacao->containeresQtd20 > 1 ? '<br>' . $container['codigo'] : $container['codigo'];
          } else {
            $captacao->containeresQtd40++;
            $captacao->containeres40 .= $captacao->containeresQtd40 > 1 ? '<br>' . $container['codigo'] : $container['codigo'];
          }
        }
        $dataFull['items'][] = $captacao->toArray();
      }
      self::openTransaction();
      return isset($dataFull) ? $dataFull : null;
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function mon(Request $request, Response $response, array $param)
  {
    try {
      self::openTransaction();
      //       $captacao = new Captacao(3663);
      // print_r($captacao);
      // exit();
      $dataFull['total_count'] = 0;
      if ( $filter = self::getFilter('mon') ) {
        $criteria1 = new Criteria;
        $criteria1->add($filter);
        $dataFull['total_count'] = count($captacoes = (new Repository(VwCaptacao::class))->load($criteria1));
      }

      // $criteria1->add(new Filter('id_status', '!=', '7'));
      // $criteria1->add(new Filter('id_captacao', 'NOT IN', '(SELECT id_captacao FROM CaptacaoEvento where  id_captacao IS NOT NULL and 
      // ( evento="g_liberacao" or 
      // evento="g_processo" or 
      // evento="g_fatura" ))', false));
      // $criteria1->clean();

      // $criteria1 = new Criteria;
      // $criteria1->add(new Filter('id_captacao', 'NOT IN', '(SELECT id_captacao FROM CaptacaoEvento")', false));

      $criteria = parent::criteria($param, $criteria1);
      $repository = new Repository('App\Model\Captacao\VwCaptacao');
      $object = $repository->load($criteria);
      foreach ($object as $idx => $captacao) {
        $captacao->notify_pres_carga = false;
        $data_created = [ 'valid' => false, 'created_at'];
        foreach ($captacao->eventos as $evento) {
          if ($evento['evento'] === 'confatracacao') {
            $data_created['valid'] = true;
            $data_created['created_at'] = $evento['created_at'];
          }
        }

        if (($data_created['valid'] and $captacao->extrato_terminal->isLoaded()) and $captacao->extrato_terminal->isLoaded() and strtotime($captacao->extrato_terminal->created_at) > strtotime($data_created['created_at'])) {
          $captacao->notify_pres_carga = true;
        }
        
        
        $captacao->previous_dta_prevista_atracacao = $captacao->previous_dta_prevista_atracacao;
        // echo '<pre>';
        // print_r($captacao->eventos);
        // print_r($captacao);
        // continue;
        $proposta = new Proposta($captacao->id_proposta);
        $complementos = [
          'notificacao' => $captacao->notificacao,
          'eventos' => $captacao->eventos,
          'proposta' => [$proposta->toArray()],
          'container' => $captacao->container,
          'documentos' => $captacao->documento
        ];
        if ($captacao->break_bulk === 'sim') {
          $complementos = array_merge($complementos, ['break_bulk_info' => [ $captacao->break_bulk_info ]]);
        } else {
          $captacao->break_bulk = 'nao';
        }
        $captacao->complementos = $complementos;
        
        $captacao->containeres20 = '';
        $captacao->containeres40 = '';
        $captacao->containeresQtd20 = 0;
        $captacao->containeresQtd40 = 0;
        // Verificando quantidade de containeres de 20 e 40, criando as propriedades dos mesmos.
        foreach ($captacao->container as $key => $container) {
          if ($container['dimensao'] == 20) {
            $captacao->containeresQtd20++;
            $captacao->containeres20 .= $captacao->containeresQtd20 > 1 ? '<br>' . $container['codigo'] : $container['codigo'];
          } else {
            $captacao->containeresQtd40++;
            $captacao->containeres40 .= $captacao->containeresQtd40 > 1 ? '<br>' . $container['codigo'] : $container['codigo'];
          }
        }
        $dataFull['items'][] = $captacao->toArray();
      }
      // exit();

      self::openTransaction();
      return isset($dataFull) ? $dataFull : null;
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function alldropdown(Request $request, Response $response)
  {
    self::openTransaction();
    $object = (new Captacao)->all();
    $dataFull = array();
    $dataFull['total_count'] = count($object);
    $dataFull['items'] = array();
    foreach ($object as $key => $value) {
      $captacaoArr['id_captacao'] = $value->id_captacao;
      $captacaoArr['numero'] = $value->numero;
      $captacaoArr['bl'] = $value->bl;
      $dataFull['items'][] = $captacaoArr;
    }
    self::closeTransaction();
    usort($dataFull['items'], function ($item1, $item2) {
      return $item2['id_captacao'] <=> $item1['id_captacao'];
    });
    return isset($dataFull) ? $dataFull : null;
  }

  public function dropdownParam(Request $request, Response $response, $param = null)
  {
    self::openTransaction();

    if ($param !== null) {
      $criteria = new Criteria;
      if (is_object($param)) {
        if (isset($param->notInLote) and $param->notInLote === true) {
          $captacoes = (new Captacao())->all();
          $dataFull = array();
          $dataFull['total_count'] = count($captacoes);
          $dataFull['items'] = array();
          foreach ($captacoes as $key => $captacao) {
            $captacaoArr['selected'] = false;
            if ($captacao->isInLote())
             $captacaoArr['selected'] = true;
             
            $captacaoArr['id_captacao'] = $captacao->id_captacao;
            $captacaoArr['numero'] = $captacao->numero;
            $captacaoArr['bl'] = ($captacao->bl ?? $captacao->mbl) ?? '';
            $dataFull['items'][] = $captacaoArr;
          }
        } else {
          $criteria->add(new Filter('status', '=', $param->status));
          $status_id = $repository = (new Repository('App\Model\Captacao\Status'))->load($criteria)[0]->id_captacaostatus;
          $criteria->clean();
          $criteria->add(new Filter('id_status', '=', $status_id));
          $repository = new Repository('App\Model\Captacao\Captacao');
          $object = $repository->load($criteria);
          $dataFull = array();
          $dataFull['total_count'] = count($object);
          $dataFull['items'] = array();
          foreach ($object as $key => $value) {
            $captacaoArr['id_captacao'] = $value->id_captacao;
            $captacaoArr['numero'] = $value->numero;
            $captacaoArr['bl'] = $value->bl ?? $value->mbl;
            $dataFull['items'][] = $captacaoArr;
          }
        }
      }
    self::closeTransaction();
    usort($dataFull['items'], function ($item1, $item2) {
      return $item2['id_captacao'] <=> $item1['id_captacao'];
    });
    return json_encode(isset($dataFull) ? $dataFull : null);
  }
 }

  public function byid(Request $request = null, Response $response = null, $id)
  {
    self::openTransaction();
    $captacao = new Captacao($id);
    $dataFull = array();
    $dataFull['total_count'] = 1;
    $dataFull['items'] = array();

    $complementos = [
      'notificacao' => $captacao->notificacao,
      'eventos' => $captacao->eventos,
      'documentos' => $captacao->documento,
      'terminal_atracacao' => [ $captacao->terminal_nome ]
    ];
    //Verificando se têm mais de um container
    if (count($captacao->container) > 0) {
      foreach ($captacao->container as $key => $container) {
        //Injetando os predicados no array
        $containeres[] = $container->toArray();
      }
      $complementos = array_merge($complementos, ['containeres' => $containeres ]);
    } else if (count($captacao->container) == 1) {
      //Injetando o predicado no array
      $complementos = array_merge($complementos, ['containeres' => [ $captacao->container[0]->toArray() ]]);
    }
   
    if ($captacao->break_bulk === 'sim') {
      $complementos = array_merge($complementos, ['break_bulk_info' => [ $captacao->break_bulk_info ]]);
    } else {
      $captacao->break_bulk = 'nao';
    }

    $captacao->complementos = $complementos;

    //Verificando se têm mais de um documento
    // if (count($captacao->documento) > 0) {
    //Injetando os documentos no arrayc
    // $dataModificated['complementos']['documentos'] = $captacao->documento;

    // } else if (count($captacao->documento) == 1) {
    //   //Injetando o documento no array
    //   $dataModificated['complementos']['documentos'][] = $documento[0]->getData();
    // }
    // $dataModificated['complementos']['terminal_redestinacao'][] = $captacao->terminal_redestinacao->individuo->toArray();
    // $dataModificated['complementos']['terminal_atracacao'][] = $captacao->terminal_atracacao->individuo->toArray();
    $dataFull['items'][] = $captacao->toArray();
    self::closeTransaction();
    return json_encode($dataFull);
  }

  public function mail(Request $request, Response $response, $id_captacao = null)
  {
    self::openTransaction();
    $captacao = new Captacao($id_captacao);
    $criteria = new Criteria;
    $criteria->add(new Filter('nome', '=', 'captacao'));
    $repository = new Repository('App\Model\Aplicacao\Aplicacao');
    $aplicacao = $repository->load($criteria);
    $aplicacao_contato = count($aplicacao) > 0 ? $aplicacao[0]->listademodulo[0]->contatos : null;
    
    // $contatos_do_grupo = $captacao->proposta->grupodecontato->contatos;
  
    // $contatos = $captacao->proposta->allgrupodecontato;
    $contatos = $captacao->allgrupodecontato;


    // if (count($contatos_do_grupo) > 0) {
    //   foreach ($contatos_do_grupo as $key => $contato) {
    //     $contatos[] = $contato->toArray();
    //   }
    // }
    // if ($aplicacao_contato)
    //   $contatos[] = $aplicacao_contato->toArray();
    
    self::closeTransaction();
    return isset($contatos) ? json_encode($contatos) : json_encode(array('sem contatos'));
  }

  /**
   * Metodo para buscar o body do email
   * @param number $id_captacao ID da Captação
   */
  public function boem(Request $request, Response $response, $data) {
    $data = (object) $data;

    if ( !isset($data->id_captacao) and is_null($data->id_captacao) or (!isset($data->bodyName) and is_null($data->bodyName) ))
      return 'Dados incompletos';

    self::openTransaction();
    $captacao = new Captacao($data->id_captacao);
    if (!$captacao->isLoaded())
      return 'Captação não carregada';

    switch ($data->bodyName) {
      case 'solbl':
        $email['subject'] = $captacao->subSolicitarBl($captacao);
        $email['body'] = $captacao->bodymail('bodySolBL');
        return $email;
        break;

      case 'solce':
        $email['subject'] = $captacao->subSolicitarCE($captacao);
        $email['body'] = $captacao->bodymail('bodySolCE');
        return $email;
        break;

      case 'confrecbl':
        $email['subject'] = $captacao->subConfRecBL($captacao);
        $email['body'] = $captacao->bodymail('bodyConfRecBL');
        return $email;
        break;

      case 'confcliente':
        $email['subject'] = $captacao->subConfCliente($captacao);
        $email['body'] = $captacao->bodymail('bodyConfirmarCliente');
        return $email;
        break;
      case 'altdtaatracacao':
        $email['subject'] = $captacao->subAltDtaAtracacao($captacao);
        $email['body'] = $captacao->bodymail('bodyAlteradoDtaAtracacao');
        return $email;
        break;
      case 'confatracacao':
        $email['subject'] = $captacao->subConfAtracacao($captacao);
        $email['body'] = $captacao->bodymail('bodyConfirmarAtracacao');
        $email['ocorrencias'] = $captacao->historico;
        return $email;
        break;
      case 'presencacarga':
        $email['subject'] = $captacao->subPresencaDeCarga($captacao);
        $email['body'] = $captacao->bodymail('bodyPresencaCarga');
        return $email;
        break;
      
      default:
        # code...
        break;
    }
    self::closeTransaction();
  }

  public function find($id = null)
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

  /**
   * Metodo para buscar o filtro especifico para diferenciar as Captações monitoradas das Gerais
   */
  private static function getFilter($method) 
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_status', '!=', '7'));
    if ( $method === 'mon' ) {
      // ( evento="g_liberacao" or 
      $criteria->add(new Filter('id_captacao', 'NOT IN', '(SELECT id_captacao FROM CaptacaoEvento where  id_captacao IS NOT NULL and 
          ( evento="g_processo" or 
          evento="g_fatura" ))', false));
      return $criteria;
    }
    return $criteria;
  }

  public function filtered(Request $request, Response $response, array $filter)
  {
    try {
      self::openTransaction();
      $dataFull = array();
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      $filter['columns'] = (new VwCaptacao())->getColTable();
      $criteria = parent::filterColunm($filter);
      
      $criteria->add(self::getFilter($filter['method']));

      $repository = new Repository('App\Model\Captacao\VwCaptacao');
      $object = $repository->load($criteria);
      $dataFull['total_count'] = count($object);
      foreach ($object as $idx => &$captacao) {
        if ( $filter['method'] !== 'mon' )
          $captacao->consent = $captacao->isItLockedEdit() ? 'r' : null;
        $captacao->previous_dta_prevista_atracacao = $captacao->previous_dta_prevista_atracacao;
        $dataModificated = array();
        $proposta = new Proposta($captacao->id_proposta);
        $prop = $proposta->getData();
        $dataModificated['complementos']['notificacao'] = $captacao->notificacao;
        $dataModificated['complementos']['eventos'] = $captacao->eventos;
        $dataModificated['complementos']['proposta'][] = $prop;
        $dataModificated['complementos']['container'] = $captacao->container;
 
        // Verificando se têm mais de um container
        if (count($captacao->container) > 0) {
          foreach ($captacao->container as $key => $container) {
            // Interando os predicados no arrayc
            $dataModificated['complementos']['containeres'][] = $container;
          }
        } else if (count($captacao->container) == 1) {
          // Interando o predicado no array
          $dataModificated['complementos']['containeres'][] = $captacao->container[0]->getData();
        }

        $dataModificated['complementos']['documentos'] = $captacao->documento;

        $dataFull['items'][] = array_merge($captacao->getData(), $dataModificated);
      }
      self::closeTransaction();
      return json_encode(isset($dataFull) ? $dataFull : null);
    } catch (Exception $e) {
      echo $e->getMessage();
    }
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
    $captacoes = json_decode($this->filtered($request, $response, $filter), true);
    
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
    \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder( new \PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder() );

    // NEW WORKSHEET
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório de Captações');
    self::openTransaction();
    if ( isset($captacoes['items']) and count($captacoes['items']) > 0) {
        $sheet->getStyle('A1:T1')->applyFromArray($header);
        $sheet->getRowDimension('1')->setRowHeight(30);
        $sheet->setCellValue("A1", 'Ref Company_Name')->getColumnDimension('A')->setWidth(15);
        $sheet->setCellValue("B1", 'Cliente')->getColumnDimension('B')->setWidth(50);
        $sheet->setCellValue("C1", 'Data')->getColumnDimension('C')->setWidth(20);
        $sheet->setCellValue("D1", 'CPNJ')->getColumnDimension('D')->setWidth(30);
        $sheet->setCellValue("E1", 'Despachante')->getColumnDimension('E')->setWidth(50);
        $sheet->setCellValue("F1", 'Transportadora')->getColumnDimension('F')->setWidth(50);
        $sheet->setCellValue("G1", 'Navio')->getColumnDimension('G')->setWidth(30);
        $sheet->setCellValue("H1", 'MBL')->getColumnDimension('H')->setWidth(20);
        $sheet->setCellValue("I1", 'HBL')->getColumnDimension('I')->setWidth(20);
        $sheet->setCellValue("J1", 'Contêiner 20')->getColumnDimension('J')->setWidth(30);
        $sheet->setCellValue("K1", 'Qtd')->getColumnDimension('K')->setWidth(20);
        $sheet->setCellValue("L1", 'Contêiner 40')->getColumnDimension('L')->setWidth(30);
        $sheet->setCellValue("M1", 'Qtd')->getColumnDimension('M')->setWidth(30);
        $sheet->setCellValue("N1", '1a atracação')->getColumnDimension('N')->setWidth(30);
        $sheet->setCellValue("O1", 'Redestinação')->getColumnDimension('O')->setWidth(30);
        $sheet->setCellValue("P1", 'Dta Envio Parceiro')->getColumnDimension('P')->setWidth(30);
        $sheet->setCellValue("Q1", 'Prev Atracação')->getColumnDimension('Q')->setWidth(20);
        $sheet->setCellValue("R1", 'Dta Atracação')->getColumnDimension('R')->setWidth(20);
        $sheet->setCellValue("S1", 'Confirmação Parceiro')->getColumnDimension('S')->setWidth(30);
        $sheet->setCellValue("T1", 'Observações')->getColumnDimension('T')->setWidth(30);

        $valor_total = 0;
        $valor_custo = 0;
        $valor_lucro = 0;
        $valor_imposto_interno = 0;
        $comissao_total = 0;
        foreach ($captacoes['items'] as $idx => $captacao) {
            $captacao_obj = (object) $captacao;
            $captacao = new Captacao($captacao_obj->id_captacao);
            // print_r($captacao_obj);
            // exit();
            $historico = '';
            $hist = $captacao->get_historico('ocacional');
            foreach ($hist as $id => $his) {
              $his = (object) $his;
              $ocorrencia = $his->ocorrencia;
              if ( $id > 0 )
                $historico .= "/";

              $historico .= $ocorrencia;
            }

            $idx = $idx+2;
            // if ( $fat->captacao->isLoaded() ) {
                $sheet->setCellValue("A{$idx}", $captacao_obj->numero)->getStyle("A{$idx}")->applyFromArray($row);
                $sheet->setCellValue("B{$idx}", $captacao_obj->cliente_nome)->getStyle("B{$idx}")->applyFromArray($row);

                $sheet->setCellValue("C{$idx}", 
                $captacao_obj->created_at
                  ? date("d/m/Y", strtotime($captacao_obj->created_at))
                  : null
                )->getStyle("C{$idx}")
                  ->applyFromArray($row)->getNumberFormat()
                  ->setFormatCode(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY
                  );
                $sheet->setCellValue("D{$idx}", $captacao->cliente_cnpj)->getStyle("D{$idx}")->applyFromArray($row);
                $sheet->setCellValue("E{$idx}", $captacao_obj->despachante)->getStyle("E{$idx}")->applyFromArray($row);
                $sheet->setCellValue("F{$idx}", $captacao->transportadora_nome)->getStyle("F{$idx}")->applyFromArray($row);
                $sheet->setCellValue("G{$idx}", $captacao->nome_navio)->getStyle("G{$idx}")->applyFromArray($row);
                $sheet->setCellValue("H{$idx}", $captacao->bl)->getStyle("H{$idx}")->applyFromArray($row);
                $sheet->setCellValue("I{$idx}", $captacao->mbl)->getStyle("I{$idx}")->applyFromArray($row);
                $sheet->setCellValue("J{$idx}", $captacao->container20)->getStyle("J{$idx}")->applyFromArray($row);
                $sheet->setCellValue("K{$idx}", $captacao->qtdcontainer['20'])->getStyle("K{$idx}")->applyFromArray($row);
                $sheet->setCellValue("L{$idx}", $captacao->container40)->getStyle("L{$idx}")->applyFromArray($row);
                $sheet->setCellValue("M{$idx}", $captacao->qtdcontainer['40'])->getStyle("M{$idx}")->applyFromArray($row);
                $sheet->setCellValue("N{$idx}", $captacao_obj->terminal_atracacao)->getStyle("N{$idx}")->applyFromArray($row);
                $sheet->setCellValue("O{$idx}", $captacao_obj->terminal_redestinacao)->getStyle("O{$idx}")->applyFromArray($row);
                $sheet->setCellValue("P{$idx}", 
                    count($status = $captacao->checkIfEnviadoAoTerminal()) > 0 
                    ? date('d/m/Y', strtotime($status[0]->created_at))
                    : null
                )->getStyle("P{$idx}")
                  ->applyFromArray($row)->getNumberFormat()
                  ->setFormatCode(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY
                  );
                
                
                $sheet->setCellValue("Q{$idx}", 
                    $captacao_obj->dta_prevista_atracacao
                    ? date('d/m/Y', strtotime($captacao_obj->dta_prevista_atracacao))
                    : null
                )->getStyle("Q{$idx}")
                  ->applyFromArray($row)->getNumberFormat()
                  ->setFormatCode(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY
                  );
                
                $sheet->setCellValue("R{$idx}", 
                    $captacao_obj->dta_atracacao
                    ? date('d/m/Y', strtotime($captacao_obj->dta_atracacao))
                    : null
                )->getStyle("R{$idx}")
                  ->applyFromArray($row)->getNumberFormat()
                  ->setFormatCode(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY
                  );

                $sheet->setCellValue("S{$idx}", 
                    count($status = $captacao->checkIfConfirmado()) > 0 
                    ? date('d/m/Y', strtotime($status[0]->created_at))
                    : null
                )->getStyle("S{$idx}")
                  ->applyFromArray($row)->getNumberFormat()
                  ->setFormatCode(
                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY
                  );
                
                
                $sheet->setCellValue("T{$idx}", $historico)->getStyle("T{$idx}")->applyFromArray($row);

                // $sheet->setCellValue("I{$idx}", 
                //     !is_null($fat->captacao->liberacao->tipo_documento) 
                //     ? $fat->captacao->liberacao->tipo_documento
                //     : null
                // )->getStyle("I{$idx}")->applyFromArray($row);

                // $sheet->setCellValue("J{$idx}", 
                //     !is_null($fat->captacao->liberacao->documento) 
                //     ? $fat->captacao->liberacao->documento
                //     : null
                // )->getStyle("J{$idx}")->applyFromArray($row);
                
                // $sheet->setCellValue("K{$idx}", $fat->valor)->getStyle("K{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("L{$idx}", $fatura_obj->valor_custo)->getStyle("L{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("M{$idx}", $fat->imposto_interno_valor)->getStyle("M{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("N{$idx}", $fat->valor_lucro)->getStyle("N{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("O{$idx}", $fat->captacao->qtdcontainer['20'])->getStyle("O{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("P{$idx}", $fat->captacao->container20)->getStyle("P{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("Q{$idx}", $fat->captacao->qtdcontainer['40'])->getStyle("Q{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("R{$idx}", $fat->captacao->container40)->getStyle("R{$idx}")->applyFromArray($row);


                // $sheet->setCellValue("T{$idx}", 
                //     ( !is_null($fat->captacao->liberacao->dta_saida_terminal) )
                //     ? date('d-m-Y', strtotime($fat->captacao->liberacao->dta_saida_terminal))
                //     : null
                // )->getStyle("T{$idx}")->applyFromArray($row);

                // $sheet->setCellValue("U{$idx}", 
                //     !is_null($fat->captacao->liberacao->valor_mercadoria) 
                //     ? $fat->captacao->liberacao->valor_mercadoria
                //     : null
                // )->getStyle("U{$idx}")->applyFromArray($row);

                // $sheet->setCellValue("V{$idx}", $fat->captacao->proposta->vendedor->nome)->getStyle("V{$idx}")->applyFromArray($row);
                // $sheet->setCellValue("W{$idx}", $fatura_obj->status)->getStyle("W{$idx}")->applyFromArray($row);
            // }
        }/////
        // Bordar geral
        // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

        $total_captacoes = $idx+3;
  

        $sheet->getRowDimension($total_captacoes)->setRowHeight(30);
        $sheet->setCellValue("A{$total_captacoes}", 'Total de Captações:')->getStyle("A{$total_captacoes}:B{$total_captacoes}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$total_captacoes}")->applyFromArray($header);

        $sheet->getStyle("B{$total_captacoes}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$total_captacoes}", $captacoes['total_count']);


    } 

    // OUTPUT
    $writer = new Xlsx($spreadsheet);


    // OR FORCE DOWNLOAD
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Captações.xlsx"');
    header('Cache-Control: max-age=0');
    header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    $writer->save('php://output');

    self::closeTransaction();
  }
}

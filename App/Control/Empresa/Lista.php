<?php
namespace App\Control\Empresa;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Expression;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Individuo;
use App\Model\Pessoa\VwIndividuo;
use App\Model\Pessoa\Endereco;
use App\Model\Pessoa\Papel;
use App\Model\Pessoa\IndividuoPapel;
use App\Model\Pessoa\PessoaFisica;
use App\Model\Pessoa\PessoaJuridica;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
  private $data;

  public function all(Request $request, Response $response, array $param = null)
  {
    try {
      self::openTransaction();
      $object = (new VwIndividuo)->all();
      $dataFull = array();
      $dataFull['total_count'] = count($object);
      $dataFull['items'] = array();
      if (!$param == null) {
        $criteria = parent::criteria($param);
        $repository = new Repository('App\Model\Pessoa\VwIndividuo');
        $object = $repository->load($criteria);
      }
      foreach ($object as $idx => $individuo) {
          $individuo->checkZero();
          $dataModificated['complementos'] = array();
          $papeis = [];
          if (count($individuo->papel) > 0) {
            foreach ($individuo->papel as $key => $papel) {
              $papeis[] = $papel->toArray();
            }
            $dataModificated['complementos']['papel'] = $papeis;
          }
          //Verificando se possui dependencia de objetos
          $endereco['logradouro'] = $object[$idx]->endereco->logradouro;
          $endereco['numero'] = $object[$idx]->endereco->numero;
          $endereco['bairro'] = $object[$idx]->endereco->bairro;
          $endereco['cidade'] = $object[$idx]->endereco->cidade->nome;
          $endereco['estado'] = $object[$idx]->endereco->estado->sigla;
          $endereco['cep'] = $object[$idx]->endereco->cep;
          $dataModificated['complementos']['endereco'] = array();
          $dataModificated['complementos']['contato'] = array();
          array_push($dataModificated['complementos']['endereco'], $endereco);
          $contatos = $individuo->contato;
          foreach ($contatos as $key => $contato) {
            $dataModificated['complementos']['contato'][] = $contato;
          }
          $individuo->complementos = $dataModificated['complementos'];
          $dataFull['items'][] = $individuo->toArray();
  
        } 
        if ($param == null) {
          usort($dataFull['items'], function ($item1, $item2) {
            return $item1['nome'] <=> $item2['nome'];
          });
        }

      self::closeTransaction();
      return isset($dataFull) ? $dataFull : null;

      //Converte o array para codigicacao utf8 e para json
      //return json_encode(array_merge($dataModificated, $this->prepare($object)));
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function bypapel(Request $request, Response $response, $papelNome)
  { 
      self::openTransaction();
      $criteria = new Criteria;

      if (is_array($papelNome) and is_array($papelNome['papeis'])) {
        foreach ($papelNome['papeis'] as $papel) {
          $criteria->add(new Filter('nome', '=', $papel), $criteria::OR_OPERATOR);
        }
      } else {
        $criteria->add(new Filter('nome', '=', $papelNome));
      }
      
      $papelObj = (new Repository('App\Model\Pessoa\Papel'))->load($criteria);

      $criteria->clean();
      foreach ($papelObj as $key => $papel) {
        $criteria->add(new Filter('id_papel', '=', $papel->id_papel), $criteria::OR_OPERATOR);
      }

      $criteria->addColunm(['id_individuo']);
      $criteria->setGroupBy('id_individuo');
      
      $repository = new Repository('App\Model\Pessoa\IndividuoPapel');
      $indPapelObj = $repository->load($criteria);
      $dataFull = array();
      $dataFull['total_count'] = count($indPapelObj);
      $dataFull['items'] = array();
      //Verificando se encontrou
      if (count($indPapelObj) > 0) {
        //Percorre por todo o objeto
        foreach ($indPapelObj as $key => $value) {
          //Instancia o Individuo
          $individuo = new Individuo($value->id_individuo);
          $dataFull['items'][] = $individuo->getData();
        }
        //Ordenando empresas por crescente
        usort($dataFull['items'], function ($item1, $item2) {
          return $item1['nome'] <=> $item2['nome'];
        });
      }
      self::closeTransaction();
      return json_encode($dataFull);
  }

  public function byid(Request $request, Response $response, $id = null)
  {
    if (is_null($id))
      return 'Sem id';

    $id = $id['id_individuo'];
    self::openTransaction();
    $empresa = new Individuo($id);

    // echo '<pre>';
    // print_r($empresa->toArray());
    // exit();
    $empresaArr = $empresa->toArray();
    $empresaArr['rg'] = $empresa->pessoa->rg ?? null;
    $empresaArr['ie'] = $empresa->pessoa->ie ?? null;
    $empresaArr = (self::removePropriety($empresaArr, ['id_endereco', 'created_at', 'updated_at']));
    $empresaArr['endereco'] = $empresa->endereco->toArray();
    $empresaArr['endereco'] = (self::removePropriety($empresaArr['endereco'], ['id_cidade', 'created_at', 'updated_at']));
    $empresaArr['endereco']['cidade'] = $empresa->endereco->cidade->toArray();
    // Coletando contatos
    foreach ($empresa->contato as $key => $contato) {
      // $contato['in_use'] = 'false';
      $empresaArr['contato'][] = $contato = (self::removePropriety($contato, ['created_at', 'updated_at']));
    }

    // Coletando papeis
    if (isset($empresa->papel) || count($empresa->papel) > 0) {
      foreach ($empresa->papel as $key => $papel) {
        $empresaArr['papel'][] = (self::removePropriety($papel->toArray(), ['created_at', 'updated_at']));
      }
    }
    $dataFull = $empresaArr;
    self::closeTransaction();
    return $dataFull ?? null;
  }

  public function find(Request $request, Response $response, $id = null)
  {
    if ($id != null) {
      self::openTransaction();
      $object = new Individuo($id);
      self::closeTransaction();
      if (!empty($object->nome)) {
        return json_encode($object->toArray());
      } else {
        return null;
      }
    }
  }

  public function filtered(Request $request, Response $response, array $filter)
  {
    try {
      if ( count($filter) > 0 and isset($filter['filter'])) {
        foreach ($filter['filter'] as $key => &$value) {
          if ($value['field'] === 'identificador')
            $value['field'] = 'id_individuo';
        }
      }

      self::openTransaction();
      $dataFull = array();
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      $param['columns'] = (new VwIndividuo())->getColTable();
      $criteria = parent::filterColunm($filter);

      $repository = new Repository('App\Model\Pessoa\VwIndividuo');
      $object = $repository->load($criteria);
      $dataFull['total_count'] = count($object);
      foreach ($object as $idx => &$individuo) {
        $individuo->checkZero();

        $dataModificated = array();
        $dataModificated['complementos'] = array();
        if (!$param == null) {
          $papeis = [];
          if (count($individuo->papel) > 0) {
            foreach ($individuo->papel as $key => $papel) {
              $papeis[] = $papel->toArray();
            }
            $dataModificated['complementos']['papel'] = $papeis;
          }
          //Verificando se possui dependencia de objetos
          $endereco['logradouro'] = $object[$idx]->endereco->logradouro;
          $endereco['numero'] = $object[$idx]->endereco->numero;
          $endereco['bairro'] = $object[$idx]->endereco->bairro;
          $endereco['cidade'] = $object[$idx]->endereco->cidade->nome;
          $endereco['estado'] = $object[$idx]->endereco->estado->sigla;
          $endereco['cep'] = $object[$idx]->endereco->cep;
          $dataModificated['complementos']['endereco'] = array();
          $dataModificated['complementos']['contato'] = array();
          array_push($dataModificated['complementos']['endereco'], $endereco);
          $contatos = $individuo->contato;
          foreach ($contatos as $key => $contato) {
            $dataModificated['complementos']['contato'][] = $contato;
          }
        }
        foreach ($individuo->getData() as $key => $value) {
          if ($value and $value != null) {
            // $value = is_string($value)?utf8_encode($value):$value;
            $dataModificated[$key] = $value;
          }
        }

        array_push($dataFull['items'], $dataModificated);
        if ($param == null) {
          usort($dataFull['items'], function ($item1, $item2) {
            return $item1['nome'] <=> $item2['nome'];
          });
        }
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

    $contato_style = [
        'font' => [
            'bold' => true,
            // 'color' => ['argb' => 'FFFFFF'],
        ],
        // 'alignment' => [
        //     'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        //     'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        // ],
        // 'borders' => [
        //     'allBorders' => [
        //         'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        //     ],
        // ],
        // 'fill' => [
        //     'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        //     'startColor' => [
        //         'argb' => '0B5A80',
        //     ],
        // ],
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
    $empresas = json_decode($this->filtered($request, $response, $filter), true);

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
    $sheet->setTitle('Relatório de Empresas');
    self::openTransaction();
    if ( isset($empresas['items']) and count($empresas['items']) > 0) {
        $sheet->getStyle('A1:I1')->applyFromArray($header);
        $sheet->getRowDimension('1')->setRowHeight(30);
        $sheet->setCellValue("A1", 'CNPJ')->getColumnDimension('A')->setWidth(30);
        $sheet->setCellValue("B1", 'Nome')->getColumnDimension('B')->setWidth(50);
        $sheet->setCellValue("C1", 'Tipo')->getColumnDimension('C')->setWidth(30);
        $sheet->setCellValue("D1", 'Cidade')->getColumnDimension('D')->setWidth(30);
        $sheet->setCellValue("E1", 'Estado')->getColumnDimension('E')->setWidth(30);
        $sheet->setCellValue("F1", 'Papel')->getColumnDimension('F')->setWidth(30);
        $sheet->setCellValue("G1", 'Contato')->getColumnDimension('G')->setWidth(30);
        $sheet->setCellValue("H1", 'Email')->getColumnDimension('H')->setWidth(30);
        $sheet->setCellValue("I1", 'Telefone')->getColumnDimension('I')->setWidth(30);
        $line = 1;

        // Quantidade de clientes por papeis
        $cont_papeis = [
          'despachante' => 0,
          'cliente' => 0,
          'importador' => 0,
          'exportador' => 0,
          'fornecedor' => 0,
          'agente_carga' => 0,
          'colaborador' => 0,
          'transportadora' => 0
        ];

        foreach ($empresas['items'] as $idx => $empresa) {
            $line++;
            $empresas_obj = (object) $empresa;
            $empresa = new Individuo($empresas_obj->id_individuo);
            $empresa->checkZero();

            $sheet->setCellValue("A{$line}", $empresa->identificador)->getStyle("A{$line}")->applyFromArray($row);
            $sheet->setCellValue("B{$line}", $empresas_obj->nome)->getStyle("B{$line}")->applyFromArray($row);
            $sheet->setCellValue("C{$line}", $empresas_obj->tipo)->getStyle("C{$line}")->applyFromArray($row);
            $sheet->setCellValue("D{$line}", $empresas_obj->cidade)->getStyle("D{$line}")->applyFromArray($row);
            $sheet->setCellValue("E{$line}", $empresas_obj->estado)->getStyle("E{$line}")->applyFromArray($row);
           
            // Cor na celula do papel
            // $sheet->setCellValue("B{$line}", 'Papel:')->getStyle("B{$line}")->applyFromArray($header)->getFill()
            //   ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            //   ->getStartColor()->setARGB('477895');;
            //

            $line_before_papel = $line;
            $count_papel = count($empresa->papel);
            $papel_size = $count_papel + $line_before_papel -1;

            // if ( $count_papel > 0 )
              // $sheet->mergeCells("B{$line_before_papel}:B{$papel_size}");

            $papel_nome = null;
            foreach($empresa->papel as $idx => $papel) {
              $delimiter = ( $idx <  ( count($empresa->papel) - 1 ) )  ? '/' : null;
              $papel_nome .= $papel->nome . $delimiter;
              if ( $papel->nome === 'cliente' ) 
                $cont_papeis['cliente']++;

              if ( $papel->nome === 'importador' ) 
                $cont_papeis['importador']++;

              if ( $papel->nome === 'exportador' ) 
                $cont_papeis['exportador']++;

              if ( $papel->nome === 'despachante' ) 
                $cont_papeis['despachante']++;

              if ( $papel->nome === 'agente_carga' ) 
                $cont_papeis['agente_carga']++;

              if ( $papel->nome === 'fornecedor' ) 
                $cont_papeis['fornecedor']++;

              if ( $papel->nome === 'transportadora' ) 
                $cont_papeis['transportadora']++;

              if ( $papel->nome === 'colaborador' ) 
                $cont_papeis['colaborador']++;

              // $sheet->mergeCells("C{$line}:E{$line}");
              // $line++;
            }
            $sheet->setCellValue("F{$line}", $papel_nome)->getStyle("C{$line}")->applyFromArray($row);


            $contato_nome = null;
            $contato_email = null;
            $contato_telefone = null;
            foreach($empresas_obj->complementos['contato'] as $idx => $contato) {
              // echo ( $idx === 0 and $idx <= count($empresas_obj->complementos['contato']));
              // echo count($empresas_obj->complementos['contato']) . ' ';
              $delimiter = ( $idx < ( count($empresas_obj->complementos['contato']) - 1 ) )  ? '/' : null;
              $contato = (object) $contato;
              $contato->ddd = ( isset($contato->ddd) and !is_null($contato->ddd) )? "({$contato->ddd})" : null;
              $contato->telefone = $contato->telefone ?? null;
              $contato_nome .=  $contato->nome  . $delimiter;
              $contato_email .= $contato->email . $delimiter;
              $contato_telefone .= "{$contato->ddd} {$contato->telefone}"  . ( ($contato->ddd and $contato->telefone ) ? $delimiter : null );
              // $line++;
            }
            $sheet->setCellValue("G{$line}", $contato_nome)->getStyle("C{$line}")->applyFromArray($row);
            $sheet->setCellValue("H{$line}", $contato_email)->getStyle("D{$line}")->applyFromArray($row);
            $sheet->setCellValue("I{$line}", $contato_telefone)->getStyle("D{$line}")->applyFromArray($row);
            // $line++;
            // $line++;

             // Cor na celula do papel
            //  $sheet->setCellValue("B{$line}", 'Contatos:')->getStyle("B{$line}")->applyFromArray($header)->getFill()
            //  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            //  ->getStartColor()->setARGB('477895');;
            //

            // $line_before_contato = $line;
            // $count_contato = count($empresas_obj->complementos['contato']);
            // $contato_size = $count_contato + $line_before_contato;

            // $sheet->setCellValue("C{$line}", 'Nome')->getStyle("C{$line}")->getFont()->setBold(true);
            // $sheet->setCellValue("D{$line}", 'Email')->getStyle("C{$line}")->applyFromArray($row);
            // $sheet->setCellValue("E{$line}", 'Telefone')->getStyle("E{$line}")->applyFromArray($row);
            // $sheet->getStyle("C{$line}:E{$line}")->applyFromArray($contato_style);

            // $line++;
            // foreach($empresas_obj->complementos['contato'] as $contato) {
            //   $contato = (object) $contato;
            //   $contato->ddd = ( isset($contato->ddd) and !is_null($contato->ddd) )? "({$contato->ddd})" : null;
            //   $contato->telefone = $contato->telefone ?? null;
            //   $sheet->setCellValue("C{$line}", $contato->nome)->getStyle("C{$line}")->applyFromArray($row);
            //   $sheet->setCellValue("D{$line}", $contato->email)->getStyle("D{$line}")->applyFromArray($row);
            //   $sheet->setCellValue("E{$line}", "{$contato->ddd} {$contato->telefone}")->getStyle("D{$line}")->applyFromArray($row);
            //   $line++;
            // }
           
            // $line = $line + 1;
        }

        // Bordar geral
        // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

        $idx = $idx+3;
        // $margem_lucro = round(($valor_lucro / $valor_total) * 100, 2) . '%';
        // $valor_total = 'R$' . number_format( $valor_total , 2, ',', '.');
        // $valor_lucro = 'R$' . number_format( $valor_lucro , 2, ',', '.');
        // $valor_custo = 'R$' . number_format( $valor_custo , 2, ',', '.');
        // $comissao_total = 'R$' . number_format( $comissao_total , 2, ',', '.');
        // $valor_imposto_interno = 'R$' . number_format( $valor_imposto_interno , 2, ',', '.');
        
        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Cliente:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['cliente']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Importador:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['importador']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Exportador:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['exportador']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Despachante:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['despachante']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Fornecedor:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['fornecedor']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Agente de Carga:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['agente_carga']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Colaborador:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['colaborador']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Transportadora:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $cont_papeis['transportadora']);

        $line++;
        $sheet->getRowDimension($line)->setRowHeight(30);
        $sheet->setCellValue("A{$line}", 'Total de Empresas:')->getStyle("A{$line}:B{$line}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$line}")->applyFromArray($header);
        $sheet->getStyle("B{$line}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$line}", $empresas['total_count']);

    } 

      // OUTPUT
      $writer = new Xlsx($spreadsheet);
  
  
      // OR FORCE DOWNLOAD
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="Relatório de Empresas.xlsx"');
      header('Cache-Control: max-age=0');
      header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Cache-Control: cache, must-revalidate');
      header('Pragma: public');
      $writer->save('php://output');

      self::closeTransaction();
  }
}

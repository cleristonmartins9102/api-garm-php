<?php
namespace App\Control\Grupodecontato    ;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Expression;
use App\Lib\Database\Filter;
use App\Lib\Database\LoggerTXT;
use App\Model\Pessoa\GrupoDeContatoNome;
use App\Model\Pessoa\VwGrupoDeContato;
use App\Model\Pessoa\GrupoDeContato;
use App\Model\Pessoa\Contato;
use App\Model\Pessoa\Individuo;
use stdClass;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{

    public function all(Request $request, Response $response, Array $param=null){
        try{
            self::openTransaction();
            $object = (new VwGrupoDeContato)->all();
            $dataFull = array();
            $dataFull['total_count'] = count($object);
            $dataFull['items'] = array();            
            if (!$param == null) {               
                $criteria = parent::criteria($param);
                $repository = new Repository('App\Model\Pessoa\VwGrupoDeContato');
                $object = $repository->load($criteria);
            }
      
            foreach ($object as $grupo) {
              $grupo_arr = $grupo->getData();
              $grupo_arr['complementos']['contato'] = $grupo->contatosDoGrupo;
              $dataFull['items'][] = $grupo_arr; 
            }
            self::closeTransaction();
            //Converte o array para codigicacao utf8 e para json
            return $dataFull;
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }
    }

    public function byid(Request $request, Response $response, $id = null)
    {   
        if (is_null($id))
          return 'Sem id';
        $id = $id['id_contato'];
        self::openTransaction();
        $grupo = new GrupoDeContato($id);
        $dataFull = $grupo->toArray();
        self::closeTransaction();
        return isset($dataFull)?$dataFull:array(array('erro' => 'sem grupo cadastrado', 'nome' => 'sem grupo cadastrado'));
    }

    public function byenvolvidos(Request $request, Response $response, stdClass $envolvidos)
    {
        // print_r($envolvidos);exit();
        self::openTransaction();
        $criteria = new Criteria;
        $criteria->add(new Filter('id_coadjuvante', '=', $envolvidos->coadjuvante));
        $criteria->add(new Filter('id_adstrito', '=', $envolvidos->adstrito), Expression::AND_OPERATOR);
        $object = (new Repository('App\Model\Pessoa\GrupoDeContato'))->load($criteria);
        if (count($object) > 0) {
            foreach ($object as $key => $grupo_contato) {
                $grupo_contato_arr = $grupo_contato->getData();
                $grupo_contato_arr['nome'] = (new GrupoDeContatoNome($grupo_contato->id_nome))->nome;
                $dataFull[] = $grupo_contato_arr;             
            }
        }
        self::closeTransaction();
        return json_encode(isset($dataFull)?$dataFull:array(array('erro' => 'sem grupo cadastrado', 'nome' => 'sem grupo cadastrado')));
    }

    public function filtered(Request $request, Response $response, Array $filter)
    {
      try {
        self::openTransaction();
        $dataFull = array();
        $dataFull['total_count'] = count((new VwGrupoDeContato)->all());
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
        $param['columns'] = (new VwGrupoDeContato())->getColTable();
        $criteria = parent::filterColunm($filter);

        $repository = new Repository('App\Model\Pessoa\VwGrupoDeContato');
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$grupo) {
            $grupo_arr = '';
            $grupo_arr = $grupo->getData();
            $grupo_arr['complementos']['contato'] = $grupo->contatosDoGrupo;
            $dataFull['items'][] = $grupo_arr;   
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
      $grupos_total = json_decode($this->filtered($request, $response, $filter), true);
      $grupos = [];
      // Organizando os grupos
      foreach($grupos_total['items'] as $grupo) {
        $grupos = self::organizar_grupo($grupos, $grupo);
      }
    
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
      $alphabet = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
      // NEW WORKSHEET
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setTitle('Relatório de Empresas');
      self::openTransaction();
      $sheet->getStyle('A3:C3')->applyFromArray($header);
      $sheet->getRowDimension('1')->setRowHeight(30);
      $sheet->setCellValue("D1", 'Grupos')->getColumnDimension('A')->setWidth(30);
      $sheet->setCellValue("A3", 'CNPJ')->getColumnDimension('A')->setWidth(30);
      $sheet->setCellValue("B3", 'Nome')->getColumnDimension('B')->setWidth(50);
      $sheet->setCellValue("C3", 'Coadjuvante')->getColumnDimension('C')->setWidth(50);

      if ( isset($grupos) and count($grupos) > 0) {
        
          // $sheet->getColumnDimension('D')->setWidth(20);
          // $sheet->getColumnDimension('E')->setWidth(20);
          // $line = 4;
  
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

          $nome_grupos = [ 
            'Captação',
            'Faturamento Geral',
            'Universal',
          ];

          $mapa = [
            'Faturamento Geral' => [
              'nome' => 'D',
              'email' => 'E',
              'telefone' => 'F',
            ],
            'Captação' => [
              'nome' => 'G',
              'email' => 'H',
              'telefone' => 'I',
            ],
            'Universal' => [
              'nome' => 'J',
              'email' => 'K',
              'telefone' => 'L',
            ],
          ];

          $col_init = 3;
          $line = 2;
          foreach ($mapa as $grupo => $conteudo) {
            $col = "$alphabet[$col_init]2:" . $alphabet[$col_init + 2] . '2';
            $sheet->getStyle("{$alphabet[$col_init]}{$line}")->applyFromArray($header);
            $sheet->setCellValue("{$alphabet[$col_init]}2", $grupo)->getStyle("{$alphabet[$col_init]}{$line}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells($col);
            $sheet->setCellValue("{$alphabet[$col_init]}3", 'Nome')->getColumnDimension("{$alphabet[$col_init]}")->setWidth(30);
            $sheet->getStyle("{$alphabet[$col_init]}3")->getFont()->setBold(true);
            $col_init++;
            $sheet->setCellValue("{$alphabet[$col_init]}3", 'Email')->getColumnDimension("{$alphabet[$col_init]}")->setWidth(30);
            $sheet->getStyle("{$alphabet[$col_init]}3")->getFont()->setBold(true);
            $col_init++;
            $sheet->setCellValue("{$alphabet[$col_init]}3", 'Telefone')->getColumnDimension("{$alphabet[$col_init]}")->setWidth(30);
            $sheet->getStyle("{$alphabet[$col_init]}3")->getFont()->setBold(true);
            $col_init++;
          }
          $line++;

          foreach ($grupos as $idx => $grupo) {
              $line++;
              $grupo_obj = (object) $grupo;
              $importador = new Individuo((int) $grupo_obj->adstrito);
              $importador->checkZero();
              $sheet->setCellValue("A{$line}", $importador->identificador)->getStyle("A{$line}")->applyFromArray($row);
              $sheet->setCellValue("B{$line}", $importador->nome)->getStyle("B{$line}")->applyFromArray($row);
              $sheet->setCellValue("C{$line}", $grupo_obj->coadjuvante)->getStyle("C{$line}")->applyFromArray($row);
              // $sheet->mergeCells("C1:E1");
              // $sheet->mergeCells("C${line}:E${line}");

              

              // $line++;
              // $line++;

              // $line_before_grupos = $line;
              // $sheet->setCellValue("B{$line}", 'Grupos:')->getStyle("B{$line}")->applyFromArray($header)->getFill()
              //   ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              //   ->getStartColor()->setARGB('477895');
              $col_init = 3;
              // $line = 3;
              foreach($grupo_obj->grupos as $gr) {
                // $sheet->setCellValue("{$alphabet[$col_init]}{$line}", $gr['nome'])->getStyle("C{$line}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;;
                // $sheet->setCellValue("{$alphabet[$col_init]}{$line}", 'Nome')->getColumnDimension("{$alphabet[$col_init]}")->setWidth(30);
                // $col_init++;
                // $sheet->setCellValue("{$alphabet[$col_init]}{$line}", 'Email')->getColumnDimension("{$alphabet[$col_init]}")->setWidth(30);
                // $col_init++;
                // $sheet->setCellValue("{$alphabet[$col_init]}{$line}", 'Telefone')->getColumnDimension("{$alphabet[$col_init]}")->setWidth(30);
                // $col_init++;
                $gr = (object) $gr;
                if (isset($gr->contatos)) {
                  $nome = null;
                  $email = null;
                  $telefone = null;
                  foreach($gr->contatos as $contato) {
                    $contato['ddd'] = ( isset($contato['ddd']) and !is_null($contato['ddd']) )? "({$contato['ddd']})" : null;
                    $contato['telefone'] = $contato['telefone'] ?? null;
                    $nome .= $contato['nome'] . '/';
                    $telefone .= "{$contato['ddd']} {$contato['telefone']}" . '/';
                    $email .= $contato['email'] . '/';                   
                  }
                  $cord = isset($mapa[$gr->nome]) ? $mapa[$gr->nome] : null;
                  // print_r("{$cord['telefone']}{$line}");
                  // exit();
                  if ( !is_null($cord['nome']) )
                    $sheet->setCellValue("{$cord['nome']}{$line}", $nome)->getColumnDimension('D')->setAutoSize(true);
                  
                  if ( !is_null($cord['email']) )  
                    $sheet->setCellValue("{$cord['email']}{$line}", $email)->getColumnDimension('E')->setAutoSize(true);

                  if ( !is_null($cord['telefone']) )  
                    $sheet->setCellValue("{$cord['telefone']}{$line}", $telefone)->getColumnDimension('F')->setAutoSize(true);;
                }
                //   $r = $line - 1;
                //   $sheet->mergeCells("B{$line_before_grupos}:B{$r}");
                //   $line++;
                // }
              }
              // exit();

  
              // $line_before_papel = $line;
              // $count_papel = count($empresa->papel);
              // $papel_size = $count_papel + $line_before_papel -1;
  
              // if ( $count_papel > 0 )
              //   $sheet->mergeCells("B{$line_before_papel}:B{$papel_size}");
  
              // foreach($empresa->papel as $papel) {
              //   $sheet->setCellValue("C{$line}", $papel->nome)->getStyle("C{$line}")->applyFromArray($row);
              //   $sheet->mergeCells("C{$line}:E{$line}");
              //   $line++;
              // }
              // $line++;
              // $line++;
  
              //  // Cor na celula do papel
              //  $sheet->setCellValue("B{$line}", 'Contatos:')->getStyle("B{$line}")->applyFromArray($header)->getFill()
              //  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              //  ->getStartColor()->setARGB('477895');;
              // //
  
              // $line_before_contato = $line;
              // $count_contato = count($empresas_obj->complementos['contato']);
              // $contato_size = $count_contato + $line_before_contato;
  
              // if ( $count_contato > 0 )
              //   $sheet->mergeCells("B{$line_before_contato}:B{$contato_size}");
  
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
             
              // $line = $line + 2;
          }
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

    private function organizar_grupo($grupos, $grupo) {
      $grupo = (object) $grupo;
      $found = false;
      foreach ($grupos as &$gr) {
        if (( isset($gr->coadjuvante) && !empty($gr->coadjuvante) ) && ( isset($gr->coadjuvante) && !empty($gr->coadjuvante) ) && ( $grupo->coadjuvante === $gr->coadjuvante and $grupo->adstrito === $gr->adstrito ) ) {
          $grp = new StdClass;
          $grp = [
            'nome' => $grupo->nome_grupo,
            'contatos' => $grupo->complementos['contato']
          ];
          $found = true;
          $gr->grupos[] = $grp;
          break;
        } 
      }
    
      if (!$found) {
        $grp = new StdClass;
        $grp->coadjuvante = $grupo->coadjuvante;
        $grp->adstrito = $grupo->adstrito;
        $grp->grupos[] = [
          'nome' => $grupo->nome_grupo,
          'contatos' => $grupo->complementos['contato']
        ];
        $grupos[] = $grp;
      }
      return $grupos;
    }
}



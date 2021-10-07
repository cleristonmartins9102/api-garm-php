<?php
namespace App\Mvc;

use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Transaction;
use App\Lib\Database\Expression;
use App\Model\Fatura\Calculo\CalculoItem;

abstract class Controller extends CalculoItem
{
  private $repository;
  private $criteria;

  public function criteria(Array $param, $criteria = null) {
    $this->criteria = new Criteria;
    $columns = isset($param['columns'])?$param['columns']:null;
    if ($columns != null) {
      foreach ($columns as $key => $coluna) {
        
        // Verifica se tem o tipo de buscar, se é valor exato, ou se é do tipo contain
        if (isset($param['comparation'])) {
          if ($param['comparation'] == 'contains') {
            $filter = "%${param['filter']}%";
          }
        } else {
          $filter = $param['filter'];
        }
        $this->criteria->add(new Filter($coluna, 'LIKE', $filter), 'or ');
      }
    }
    // Criando filtro
    if (isset($param['page']) and $param['page'] || $param['limit']) {
      // Removendo alpha
      $numPage = str_replace('page', '', $param['page']);
      // Verificando se é aprimeira pagina e insere limit default
      if ($numPage > 1) {
        if ($numPage == 2) {
          $start = $param['limit']; 
          $param['limit'] = "${start},${param['limit']}"; 
        } else {
          // Verifica se o número limit por página é impar ou par
          if ($param['limit']%2) {
            // Impar
            $numPage--;
            $start = ($numPage * $param['limit']);
            $param['limit'] = "${start},${param['limit']}";
          } else {
            // Par
            $start = (($numPage - 1) * $param['limit']); 
            $param['limit'] = "${start},${param['limit']}";
          }
        }
      } 
    $this->criteria->setProperty('limit', $param['limit']);
    }

    if (isset($param['order']) and $param['order'] and $param['sort']) {
      $sort = $param['sort'] === 'numero' ? "ABS(${param['sort']})" : $param['sort'];
      $this->criteria->setProperty('order', "$sort ${param['order']}");
    }
    // echo $this->criteria->dump();exit();
    if (!is_null($criteria))
      $this->criteria->add($criteria);
    return $this->criteria;
  }

  public function filterColunm(Array $param, $limit = true) {
    $this->criteria = new Criteria;
    $filters = $param['filter'] ?? $param;
    foreach ($filters as $key => $filter) {
      // Verificando se têm valor preenchido, se não tiver ignora o filtro
      if (isset($filter['filter']) && !is_null($filter['filter']) && $filter['filter'] !== '') {
        $field = $filter['field'];
        $expression = $filter['expression'];
        $operator = 'LIKE';
        switch ($expression) {
          case 'contem':
            if ( isset($filter['filter']) and 
                 ( is_array($filter['filter']) and count($filter['filter']) and
                  ( !is_null($filter['filter'][0]) and !empty($filter['filter'][0])) ) or 
                 is_string($filter['filter']) )  {

              // print_r(( is_array($filter['filter']) and !is_null($filter['filter'][0])));
              // echo is_null($filter['filter'][0]);
              // exit();
              $filter['filter'] = is_string($filter['filter']) 
                ? trim($filter['filter']) 
                : ( !is_null($filter['filter'][0]) 
                  ? trim($this->is_date($filter['filter'][0]))
                  : null );
              $filter_val = utf8_encode("%${filter['filter']}%");
              $filter = new Filter($field, $operator, $filter_val);
              $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');   
            }
            break;
          case 'igual':
            $operator = '=';
            // Verificando se é um array
            if ( is_array($filter['filter']) ) {
              // Verificando a quantidade de elementos no array
              if ( count($filter['filter']) > 0 and !is_null($filter['filter'][0]) and !empty($filter['filter'][0]) ) {
                $filter = $filter['filter'][0];
                if ( is_string($filter) ) {
                  $filter_val = $this->is_date($filter);
                  $filter = new Filter($field, $operator, $filter_val);
                } elseif ( is_numeric($filter)) {
                  $filter = new Filter($field, $operator, $filter);
                }   
                $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');       
              }
            } else {
              if ( is_string($filter['filter']) ) {
                $filter = $filter['filter'];
                $filter_val = utf8_encode($filter);
                $filter = new Filter($field, $operator, $filter_val);
                $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');
              }
              // echo $this->criteria->dump();
              // exit();

            }
            break;

          case 'maior':
            $operator = '>';
            // Verificando se é um array
            if ( is_array($filter['filter']) ) {
              // Verificando a quantidade de elementos no array
              if ( count($filter['filter']) > 0 and !is_null($filter['filter'][0]) ) {
                $filter = $filter['filter'][0];
                if ( is_string($filter) ) {
                  $filter_val = $this->is_date($filter);
                  $filter = new Filter($field, $operator, $filter_val);
                } elseif ( is_numeric($filter)) {
                  $filter = new Filter($field, $operator, $filter);
                }   
                $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');       
              }
            } else {
              if ( is_string($filter['filter']) ) {
                $filter = $filter['filter'];
                $filter_val = utf8_encode($filter);
                $filter = new Filter($field, $operator, $filter_val);
              }
              $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');
              // echo $this->criteria->dump();
              // exit();

            }
            break;

          case 'menor':
            $operator = '<';
            // Verificando se é um array
            if ( is_array($filter['filter']) ) {
              // Verificando a quantidade de elementos no array
              if ( count($filter['filter']) > 0 and !is_null($filter['filter'][0]) ) {
                $filter = $filter['filter'][0];
                if ( is_string($filter) ) {
                  $filter_val = $this->is_date($filter);
                  $filter = new Filter($field, $operator, $filter_val);
                } elseif ( is_numeric($filter)) {
                  $filter = new Filter($field, $operator, $filter);
                }   
                $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');       
              }
            } else {
              if ( is_string($filter['filter']) ) {
                $filter = $filter['filter'];
                $filter_val = utf8_encode($filter);
                $filter = new Filter($field, $operator, $filter_val);
              }
              $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');
              // echo $this->criteria->dump();
              // exit();

            }
            break;


          case 'intervalo':
            $operator = '=';
            // Verificando se é um array
            if ( is_array($filter['filter']) ) {
              // Verificando a quantidade de elementos no array
              if ( count($filter['filter']) === 2) { // Garantindo que o intervalo seja dois valores
                foreach ($filter['filter'] as $idx => $filter) {
                  if ( is_string($filter) ) {
                    $filter_val = $this->is_date($filter);
                    // echo $filter_val;
                    $filter = new Filter($field, $operator, $filter_val);
                  } elseif ( is_numeric($filter)) {
                    $filter = new Filter($field, $operator, $filter);
                  }  
                  if ( $idx === 0) {
                    $operator = '>=';
                  } else {
                    $operator = '<=';
                  }
                  $this->criteria->add(new Filter($field, $operator, $filter_val));      
                };
                // echo $this->criteria->dump();
                // exit();
              }
            } else {
              if ( is_string($filter['filter']) ) {
                $filter = $filter['filter'];
                $filter_val = utf8_encode($filter);
                $filter = new Filter($field, $operator, $filter_val);
              }
            }
            // $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');
            break;

          case 'diferente':
            $filter_val = utf8_encode($filter['filter']);
            $operator = '!=';
            break;
          
          default:
            break;
        }
        // echo mb_detect_encoding($filter['filter']);
        // $this->criteria->add(new Filter($field, $operator, $filter_val), 'and ');
      }
    }

    //Criando filtro
    if ( ( isset($param['page']) and $param['page'] || $param['limit'] ) and $limit) {
      //Removendo alpha
      $numPage = str_replace('page', '', $param['page']);
      //Verificando se é aprimeira pagina e insere limit default
      if ($numPage > 1) {
        if ($numPage == 2) {
          $start = $param['limit'] + 1; 
          $param['limit'] = "${start},${param['limit']}"; 
        } else {
          //Verifica se o número limit por página é impar ou par
          if ($param['limit']%2) {
            //Impar
            $numPage--;
            $start = ($numPage * $param['limit']) + 1;
            $param['limit'] = "${start},${param['limit']}";
          } else {
            //Par
            $start = (($numPage - 1) * $param['limit']) + 1; 
            $param['limit'] = "${start},${param['limit']}";
          }
        }
      } 
    $this->criteria->setProperty('limit', $param['limit']);
    }

    if (isset($param['order']) and $param['order'] and $param['sort']) {
      $this->criteria->setProperty('order', "${param['sort']} ${param['order']}");
    }
    // echo $this->criteria->dump();exit();
    return $this->criteria;
  }


  /**
   * Verifica se a string é uma data
   * @param String $date
   * @return string retorna a data em formatada ou string codificada utf8
   */
  private function is_date(string $date) {
    return is_null($date) 
              ? null
              : ( is_numeric($date) 
                ? $date 
                : ( (\DateTime::createFromFormat('m-d-Y', date('m-d-Y', strtotime($date))) !== FALSE) 
                  ? date('Y-m-d', strtotime($date))
                  : \utf8_decode($date) ) );
  }

  //Recebe o objeto e constroi um array com os dados das propriedades
  public function prepare($object, $dependence = null)
  {
    foreach ($object as $idx => $value) {
      if (!is_array($object)){
        $value = $object;
      }
      foreach ($value->getData() as $key => $value) {
        if ($value and $value != null){
          $value = is_string($value)?utf8_encode($value):$value;
          $dataModificated[$key] = trim($value);
        }
      }
    $dataArray[] = $dataModificated;   
    }
    return isset($dataArray)?$dataArray:null;
  }

  public function prepareBeforeSave($data) {
    foreach ($data as $key1 => &$val) {
      if (is_array($val)) {
        foreach ($val as $key1 => &$value) {
          if (is_array($value)) {
            foreach ($value as $key2 => &$val2) {
              $value[$key2] = trim($val2);
            }
          } else {
            $data[$key1] = trim($value);
          }
        }
      } else {
        $data[$key1] = trim($val);
      }
    }
    return $data;
  }

  protected function fromArray(String $key_model, Array $model, Array $request ){
    //Verifica se existem campos no modelo ou se é vazio
    if (count($model) > 0){
      //Percorrendo o array modelo para verificar modelo vs request
      foreach ($model as $key => $value) {
        //Verifica se existe o campos dentro do array de modelo
        if (!array_key_exists($value, $request)){
          return "faltando o campo $key_model:{ $value } nos dados submetidos";
        }
      }
      //Percorrendo o array modelo para verificar request vs modelo
      foreach ($request as $key => $value) {
        //Verifica se existe o campos dentro do array de modelo
        if (!in_array($key, $model)){
          return "o modelo não contêm o campo $key_model:{ $key:$value } ";
        }
      }
    }
    return 1;
  }


  protected function fromString(Array $model, Array $request){
    foreach ($model as $key => $value) {
      if (!is_array($value)){
        if (!in_array($key, $request)){
          return "faltando o campo $key nos dados submetidos";
        }
      }
    }
    foreach ($request as $key => $value) {
      if (!is_array($value)){
        if (!array_key_exists($value, $model)){
          return "o modelo não contêm o campo $value";
        }
      }
    }
    return 1;
  }


  static function openTransaction() {
    Transaction::open('zoho');
  }
  
  static function closeTransaction() {
    Transaction::close();
  }

  static function removePropriety(Array $data, Array $proprieties) {
    if (count($proprieties) > 0) {
      foreach ($proprieties as $i => $value) {
        // Verificando se existe a propriedade no array
        if (isset($data[$value])) {
          unset($data[$value]);
        }
      }
    }
    return $data;
  }

  static function historico($resp_save, $object)
  {
    // Verificando se houve alteracão ou inclusão
    if ($resp_save['occurrences'] !== null) {
      foreach ($resp_save['occurrences'] as $key => $occurrence) {
        switch ($occurrence['action']) {
          case 'updated':
            $msg = "Alterado " . ( $occurrence['propertie_comment'] !== '' ? $occurrence['propertie_comment'] : $occurrence['propertie']) . " de " . $occurrence['value_old'] . " para " . $occurrence['value_new'];
            break;
          case 'added':
            $msg = "Inserido " . ( $occurrence['propertie_comment'] !== '' ? $occurrence['propertie_comment'] : $occurrence['propertie'] );
            break;

          default:
            break;
        }
        $object->addHistorico($msg);
      }
    }
  }
}

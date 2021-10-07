<?php
namespace App\Control\Terminal;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Terminal\Terminal;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{
    
  public function store(Request $request, Response $response, Array $data)
  { 
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";exit();
    $valid = self::validate($data);
    $valid = 1;
    //Verificando se os dados possuem todas as colunas necessarias para o banco
    if ($valid == 1){
      try{
        self::openTransaction();
        $terminal = new Terminal($data['id_terminal'] ?? null);
        $terminal->id_individuo = $data['identificador'];
        $terminal->nome = $data['nome'];
        $terminal->id_status = $data['status'];
        // Gravando proposta
        $terminal->store();
        self::closeTransaction();
        return json_encode($result);
      }
      catch (Exception $e)
      {
        echo $e->getMessage();
      }

    }else{
      return $valid;
    }
  }

  public function clone(Request $request, Response $response, $idProposta)
  {
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';

    if ($idProposta != null) {
      self::openTransaction();
      // Intanciando proposta do banco
      $proposta = new Proposta($idProposta);
      // Clonando proposta
      $propostaArray = $proposta->getData();
      // Definindo o nome da coluna que não vai clonar
      $notClone = array('id_proposta', 'numero', 'created_at', 'updated_at', 'last_modificated', 'create_by', 'id_doc_proposta', 'id_aceite');
      // Instancia um novo objeto proposta que vai ser o clone
      $propostaClone = new Proposta;
      foreach ($propostaArray as $key => $value) {
        // Verificando se a chave é a coluna que não vai clonar
        if (!in_array($key, $notClone)) {
          $propostaClone->{$key} = $value;
        }
      }
      // Definindo status de renovação
      $propostaClone->tipo = 'renovação';
      // Se numero da proposta têm caracter "ponto"
      if (strpos($proposta->numero, '.')) {
        $barra = strstr($proposta->numero, '/', true);
        $traco = strpos($barra, '.')+1;
        // Definindo a verão da nova proposta
        $versaoProposta = substr($barra , $traco) + 1;

        $numeroAntesTraco = strstr($proposta->numero, '.', true);
        $anoProposta = str_replace('/', '', strstr($proposta->numero, '/', false));
        // Gravando o numero da proposta versionado
        $propostaClone->numero = "$numeroAntesTraco.$versaoProposta/$anoProposta";
      }
      // Gravando
      $propostaClone->store();

      // Clonando os predicados
      $criteria = new Criteria;
      $criteria->add(new Filter('id_proposta', '=', $proposta->id_proposta));
      $predicados = (new Repository('App\Model\Proposta\PropostaPredicado'))->load($criteria);
      
      //Verificando se encontrou predicados
      if (count($predicados) > 0) {
        // Percorrendo pela array de objetos de predicados
        $PropostaPredicado = new PropostaPredicado;
        foreach ($predicados as $key => $value) {
          $predicadosArray[] = $value->getData();
        }
        // Percorrendo pelo array de dados dos predicados
        foreach ($predicadosArray as $key => $valuePre) {
          foreach ($valuePre as $key => &$value) {
            $PropostaPredicado->{$key} = $value;
          }
          // Alteando o id da proposta para a clonada
          $PropostaPredicado->id_proposta = $propostaClone->id;
          // Gravando
          // print_r($PropostaPredicado);exit();

          $PropostaPredicado->store();
        }     
      }
      // Definindo status da proposta clonada para Inativa
      $proposta->status = "inativa";
      // Gravando
      $proposta->store();

      self::closeTransaction();
      $result['id_proposta'] = $propostaClone->id;
      return json_encode($result);
    }
  }

  private function validate($data)
  {
    $resp = 1;
    $endereco = array('logradouro','numero','complemento','cep','bairro','id_cidade');
    $papel = array();
    $pessoa_fisica = array('cpf','rg');
    $pessoa_juridico = array('cnpj','ie');
    $conjunto_individuo_fisico = array('nome', 'tipo', 'papel' => $papel, 'endereco' => $endereco, 'pessoa' => $pessoa_fisica);
    $conjunto_individuo_juridico = array('nome', 'tipo','endereco' => $endereco, 'pessoa' => $pessoa_juridico);

    if (isset($data['tipo'])){
      switch ($data['tipo']) {
        case 'PessoaFisica':
          foreach ($data as $key => $value) {
            //Testa se o valor é um array
            if (is_array($value)){
              //Verifica se no modelo existe a posição com o mesmo nome de array
              if (array_key_exists($key, $conjunto_individuo_fisico)){
                //Chama a função que vai verificar se todas as chaves do objeto estao corretas
                $resp = $this->fromArray($key, $conjunto_individuo_fisico[$key], $data[$key]);
                if ($resp != null and $resp != 1){
                  return json_encode(array('message'=>$resp));
                }
              }
            }else{
              //Verifica se a chave existe no conjunto
              $resp = $this->fromString($data, $conjunto_individuo_fisico);
              if ($resp != null and $resp != 1){
                return json_encode(array('message'=>$resp));
              }            
            }
          }
          if ($resp == 1){            
            return 1;
          }
         break;
        case 'PessoaJuridica':
          foreach ($data as $key => $value) {
            //Testa se o valor é um array
            if (is_array($value)){
              //Verifica se no modelo existe a posição com o mesmo nome de array
              if (array_key_exists($key, $conjunto_individuo_juridico)){
                //Chama a função que vai verificar se todas as chaves do objeto estao corretas
                $resp = $this->fromArray($key, $conjunto_individuo_juridico[$key], $data[$key]);
                if ($resp != null and $resp != 1){
                  return json_encode(array('message'=>$resp));
                }
              }
            }else{
              //Verifica se a chave existe no conjunto
              $resp = $this->fromString($data, $conjunto_individuo_juridico);
              if ($resp != null and $resp != 1){
                return json_encode(array('message'=>$resp));
              }            
            }
          }
          if ($resp == 1){            
            return 1;
          }
         break;
        default:
          return "{message: tipo pessoa invalido}";
        break;
       }
   }
}
    
}

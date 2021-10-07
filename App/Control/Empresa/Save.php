<?php
namespace App\Control\Empresa;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Endereco;
use App\Model\Pessoa\Contato;
use App\Model\Pessoa\Papel;
use App\Model\Pessoa\PessoaFisica;
use App\Model\Pessoa\PessoaJuridica;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{
    
  public function store(Request $request, Response $response, Array $data)
{   
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    $valid = self::validate($data);
    //Verificando se os dados possuem todas as colunas necessarias para o banco
    if (1 == 1){
      try{
        self::openTransaction();
        if (isset($data['id_individuo']) && $data['id_individuo']) {
          $metodo = 'update';
          $individuo = new Individuo($data['id_individuo']);
          $individuo->updated_at = 'now()';
          $endereco = new Endereco($individuo->id_endereco);
        } else {
          $metodo = 'create';
          $individuo = new Individuo;
          $endereco = new Endereco;
        }
        $individuo->request = $request;
        $individuo->response = $response;
        $individuo->nome = $data['nome'];
        $individuo->tipo = $data['tipo'];
        $endereco->logradouro  = $data['endereco']['logradouro'];
        $endereco->numero      = $data['endereco']['numero'];
        $endereco->cep         = $data['endereco']['cep'];
        $endereco->complemento = $data['endereco']['complemento'];
        $endereco->bairro      = $data['endereco']['bairro'];
        $endereco->id_cidade   = $data['endereco']['id_cidade'];

        //Gravando Endereco
        if (!$individuo->endereco = $endereco){
          $result['message'][] = 'erro ao gravar endereço';
          $result['status'] = 'fail';
        }
        
        // Verificando
        if ($data['tipo'] == 'PessoaFisica'){
            $p = new PessoaFisica($data['id_individuo'] ?? null);
            // Definindo uma propriedade identificadora para não gerar id na tabela
            if ($metodo !== 'update')
              $individuo->identificator = (int) $data['pessoa']['cpf'];
            $p->cpf = $data['pessoa']['cpf'];
            $p->rg = $data['pessoa']['rg'];         
        } else {
            $p = new PessoaJuridica($data['id_individuo'] ?? null);
            // Definindo uma propriedade identificadora para não gerar id na tabela
            if ($metodo !== 'update')
              $individuo->identificator = (int) $data['pessoa']['cnpj'];
            $p->cnpj = $data['pessoa']['cnpj'];
            $p->ie = $data['pessoa']['ie'] ?? null;
        }
 
         //Grava o individuo e aguarda a resposta da gravação
         if (!$p->individuo = $individuo) {
          $result['message'][] = 'erro ao gravar individuo pessoa juridica';
          $result['status'] = 'fail';
        };        
    
        //Adicionando papel depois de ter adicionado o individuo
        $papel = new Papel;
        $individuo->deletePapel();
        foreach ($data['papel'] as $key => $value) {
          $papel->id_papel = $value;
          $individuo->addPapel($papel);
        }

        $contatos_antigos = [];
        $contatos_novos = [];
        foreach ($data['contatos'] as $key => $contato) {
          if ($contato['id_contato']) {
            $contatos_antigos[] = $contato;
          } else {
            $contatos_novos[] = $contato;
          }
        }

        $individuo->deleteContato();
        foreach ($contatos_antigos as $key => $contato) {
          $contatoObj = new Contato($contato['id_contato']);
          $contatoObj->nome = $contato['nome'] ?? null;
          $contatoObj->ddi = $contato['ddi'] ?? null;
          $contatoObj->ddd = $contato['ddd'] ?? null;
          $contatoObj->telefone = $contato['telefone'] ?? null;
          $contatoObj->email = $contato['email'] ?? null;
          $contatoObj->classificacao = $contato['classificacao'] ?? null;
          $individuo->addContato($contatoObj);
        }

        foreach ($contatos_novos as $key => $contato) {
          $contatoObj = new Contato;
          $contatoObj->nome = $contato['nome'];
          $contatoObj->ddi = $contato['ddi'] ?? null;
          $contatoObj->ddd = $contato['ddd'] ?? null;
          $contatoObj->telefone = $contato['telefone'] ?? null;
          $contatoObj->email = $contato['email'] ?? null;
          $contatoObj->classificacao = $contato['classificacao'] ?? null;
          $individuo->addContato($contatoObj);
        }

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
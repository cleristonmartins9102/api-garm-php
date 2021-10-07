<?php
namespace App\Control\Empresa;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Endereco;
use App\Model\Pessoa\Papel;
use App\Model\Pessoa\PessoaFisica;
use App\Model\Pessoa\PessoaJuridica;

class Empresa extends Controller
{
  private $data;
  public function list($criteria, $filter)
  {
    
  }

  public function store(Array $data)
  {
    $data = self::prepareBeforeSave($data);
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";
    $valid = self::validate($data);
    //Verificando se os dados possuem todas as colunas necessarias para o banco
    if ($valid == 1){
      try{
        Transaction::open('zoho');
        $individuo = new Individuo;
        $individuo->nome = $data['nome'];
        $individuo->tipo = $data['tipo'];

        $endereco = new Endereco;
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
        
             
        //Testando se é pessoa fisica
        if ($data['tipo'] == 'PessoaFisica' ){
          $p = new PessoaFisica;
          $p->cpf = $data['pessoa']['cpf'];
          $p->rg = $data['pessoa']['rg'];
          //Grava o individuo e aguarda a resposta da gravação
          if (!$p->individuo = $individuo) {
            $result['message'][] = 'erro ao gravar individuo pessoa fisica';
            $result['status'] = 'fail';
          };
        }else{
          $p = new PessoaJuridica;
          $p->cnpj = $data['pessoa']['cnpj'];
          $p->ie = $data['pessoa']['ie'];
          //Grava o individuo e aguarda a resposta da gravação
          if (!$p->individuo = $individuo) {
            $result['message'][] = 'erro ao gravar individuo pessoa juridica';
            $result['status'] = 'fail';
          };        
        }

        //Adicionando papel depois de ter adicionado o individuo
        $papel = new Papel;
        foreach ($data['papel'] as $key => $value) {
          $papel->id_papel = $value;
          $individuo->addPapel($papel);
        }
        
        Transaction::close();
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

  private function individuo($i){
    foreach ($i as $key => $individuo) {
      $this->data[] = array('id_individuo' => $individuo->id_individuo,
                            'nome'         => $individuo->nome,
                            'endereco'     => $individuo->endereco->logradouro,
                            'numero'       => $individuo->endereco->numero,
                            'cep'          => $individuo->endereco->cep,
                            'bairro'       => $individuo->endereco->bairro,
                            'cidade'       => $individuo->endereco->cidade,
                            'estado'       => $individuo->endereco->estado,
                            'created_at'   => $individuo->created_at,
                            'updated_at'   => $individuo->updated_at,
                      );
                      //Verifica se é pessoa fisica ou juridica para pegar os dados corretos
                      if ($individuo->tipo == "PessoaFisica"){
                        $individuo->pessoa->cpf = 'dsdsd';
                        // $p = array('tipo'       => 'PF',
                        //           'identidade' => $cpf,
                        //           'cpf'        => $cpf,
                        //           'rg'         => $individuo->pessoa->rg
                        //           );
                        // $this->data[$key] = array_merge($this->data[$key], $p);
                      }else{
                        $p = array('tipo'       => 'PJ',
                                    'identidade' => $individuo->pessoa->cnpj,
                                    'cnpj'        => $individuo->pessoa->cnpj,
                                    'ie'         => $individuo->pessoa->ie
                                  );
                        $this->data[$key] = array_merge($this->data[$key], $p);
                      }
              
    }
  }
    
  

  
}

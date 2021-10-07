<?php
namespace App\Lib\Database;

use Exception;
use PDO;
use Std;
use App\Lib\Log\LoggerHTML;
use App\Lib\Log\LoggerTXT;
use App\Lib\Tool\Modificated;
use App\Lib\Tool\Register;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class Record extends Modificated implements RecordInterface
{
    protected $data; // array contendo os dados do objeto
    public $request;
    public $response;

    // Id no banco de dados com a nomenclaruta id_{classe}
    public $idBase;
    public $id;
    /**
     * Instancia um Active Record. Se passado o $id, já carrega o objeto
     * @param [$id] = ID do objeto
     */
    public function __construct($id = NULL)
    {
        //Concatena a palavra id_ com o nome da classe para definir o id da tabela no banco
        $this->idBase = "id_".strtolower($this->getEntity());

        if ($id and $id != null) // se o ID for informado
        {
            // carrega o objeto correspondente
            $object = $this->load($id);
            $this->id = $id;
            if ($object)
            {
                $this->fromArray($object->toArray());
            }
        }
    }

    /**
     * @param String $name Nome do objeto
     * @param String $colunm propriedade do objeto
     */
    public function __invoke(string $colunm, string $name, Criteria $cri = null) {
        $criteria = new Criteria;
        $criteria->add(new Filter($colunm, '=', $name));
        $repository = (new Repository(get_called_class()))->load($cri ?? $criteria);
        if (count($repository) === 0)
            return [];

        $id = $repository[0]->{$repository[0]->idBase};
        $object = $this->load($id);
        $this->id = $id;
        if ($object)
        {
            $this->fromArray($object->toArray());
        }
        return count($repository) === 0 ? [] : $this->load($repository[0]->id ?? $repository[0]->{$repository[0]->idBase}); 
    }

    /**
     * Limpa o ID para que seja gerado um novo ID para o clone.
     */
    public function __clone()
    {
        unset($this->data["{$this->idBase}"]);
        unset($this->data['created_at']);
        unset($this->data['created_by']);
        unset($this->data['updated_at']);
        unset($this->data['updated_by']);
        $this->id = null;
    }

    /**
     * Executado sempre que uma propriedade for atribuída.
     */
    public function __set($prop, $value)
    {   
        // verifica se existe método set_<propriedade>
        if (method_exists($this, 'set_'.$prop))
        {
            // executa o método set_<propriedade>
            call_user_func(array($this, 'set_'.$prop), $value);
        }
        else
        {
                            $this->data[$prop] = $value;

            if ($value || $value >= 0)
            {
                unset($this->data[$prop]);
            }
            else
            {
                // atribui o valor da propriedade
                $this->data[$prop] = $value;
            }
            $this->data[$prop] = $value || $value >= 0 ? $value : null;

        }
    }

    /**
     * Executado sempre que uma propriedade for requerida
     */
    public function __get($prop)
    {
        // verifica se existe método get_<propriedade>
        if (method_exists($this, 'get_'.$prop))
        {
            // executa o método get_<propriedade>
            return call_user_func(array($this, 'get_'.$prop));
        }
        else
        {
            // retorna o valor da propriedade
            if (isset($this->data[$prop]))
            {
                return $this->data[$prop];
            }
        }
    }

    /**
     * Retorna se a propriedade está definida
     */
    public function __isset($prop)
    {
        return isset($this->data[$prop]);
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Retorna o comentário da coluna
     */
    public function getColumnComment($column_name) {
        $sql = 'select `column_comment`
                from `information_schema`.`COLUMNS` 
                where `table_name` = `Liberacao` and column_name="dta_liberacao"';
        
        $sql = "SELECT `column_comment` FROM `information_schema`.`COLUMNS`";
        $sql .= " WHERE `table_name`='{$this->getEntity()}' and column_name='${column_name}'";
        // obtém transação ativa
        if ($conn = Transaction::get())
        {
            // cria mensagem de log e executa a consulta
            Transaction::log($sql);
            $result= $conn->query($sql);
            $object = null;
            // se retornou algum dado
            if ($result)
            {
                $fetch = $result->fetch();
                if ( is_array($fetch) and count($fetch) > 0 ) {
                    // print_r($fetch);
                    // retorna os dados em forma de objeto
                    $object = $fetch[0];
                } 
            }
            return $object;
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }

    }

    /**
     * Retorna o nome da entidade (tabela)
     */
    private function getEntity()
    {
        // obtém o nome da classe
        $class = get_class($this);
        
        // retorna a constante de classe TABLENAME
        return constant("{$class}::TABLENAME");
    }

    /**
     * Preenche os dados do objeto com um array
     */
    public function fromArray($data)
    {
        $this->data =  $data;
    }

    /**
     * Retorna os dados do objeto como array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Armazena o objeto na base de dados
     */
    public function store(Request $request = null, Response $response = null, Register $register = null)
    {    
        $request = !is_null($this->request) ? $this->request : $request;
        $response = !is_null($this->response) ? $this->response : $response;

        $class = get_called_class();

        // Verificando se têm alteracao entre os objetos
        $mod = $this->checkModificated($class, $register ?? null);
        $prepared = $this->prepare($this->data);
        // print_r($request->getAttribute('jwt')['name']);
        // print_r($prepared);
        // exit();
        if (!is_null($request)) 
            $usuario = $request->getAttribute('jwt')['name'];

            // verifica se tem ID ou se existe na base de dados
            if ( empty($this->data["{$this->idBase}"] ) or (!$this->load($this->id))) {
      
                //verifica se a tabela é to tipo manytomany e não adiona id
                if (!defined("$class::MANYTOMANY")) {
        
                    //Verificando se foi definido um identificador, se tiver definido ele vai usar o valor como ID na tabela  
                    if (isset($this->data['identificator']) or isset($this->data['identificador'])) {
                        $this->id = $this->data['identificator'];
                        $prepared["{$this->idBase}"] = $this->id;
                        unset($prepared["identificator"]);
                    }else{
                        // Caso não tiver definido identificador ele incrementa o ID
                        if (empty($this->data["{$this->idBase}"]))
                        {
                            $this->id = $this->getLast() + 1;
                            $prepared["{$this->idBase}"] = $this->id;
                            $this->data["{$this->idBase}"] = $this->id;
                        }
                        // if ( constant("$class::TABLENAME") === 'FaturaItemPro') {
                        //     echo constant("$class::TABLENAME");
                        //     echo $this->id;
                        //     // echo !defined("$class::MANYTOMANY");
                        //     echo $this->getLast() +1;
                        //     exit();
                        // }
                    }  
                }
                // cria uma instrução de insert
                $sql = "INSERT INTO {$this->getEntity()} " .
                        //  '('. implode(', ', array_keys($prepared)) . !is_null($request) ?  `created_by`  : '' .
                        '('. implode(', ', array_keys($prepared)) . (isset($usuario) ?  ", `created_by`"  : null) . ')' .
                        ' values ' .
                        '('. implode(', ', array_values($prepared)) . (isset($usuario) ? (",'$usuario'") : null) .')';
            }
            else
            {
                // describe Container
                // monta a string de UPDATE
                $sql = "UPDATE {$this->getEntity()}";
                // monta os pares: coluna=valor,...
                if ($prepared) {
                    foreach ($prepared as $column => $value) {
                        if ($column !== "{$this->idBase}") {
                            $set[] = "{$column} = {$value}";
                        }
                    }
                }
                // print_r($usuario);
                // $sql .= ' SET ' . implode(', ', $set) . ",`updated_by` = '$usuario'";
                $sql .= ' SET ' . implode(', ', $set) . (isset($usuario) ? ", updated_by = '$usuario'" : null);
                $sql .= " WHERE {$this->idBase}=" . (int) $this->data["{$this->idBase}"];
            }

            // obtém transação ativa
            if ($conn = Transaction::get())
            {
                // faz o log e executa o SQL
                Transaction::setLogger(new LoggerTXT('/var/log/garm/writeBD.txt'));
                Transaction::log($sql);
                $result = $conn->exec($sql);

                // retorna o resultado
                $result = [ 'result' => $result, 'occurrences' => $mod ?? null ];
                return $result;
            }
            else
            {
                // se não tiver transação, retorna uma exceção
                throw new Exception('Não há transação ativa!!');
            }
        }

    /*
     * Recupera (retorna) um objeto da base de dados pelo seu ID
     * @param $id = ID do objeto
     */
    public function load($id)
    {

        // instancia instrução de SELECT
        $sql = "SELECT * FROM {$this->getEntity()}";
        $sql .= " WHERE {$this->idBase}=" . (int) $id;

        // obtém transação ativa
        if ($conn = Transaction::get())
        {
            // cria mensagem de log e executa a consulta
            Transaction::log($sql);
            $result= $conn->query($sql);
            // se retornou algum dado
            if ($result)
            {   
                // retorna os dados em forma de objeto
                $object = $result->fetchObject(get_class($this));
                if ($object) {
                    foreach ($object->toArray() as $key => $value) {
                        if ($value == null) {
                            $object->removeProperty($key);
                        }
                    }
                }
            }
            return $object;
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    /**
     * Metodo que verifica se o objeto foi carregado
     */

    public function isLoaded() {
        if (empty($this->data))
            return false;
        return true;
    }

    /**
     * Exclui um objeto da base de dados através de seu ID.
     * @param $id = ID do objeto
     */
    public function delete($id = NULL)
    {
        // o ID é o parâmetro ou a propriedade ID
        $id = $id ? $id : $this->id;

        // monsta a string de UPDATE
        $sql  = "DELETE FROM {$this->getEntity()}";
        $sql .= " WHERE {$this->idBase}=" . (int) $id;
        // obtém transação ativa
        if ($conn = Transaction::get())
        {
            // faz o log e executa o SQL
            Transaction::log($sql);
            $result = $conn->exec($sql);
            // retorna o resultado
            return $result;
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    /**
     * Metodo que apaga por criterio
     * @param Criteria $criteria Criterio
     * @param Object $aggregate agregados que seram apagados e que possuem relacao
     */
    public function deleteByCriteria(Criteria $criteria, $aggregate=null)
    {  
        $key_primary = $criteria->expression()[0]->variable();
        // Verificando se foi passado um agregado para ser excluido
        if ($aggregate != null) {
            $sql  = "SELECT * FROM {$this->getEntity()}";
            $sql .= " WHERE {$criteria->dump()}";
            // obtém transação ativa
            if ($conn = Transaction::get())
            {
                // faz o log e executa o SQL
                Transaction::log($sql);
                $sth = $conn->prepare($sql);
                $sth->execute();
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (count($result)> 0) {
                    foreach ($result as $key => $value) {
                        // monsta a string de UPDATE
                        $name_key_del = $aggregate->idBase;
                        $sql  = "DELETE FROM {$aggregate->getEntity()}";
                        $sql .= " WHERE $name_key_del = $value[$name_key_del]";
                        // obtém transação ativa
                        if ($conn = Transaction::get())
                        { 
                            // faz o log e executa o SQL
                            Transaction::log($sql);
                
                            $result = $conn->exec($sql);
                        }
                    }
                }
            }   
        }
        // monsta a string de UPDATE
        $sql  = "DELETE FROM {$this->getEntity()}";
        $sql .= " WHERE {$criteria->dump()}";
        // obtém transação ativa
        if ($conn = Transaction::get())
        {
            // faz o log e executa o SQL
            Transaction::log($sql);
            $result = $conn->exec($sql);
            // retorna o resultado
            return $result;
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
        
    }

    public function removeProperty($property)
    {
        if (is_array($property)) {
            foreach ($property as $key => $prop) {
                unset($this->data[$prop]);
            }
            return;
        }
        unset($this->data[$property]);
    }

    /**
     * Retorna o último ID
     */
    public function getLast()
    {
        // inicia transação
        if ($conn = Transaction::get())
        {
            // instancia instrução de SELECT
            $sql  = "SELECT max({$this->idBase}) FROM {$this->getEntity()}";

            // cria log e executa instrução SQL
            Transaction::log($sql);
            // echo $sql . "<br>";
            $result= $conn->query($sql);

            // retorna os dados do banco
            $row = $result->fetch();
            return $row[0];
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    /**
     * Retorna o último ID
     */
    public function getLastNumber()
    {
        // inicia transação
        if ($conn = Transaction::get())
        {
            // instancia instrução de SELECT
            $sql  = "SELECT max(numero) FROM {$this->getEntity()}";

            // cria log e executa instrução SQL
            Transaction::log($sql);
            // echo $sql . "<br>";
            $result= $conn->query($sql);

            // retorna os dados do banco
            $row = $result->fetch();
            return round($row[0], 1);
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    /**
     * Retorna o último ID
     */
    public function getLastNum()
    {
        // inicia transação
        if ($conn = Transaction::get())
        {
            // instancia instrução de SELECT
            $sql  = "SELECT max(num) FROM {$this->getEntity()}";

            // cria log e executa instrução SQL
            Transaction::log($sql);
            // echo $sql . "<br>";
            $result= $conn->query($sql);

            // retorna os dados do banco
            $row = $result->fetch();
            return round($row[0], 1);
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    /**
     * Retorna todos objetos
     */
    public static function all()
    {
        $classname = get_called_class();
        $rep = new Repository($classname);
        return $rep->load(new Criteria);
    }

    /**
     * Busca um objeto pelo id
     */
    public static function find($id)
    {
        $classname = get_called_class();
        $ar = new $classname;
        return $ar->load($id);
    }

    public function findByCriteria(Criteria $criteria)
    {
        if (!empty($criteria)){
            return (new Repository(get_called_class()))->load($criteria);
        }
    }

    public function getColTable() 
    {
        if ($conn = Transaction::get())
        {
            // instancia instrução de SELECT
            $sql  = "SHOW columns FROM {$this->getEntity()}";

            Transaction::setLogger(new LoggerTXT('/var/log/garm/writeBD.txt'));
            // cria log e executa instrução SQL
            Transaction::log($sql);
            // echo $sql . "<br>";
            $result= $conn->prepare($sql);
            $result->execute();
            $table_fields = $result->fetchAll(PDO::FETCH_COLUMN);

            // retorna os dados do banco
            return $table_fields;
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    public function prepare($data)
    {     
        $prepared = array();
        if (count($data) > 0) {
            foreach ($data as $key => $value)
            {            
                if (is_scalar($value) )
                {
                    $prepared[$key] = $this->escape($value);
                } 
                // else if ( is_null($value)) {
                //     $prepared[$key] = 'NULL';
                // }
            }
            return $prepared;
        }
        
    }

    public function escape($value)
    {
        $scapeList = array('now()');
        // verifica se é um dado escalar (string, inteiro, ...)
        if (is_scalar($value))
        { 
            if (is_string($value) and (!empty($value)))
            {                 
                // adiciona \ em aspas
                $value = addslashes($value);
                // caso seja uma string
                if (in_array($value, $scapeList)) {
                    return $value;
                } else {
                    return "'$value'";
                }
            } 
            else if (is_bool($value))
            {
                // caso seja um boolean
                return $value ? 'TRUE': 'FALSE';
            }
            else if ($value!=='')
            {
                // caso seja outro tipo de dado
                return $value;
            } 
            else
            {
                // caso seja NULL
                return "NULL";
            }
        }
    }
}

<?php
namespace App\Lib\Database;

use Exception;

/**
 * Manipular coleções de objetos.
 * @author Pablo Dall'Oglio
 */
final class Repository
{
    private $data;
    private $colunm;
    private $group;
    private $activeRecord; // classe manipulada pelo repositório

    /**
     * Instancia um Repositório de objetos
     * @param $class = Classe dos Objetos
     */
    function __construct($class)
    {
        $this->activeRecord = $class;
    }

    /**
     * Carrega um conjunto de objetos (collection) da base de dados
     * @param $criteria = objeto do tipo TCriteria
     */
    function load(Criteria $criteria, $dump = false)
    {   
        $colunms = 'dds';
        // instancia a instrução de SELECT
        // $sql = "SELECT $colunms FROM " . constant($this->activeRecord.'::TABLENAME');

        // obtém a cláusula WHERE do objeto criteria.
        if ($criteria)
        {
            $expression = $criteria->dump();
            if ($expression)
            {
                $sql .= ' WHERE ' . $expression;
            }

            // obtém as propriedades do critério
            $order = $criteria->getProperty('order');
            $limit = $criteria->getProperty('limit');
            $offset= $criteria->getProperty('offset');

            // obtém a ordenação do SELECT
            if ($order) {
                $sql .= ' ORDER BY ' . $order;
            }
            if ($limit) {
                $sql .= ' LIMIT ' . $limit;
            }
            if ($offset) {
                $sql .= ' OFFSET ' . $offset;
            }
        }

        if ( $dump ) 
            return $sql;

        // obtém transação ativa
        if ($conn = Transaction::get())
        {
            // registra mensagem de log
            Transaction::log($sql);

            // executa a consulta no banco de dados
            $result= $conn->query($sql);
            $results = array();

            if ($result)
            {
                // percorre os resultados da consulta, retornando um objeto
                while ($row = $result->fetchObject($this->activeRecord))
                {
                    // armazena no array $results;
                    $results[] = $row;
                }
            }
            $this->data = $results;
            return $this->data;
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    function ump(Criteria $criteria) {
        return $this->load($criteria, true);
    }

    /**
     * Seta um grupo para o qual a busca vai ser agrupada
     * @param string $group = nome da coluna a ser agrupada
     */
    function setGroupBy(string $group) {
        if (is_empty($this->colunm) || is_null($this->colunm) || count($this->colunm) === 0)
            return 'Sem colunas adicionadas';
        $this->group = $group;
    }

    /**
     * Reseta colunas e grupos
     */
    function reset() {
        $this->group = null;
        $this->colunm = null;
    }

    /**
     * Adiciona as colunas que serão selecionadas
     * @param string $colunm = nome da coluna a ser selecionada
     */
    function addColunm(string $colunm) {
        $this->colunm[] = $colunm;
    }

    /**
     * Excluir um conjunto de objetos (collection) da base de dados
     * @param $criteria = objeto do tipo Criteria
     */
    function delete(Criteria $criteria)
    {
        $expression = $criteria->dump();
        $sql = "DELETE FROM " . constant($this->activeRecord.'::TABLENAME');
        if ($expression)
        {
            $sql .= ' WHERE ' . $expression;
        }

        // obtém transação ativa
        if ($conn = Transaction::get())
        {
            // registra mensagem de log
            Transaction::log($sql);
            // executa instrução de DELETE
            $result = $conn->exec($sql);
            return $result;
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');

        }
    }

    /**
     * Retorna a quantidade de objetos da base de dados
     * que satisfazem um determinado critério de seleção.
     * @param $criteria = objeto do tipo TCriteria
     */
    function count(Criteria $criteria)
    {
        $expression = $criteria->dump();
        $sql = "SELECT count(*) FROM " . constant($this->activeRecord.'::TABLENAME');
        if ($expression)
        {
            $sql .= ' WHERE ' . $expression;
        }

        // obtém transação ativa
        if ($conn = Transaction::get())
        {
            // registra mensagem de log
            Transaction::log($sql);

            // executa instrução de SELECT
            $result= $conn->query($sql);
            if ($result)
            {
                $row = $result->fetch();
            }
            // retorna o resultado
            return $row[0];
        }
        else
        {
            // se não tiver transação, retorna uma exceção
            throw new Exception('Não há transação ativa!!');
        }
    }

    function toArray() {
        foreach ($this->data as $key => $data) {
            $all_data[] = $data->toArray();
        }
        return $all_data;
    }
}

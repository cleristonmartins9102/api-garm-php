<?php
namespace App\Lib\Database;

/**
 * Permite definição de critérios
 * @author Pablo Dall'Oglio
 */
class Criteria extends Expression
{
    private $expressions; // armazena a lista de expressões
    private $operators;     // armazena a lista de operadores
    private $properties;    // propriedades do critério
    private $group;
    private $colunm = [];

    /**
     * Método Construtor
     */
    function __construct()
    {
        $this->expressions = array();
        $this->operators = array();
    }

    /**
     * Adiciona uma expressão ao critério
     * @param $expression = expressão (objeto Expression)
     * @param $operator   = operador lógico de comparação
     */
    public function add(Expression $expression, $operator = self::AND_OPERATOR)
    {
        // na primeira vez, não precisamos de operador lógico para concatenar
        if (empty($this->expressions))
        {  
            $operator = NULL;
        } 

        // agrega o resultado da expressão à lista de expressões
        $this->expressions[] = $expression;
        $this->operators[] = $operator;
    }

    public function clean()
    {
        $this->expressions = array();
        $this->operators = array();
        $this->properties = array();
    }

    /**
     * Retorna a expressão final
     */
    public function dump()
    {
        // print_r($this->operators);

        // concatena a lista de expressões
        if (is_array($this->expressions))
        {
            if (count($this->expressions) > 0)
            {
                $result = '';
                foreach ($this->expressions as $i=> $expression)
                {
                    $operator = $this->operators[$i];
                    // concatena o operador com a respectiva expressão
                    $result .= $operator. $expression->dump() . ' ';
                }
                $result = trim($result);
                return "({$result})";
            }
        }
    }

    public function expression()
    {
        return $this->expressions;
    }


    /**
     * Seta um grupo para o qual a busca vai ser agrupada
     * @param string $group = nome da coluna a ser agrupada
     */
    function setGroupBy(string $group) {
        if (empty($this->colunm) || is_null($this->colunm) || count($this->colunm) === 0){
            echo 'Sem colunas adicionadas';
        } else {
            $this->group = $group;
        }
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
    function addColunm($colunm) {
        if (is_array($colunm)) {
            foreach ($colunm as $key => $colunm) {
                $this->colunm[] = $colunm;
            }
        } else {
            $this->colunm[] = $colunm;
        }
    }

    public function getGroupBy() {
        return $this->group;
    }

    public function getColumn() {
        if (count($this->colunm) > 0) {
            $colunms = null;
            foreach ($this->colunm as $key => $colunm) {
                $colunms .=  ($key === 0 ? '' : ', ') . $colunm;
            }
            return $colunms;
        }
    }


    /**
     * Define o valor de uma propriedade
     * @param $property = propriedade
     * @param $value    = valor
     */
    public function setProperty($property, $value)
    {
        if (isset($value))
        {
            $this->properties[$property] = $value;
        }
        else
        {
            $this->properties[$property] = NULL;
        }
    }

    /**
     * Retorna o valor de uma propriedade
     * @param $property = propriedade
     */
    public function getProperty($property)
    {
        if (isset($this->properties[$property]))
        {
            return $this->properties[$property];
        }
    }
}

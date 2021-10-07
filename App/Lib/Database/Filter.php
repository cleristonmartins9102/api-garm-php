<?php
namespace App\Lib\Database;

/**
 * Permite definir filtros de seleção
 * @author Pablo Dall'Oglio
 */
class Filter extends Expression
{
    private $variable; // variável
    private $operator; // operador
    private $value;    // valor

    /**
     * Instancia um novo filtro
     * @param $variable = variável
     * @param $operator = operador (>,<)
     * @param $value      = valor a ser comparado]
     * @param $verify      =  se transform for false, ele ignora a transformação

     */
    public function __construct($variable, $operator = null, $value = null, $transform = null)
    {
        // armazena as propriedades
        $this->variable = $variable;
        $this->operator = $operator;

        // transforma o valor de acordo com certas regras
        // antes de atribuir à propriedade $this->value
        if (is_null($transform)) {
            $this->value = $this->transform($value);
        } else {
            $this->value = $value;
        }
    }

    /**
     * Recebe um valor e faz as modificações necessárias
     *   para ele ser interpretado pelo banco de dados
     * @param $value = valor a ser transformado
     */
    private function transform($value)
    {
        // caso seja um array
        if (is_array($value))
        {
            // percorre os valores
            foreach ($value as $x)
            {
                // se for um inteiro
                if (is_integer($x))
                {
                    $foo[]= $x;
                }
                else if (is_string($x))
                {
                    // se for string, adiciona aspas
                    $foo[]= "'$x'";
                }
            }
            // converte o array em string separada por ","
            $result = '(' . implode(',', $foo) . ')';
        }
        // caso seja uma string
        else if (is_string($value))
        {
            //se for utf-8 decodifica
            if (mb_detect_encoding($value) == 'UTF-8'){
                $value = utf8_decode($value);
            }
            // adiciona aspas
            $result = "'$value'";
        }
        // caso seja valor nullo
        else if (is_null($value))
        {
            // armazena NULL
            $result = 'NULL';
        }

        // caso seja booleano
        else if (is_bool($value))
        {
            // armazena TRUE ou FALSE
            $result = $value ? 'TRUE' : 'FALSE';
        }
        else
        {
            $result = $value;
        }
        // retorna o valor
        return $result;
    }

    /**
     * Retorna o filtro em forma de expressão
     */
    public function dump()
    {
        if ( is_null($this->operator) )
            return $this->variable;
            
        // concatena a expressão
        return "{$this->variable} {$this->operator} {$this->value}";
    }

    public function variable()
    {
        return $this->variable;
    }
}

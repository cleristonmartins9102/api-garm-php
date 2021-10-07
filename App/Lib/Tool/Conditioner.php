<?php
namespace App\Lib\Tool;

class Conditioner extends Operator
{
    private $operator;
    private $expressions;

    function __construct()
    {
        $this->expressions = array();
        $this->operators = array();
    }

    public function add(Condition $expression = null, $operator = self::AND_OPERATOR ) {
          // na primeira vez, não precisamos de operador lógico para concatenar
          if (empty($this->expressions))
          {  
              $operator = NULL;
          } 
  
          // agrega o resultado da expressão à lista de expressões
          $this->expressions[] = $expression;
          $this->operators[] = $operator;
    }

    public function dump()
    {
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
                $result = "return " . trim($result) . ";";
                return eval($result);
            }
        }
    }

    public function expression()
    {
        return $this->expressions;
    }

}

<?php
namespace App\Lib\Tool;

/**
 * Classe abstrata para permitir definição de expressões
 * @author Pablo Dall'Oglio
 */
abstract class Operator
{
    // operadores lógicos
    const AND_OPERATOR = 'AND ';
    const OR_OPERATOR = '|| ';

    // marca método dump como obrigatório
    abstract public function dump();
}

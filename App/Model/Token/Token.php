<?php
namespace App\Model\Token;

use App\Lib\Database\Record;
use App\Model\Usuario\Usuario;

/**
 * Modelo de Token
 */
class Token extends Record
{
    const TABLENAME = 'Token';

    function get_usuario() {
        return new Usuario($this->id_usuario);
    }
}

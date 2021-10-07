<?php
namespace App\Model;

interface SafeData {
    /**
     * Função que verifica se o registro pode ser editavel
     */
    function isItLockedEdit();
}
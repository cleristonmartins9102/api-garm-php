<?php
namespace App\Lib\Tool;

use DateTime;

/**
 * Verifica se houve alteracao ou edicao no objeto
 * @author Cleriston Martins 
 */

class Modificated
{
    protected $prop_mod; // Prop

    public function checkModificated($called_class, Register $register = null)
    {
        $object_old = new $called_class($this->id);
        $object_new = $this;

        // Chamando o metodo recursivo para identificar as diferenças
        return self::recursiveDiff($object_new, $object_old, $register);
    }

    public function recursiveDiff($object_new, $object_old, Register $register = null)
    {
        $object_new_arr = $object_new->toArray();
        $object_old_arr = $object_old->toArray();

        $exclude = ['numero', 'created_at', 'updated_at']; // Lista de exclusão de propriedades a serem ignoradas
        // Percorrendo as propriedades do objeto novo para ver se existe no objeto antigo
        foreach ($object_new_arr as $prop => &$value) {
            $prop_changed = null;
            // Verificando se a propriedade esta na lista de pripriedades a serem ignoradas
            if (!in_array($prop, $exclude) && $value !== null) {
                // Vericica se a propriedade do objeto novo existe no objeto antigo
                if (is_array($object_old_arr) && array_key_exists($prop, $object_old_arr)) {

                    // Já que o objeto existe, verifica se os valores foram alterados
                    if ((is_string($object_old_arr[$prop]) && is_string($object_new_arr[$prop])) && ($object_new_arr[$prop] !==  $object_old_arr[$prop])) {
                        if (DateTime::createFromFormat('Y-m-d', $object_old_arr[$prop]) !== FALSE) {
                            $object_old_arr[$prop] = date('d/m/Y', strtotime($object_old_arr[$prop]));
                        }
                        if (DateTime::createFromFormat('Y-m-d', $object_new_arr[$prop]) !== FALSE) {
                            $object_new_arr[$prop] = date('d/m/Y', strtotime($object_new_arr[$prop]));
                        }
                        // Verificando se a propriedade consta no registro
                        if (!is_null($register)) {
                            if ($register->search($prop)) {
                                // Se existe, executa o methodo do objeto que esta registrado
                                $object_old_arr[$prop] = $object_old->{$register->search($prop)['method']};
                                $object_new_arr[$prop] = $object_new->{$register->search($prop)['method']};
                            }
                        }
                        $prop_changed = [
                            'propertie_comment' => $this->getColumnComment($prop) !== null ?
                                $this->getColumnComment($prop) : $prop, 'propertie' => $prop,
                            'value_old' => is_scalar($object_old_arr[$prop]) ? $object_old_arr[$prop] : null,
                            'value_new' => is_scalar($object_new_arr[$prop]) ? $object_new_arr[$prop] : null,
                            'action' => 'updated'
                        ];
                    }
                } else {
                    $prop_changed = [
                        'propertie_comment' => $this->getColumnComment($prop) !== null ?
                            $this->getColumnComment($prop) : $prop, 'propertie' => $prop,
                        'action' => 'added'
                    ];
                }
            }

            if (isset($prop_changed)) {
                $this->prop_mod[] = $prop_changed;
            }
        }
        return $this->prop_mod;
    }
}

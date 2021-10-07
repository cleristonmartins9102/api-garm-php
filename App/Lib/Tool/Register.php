<?php
namespace App\Lib\Tool;

/**
 * @author Cleriston Martins
 * Registra propriedades de objetos e nomes de metodos
 */
class Register
{
    protected $prop;

    /**
     * Metodo para adicionar um registro
     * @param string | array $property
     */
    public function add($property = null, $method = null)
    {
        // Se for diferente de nulo
        if ($property and $method) {
            $this->prop[] = [ 'propriety' => $property, 'method' => $method ];
        }
    }

    public function clean() {
        $this->prop = [];
    }

    // Retorna todas as propriedades e metodos
    public function dump() {
        return $this->prop;
    }
    
    // Verifica se a propriedade esta registrada
    public function search($prop = null) {
        $result = array_search($prop, array_column($this->dump(), 'propriety'));
        if ($result !== False) {
            return $this->prop[$result];
        }
    }
}

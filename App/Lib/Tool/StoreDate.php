<?php
namespace App\Lib\Tool;

class StoreDate
{
    private $date;

    public function __construct()
    {
         $this->date = [];
    }

    public function add($date = null) {
        if (!is_null($date)) {
            $this->date[] = date('Y-m-d', strtotime($date));
        }
    }

    public function first() {
        return $this->date[0];
    }

    public function last() {
        if (count($this->date) > 0) {
            return $this->date[count($this->date) - 1];
        }
    }

    public function nextDay() {
        if (count($this->date) > 0) {
            return date('Y-m-d', strtotime($this->date[count($this->date) - 1] . ' +1 day'));
        }
    }

    public function calcDatePeriodo($periodo) {
        if (!is_null($periodo)) {
            self::add(date('Y-m-d', strtotime($this->last() . "+{$periodo} day")));
            return self::last();
        }
    }

    public function dataFinal($dias_consumo) {
        if (!is_null($dias_consumo)) {
            self::add(date('Y-m-d', strtotime($this->first() . "+{$dias_consumo} day")));
            return self::last();
        }
    }

    public function clean() {
        $this->date = [];
    }

    public function reset() {
            $this->date= [$this->date[0]];
    }
}

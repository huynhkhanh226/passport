<?php
namespace App\Classes;

class DataExport
{
    public function __construct(array $data = array())
    {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
    }
}
<?php

/**
 * Created by PhpStorm.
 * User: HUYNH KHANH
 * Date: 12/12/2016
 * Time: 3:58 PM
 */
namespace App\Classes;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MyExportExcel implements WithMultipleSheets
{
    use Exportable;
    protected $data;
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        for ($i = 1; $i <= count($this->data); $i++) {
            if (count($this->data[$i-1]["dataExport"]) > 0){
                $sheets[] = new MySheetFromView($this->data[$i-1], $i);
            }

        }

        return $sheets;
    }
}
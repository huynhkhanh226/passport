<?php

/**
 * Created by PhpStorm.
 * User: HUYNH KHANH
 * Date: 12/12/2016
 * Time: 3:58 PM
 */
namespace App\Classes;
use App\Users;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class MySheetFromCollection implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithEvents
{
    private $sheetData;

    public function collection()
    {
        return Users::all();
    }
    public function headings(): array
    {
        return [
            '#',
            'Name',
            'Email',
            'Created at',
            'Updated at'
        ];
    }
    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = 'A1:W1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(700);
            },
        ];
    }
    /**
     * @return string
     */
    public function title(): string
    {
        return "abc";
    }


}
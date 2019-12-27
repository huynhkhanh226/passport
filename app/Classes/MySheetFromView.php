<?php

/**
 * Created by PhpStorm.
 * User: HUYNH KHANH
 * Date: 12/12/2016
 * Time: 3:58 PM
 */

namespace App\Classes;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;


class MySheetFromView implements FromView, WithTitle, ShouldAutoSize, WithEvents
{
    private $sheetData;
    private $order;

    public function __construct($sheetData, $i)
    {
        $this->sheetData = $sheetData;
        $this->order = $i;
    }

    public function view(): View
    {
        $sheetData = $this->sheetData;
        return view('partial.exportExcel', compact('sheetData'));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
            $rowIndx = intval($this->sheetData["maxLevel"]) + 1;
                $cellRange = 'A1:W'.$rowIndx ; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(700);
            },
        ];
    }

//    public function registerEvents(): array
//    {
//        // TODO: Implement registerEvents() method.
//        return [
//            BeforeExport::class  => function(BeforeExport $event) {
//                $event->writer->setCreator('Patrick');
//            },
//            AfterSheet::class    => function(AfterSheet $event) {
//                $event->sheet->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
//
//                $event->sheet->styleCells(
//                    'B2:G8',
//                    [
//                        'borders' => [
//                            'outline' => [
//                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
//                                'color' => ['argb' => 'FFFF0000'],
//                            ],
//                        ]
//                    ]
//                );
//            },
//        ];
//    }

    /**
     * @return string
     */
    public function title(): string
    {
        $name = '';//$this->sheetData["sheetName"];
        if ($name == ''){
            $name = 'sheetData_'. $this->order;
        }
        return $name;// ;
    }
}
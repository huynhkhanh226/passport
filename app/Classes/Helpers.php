<?php

namespace App\Classes;


use alhimik1986\PhpExcelTemplator\PhpExcelTemplator;
use alhimik1986\PhpExcelTemplator\params\CallbackParam;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use DateTime;
use DaveJamesMiller\Breadcrumbs\Facades\Breadcrumbs;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\URL;
use PDO;
use PHPExcel_Cell;

use PHPExcel_IOFactory;
use PHPExcel_Settings;
use PHPExcel_Style_Alignment;
use PHPExcel_Writer_Excel2007;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;


/**
 * Created by PhpStorm.
 * User: THANHLUAN
 * Date: 8/12/2015
 * Time: 9:42 AM
 */
class Helpers
{
    protected static $gsTypeEncrypt = "DED0104";
    protected static $gsXCodeString = "CCuL40";

    //ghi log
    public static function log($str)
    {
        $path = base_path() . "/report/logfile/err.txt";
        File::prepend($path, date('d-m-Y H:i:s') . PHP_EOL . "Debug location: " . Request::path() . PHP_EOL . $str . PHP_EOL . " " . PHP_EOL);
    }

    // trả ra mảng chứa menu bên trái
    public static function LeftMenu($array, $columnGroup, $columnIcon = '', $columnKey = '', $columnVal = '')
    {
        $rs = [];
        foreach ($array as $row) {
            if ($columnIcon != '')
                $rs[$row[$columnGroup]][0] = $row[$columnIcon];
            $rs[$row[$columnGroup]][1] = $row[$columnGroup];
            $rs[$row[$columnGroup]][] = $row;
            // lưu quyền
            Session::set($row[$columnKey], $row[$columnVal]);
        }
        return $rs;
    }

    // hiển thị cho cột bắt buộc nhập trên lưới
    public static function ColRequire()
    {
        return "<span class='gridColRequire'>*</span>";
    }


    //Mã hóa cho passuser cura Lemon3
    //Chú ý: những ký tự bảng mã từ 128 đến 255 PHP không hiểu nên viết thêm hàm unichr()

    public static function encrypt_userpass($str)
    {
        if ($str == '') return '';

        $len = strlen($str);
        //đưa chuỗi vào mảng arr
        $arr = str_split($str, 1);
        $arr1 = $arr;    //print_r($arr);

        // Mã hóa chuỗi gắn lại giá trị vào mảng
        for ($i = 0; $i < $len; $i++) {
            $iord = (ord($arr1[$i]) * 2) + 3;
            if ($iord > 127) // nếu dùng mã mở rộng thì dùng hàm unichr (tự viết)
                $arr[$len - ($i + 1)] = Helpers::unichr($iord, '');
            else
                $arr[$len - ($i + 1)] = chr($iord);
        }

        //lấy mảng đưa vào chuỗi lại.
        $outstr = "";
        for ($i = 0; $i < $len; $i++) {
            $outstr .= $arr[$i];
        }


        return $outstr;
    }

    public static function decrypt_userpass($str)
    {
        if ($str == '') return '';

        //đưa chuỗi vào mảng arr
        $arr = Helpers::mbStringToArray($str);//mb_substr($str,0,1,"UTF-8");
        $arr1 = $arr;    //print_r($arr);
        $len = count($arr);
        // Mã hóa chuỗi gắn lại giá trị vào mảng
        $offset = 0;
        $i = 0;

        for ($i = 0; $i < $len; $i++) {
            if ($arr1[$i] != '') {
                $key = Helpers::unichr(0, $arr1[$i]);
                if ($key === false) {
                    $iord = (Helpers::ordutf8($arr1[$i], $i) - 3) * 0.5;
                    if ($iord > 127) // n?u dùng mã m? r?ng thì dùng hàm unichr (t? vi?t)
                    {
                        $arr[$len - ($i + 1)] = Helpers::unichr($iord, '');
                    } else {
                        $arr[$len - ($i + 1)] = chr($iord);
                    }
                } else {
                    $arr[$len - ($i + 1)] = chr(($key - 3) / 2);
                }

            }
        }
        //lấy mảng đưa vào chuỗi lại.
        $outstr = "";
        for ($i = 0; $i < $len; $i++) {
            $outstr = $outstr . $arr[$i];
        }
        return $outstr;
    }


    protected static function ordutf8($string, &$offset)
    {
        //$code = ord(substr($string, $offset,1));
        $code = ord($string);
        if ($code >= 128) {        //otherwise 0xxxxxxx
            if ($code < 224) $bytesnumber = 2;                //110xxxxx
            else if ($code < 240) $bytesnumber = 3;        //1110xxxx
            else if ($code < 248) $bytesnumber = 4;    //11110xxx
            $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
            for ($i = 2; $i <= $bytesnumber; $i++) {
                //$offset ++;
                $code2 = ord($string) - 128;// ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
                $codetemp = $codetemp * 64 + $code2;
            }
            $code = $codetemp;
        }
        return $code;
    }

    //đưa các kí tự trong chuỗi vào mảng
    protected static function mbStringToArray($string)
    {
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "UTF-8");
            $string = mb_substr($string, 1, $strlen, "UTF-8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }

    static function unichr($n, $value)
    {
        $ascii_array = array(
            128 => "€",
            129 => "",
            130 => "‚",
            131 => "ƒ",
            132 => "„",
            133 => "…",
            134 => "†",
            135 => "‡",
            136 => "ˆ",
            137 => "‰",
            138 => "Š",
            139 => "‹",
            140 => "Œ",
            141 => "",
            142 => "Ž",
            143 => "",
            144 => "",
            145 => "‘",
            146 => "’",
            147 => "“",
            148 => "”",
            149 => "•",
            150 => "–",
            151 => "—",
            152 => "˜",
            153 => "™",
            154 => "š",
            155 => "›",
            156 => "œ",
            157 => "",
            158 => "ž",
            159 => "Ÿ",
            160 => " ",
            161 => "¡",
            162 => "¢",
            163 => "£",
            164 => "¤",
            165 => "¥",
            166 => "¦",
            167 => "§",
            168 => "¨",
            169 => "©",
            170 => "ª",
            171 => "«",
            172 => "¬",
            173 => "­",
            174 => "®",
            175 => "¯",
            176 => "°",
            177 => "±",
            178 => "²",
            179 => "³",
            180 => "´",
            181 => "µ",
            182 => "¶",
            183 => "·",
            184 => "¸",
            185 => "¹",
            186 => "º",
            187 => "»",
            188 => "¼",
            189 => "½",
            190 => "¾",
            191 => "¿",
            192 => "À",
            193 => "Á",
            194 => "Â",
            195 => "Ã",
            196 => "Ä",
            197 => "Å",
            198 => "Æ",
            199 => "Ç",
            200 => "È",
            201 => "É",
            202 => "Ê",
            203 => "Ë",
            204 => "Ì",
            205 => "Í",
            206 => "Î",
            207 => "Ï",
            208 => "Ð",
            209 => "Ñ",
            210 => "Ò",
            211 => "Ó",
            212 => "Ô",
            213 => "Õ",
            214 => "Ö",
            215 => "×",
            216 => "Ø",
            217 => "Ù",
            218 => "Ú",
            219 => "Û",
            220 => "Ü",
            221 => "Ý",
            222 => "Þ",
            223 => "ß",
            224 => "à",
            225 => "á",
            226 => "â",
            227 => "ã",
            228 => "ä",
            229 => "å",
            230 => "æ",
            231 => "ç",
            232 => "è",
            233 => "é",
            234 => "ê",
            235 => "ë",
            236 => "ì",
            237 => "í",
            238 => "î",
            239 => "ï",
            240 => "ð",
            241 => "ñ",
            242 => "ò",
            243 => "ó",
            244 => "ô",
            245 => "õ",
            246 => "ö",
            247 => "÷",
            248 => "ø",
            249 => "ù",
            250 => "ú",
            251 => "û",
            252 => "ü",
            253 => "ý",
            254 => "þ",
            255 => "ÿ"
        );
        if ($value != '')
            return array_search($value, $ascii_array);
        else
            return $ascii_array[$n];
    }


    // trả ra chuỗi
    public static function ShowIfExit($str)
    {
        return isset($str) ? $str : "";
    }

    //header cho popup modal có logo
    public static function generateHeading($text = "", $formid = "", $show = true, $func = "", $close = true, $pform = "", $factive = "")
    {
        $head = '<span class="logodg pdl0"></span>';
        if ($close) {
            if ($func != "") {//Nếu có function riêng thì ko được dùng button chung của modal
                $head .= '<div class="btn-group pull-right">';
                if ($show)//Chỉ có các popup cha mới có icon thông tin
                    $head .= '<button type="button" class="btn header-icon-info" title="' . $formid . '" onclick="ShowFormInfo(\'' . $formid . '\',\'' . $text . '\',\'' . $pform . '\',\'' . $factive . '\')"><i class="fa fa-info-circle"  style="color:#FFffff;"></i></button>';
                if ($show)
                    $head .= '<button type="button" class="btn header-icon-close" onclick="' . $func . '()"><i class="fa fa-times" style="color:#FFffff;" aria-hidden="true"></i></button>';
                else
                    $head .= '<button type="button" class="btn header-icon-close1" onclick="' . $func . '()"><i class="fa fa-times" style="color:#FFffff;" aria-hidden="true"></i></button>';
                $head .= '</div>';

            } else {
                $head .= '<div class="btn-group pull-right">';
                if ($show)//Chỉ có các popup cha mới có icon thông tin
                    $head .= '<button type="button" class="btn header-icon-info" title="' . $formid . '" onclick="ShowFormInfo(\'' . $formid . '\',\'' . $text . '\',\'' . $pform . '\',\'' . $factive . '\')"><i class="fa fa-info-circle"  style="color:#FFffff;"></i></button>';
                if ($show)
                    $head .= '<button type="button" class="btn header-icon-close"  data-dismiss="modal" aria-label="Close"><i class="fa fa-times" style="color:#FFffff;" aria-hidden="true"></i></button>';
                else
                    $head .= '<button type="button" class="btn header-icon-close1"  data-dismiss="modal" aria-label="Close"><i class="fa fa-times" style="color:#FFffff;" aria-hidden="true"></i></button>';
                $head .= '</div>';
            }

        }
        $head .= '<div class="panel-heading logodg clfontsize"> ' . $text . ' </div>';
        return $head;
    }

    // header cho popup modal mh duyệt
    public static function generateHeadingApp($text = "", $formid = "", $func = "", $pform = "", $factive = "")
    {
        $head = '<span class="logodg pdl0"></span>';
        $head .= '<div class="btn-group pull-right">';

        $head .= '<button onclick="ShowFormInfo(\'' . $formid . '\',\'' . $text . '\',\'' . $pform . '\',\'' . $factive . '\')" title="' . $formid . '" class="btn header-icon-info app" type="button"><i class="fa fa-info-circle" style="color:#FFffff;"></i></button>';
        $head .= '<button aria-label="Close" data-dismiss="modal" class="btn header-icon-close" type="button" ' . ($func !== "" ? 'onclick="' . $func . '();"' : '') . '><span aria-hidden="true"><i class="fa fa-close" style="color:#FFffff;"></i></span></button>';
        $head .= '</div>';
        $head .= '<div class="panel-heading text-center clfontsize1"><button class="btn pull-left btn-default fa fa-align-justify" type="button" id="btnCollapse"></button> ' . $text . ' </div>';
        return $head;
    }

    public static function generateHeadingCus(array $class, $text = "", $collapse = "", $close = "")
    {
        $class = implode(" ", $class);
        return "<div class=\"$class\"> $collapse $text $close</div>";
    }
    // lấy ngày tới ngày của tuần này, tháng này, năm này, quý này
    // $mode:: 0:Tất cả, 1: Tuần này, 2: Tháng này, 3: Năm nay, 4: tuần trước, 5: tháng trước, 6 quý này
    public static function getFromToDate($mode, &$FromDate, &$ToDate)
    {
        switch ($mode) {
            case 0 :
                $FromDate = "'01/01/2000'";
                $ToDate = "'01/01/9999'";
                break;
            case 1 :
                $FromDate = "'" . date('m/d/Y', strtotime('monday this week', time())) . "'";
                $ToDate = "'" . date('m/d/Y', strtotime('sunday this week', time())) . "'";
                break;
            case 2 :
                $FromDate = "'" . date('m/d/Y', strtotime('first day of this month', time())) . "'";
                $ToDate = "'" . date('m/d/Y', strtotime('last day of this month', time())) . "'";
                break;
            case 3:
                $FromDate = "'01/01/" . date('Y') . "'";
                $ToDate = "'12/31/" . date('Y') . "'";
                break;
            case 4:
                $FromDate = "'" . date('m/d/Y', strtotime('monday last week', time())) . "'";
                $ToDate = "'" . date('m/d/Y', strtotime('sunday last week', time())) . "'";
                break;
            case 5:
                $FromDate = "'" . date('m/d/Y', strtotime('first day of last month', time())) . "'";
                $ToDate = "'" . date('m/d/Y', strtotime('last day of last month', time())) . "'";
                break;
            case 6:
                $m = intval(date('m'));
                if ($m < 4) {
                    $FromDate = "'" . date('m/d/Y', strtotime('first day of January', time())) . "'";
                    $ToDate = "'" . date('m/d/Y', strtotime('last day of March', time())) . "'";
                };
                if ($m > 3 && $m < 7) {
                    $FromDate = "'" . date('m/d/Y', strtotime('first day of April', time())) . "'";
                    $ToDate = "'" . date('m/d/Y', strtotime('last day of June', time())) . "'";
                }
                if ($m > 6 && $m < 10) {
                    $FromDate = "'" . date('m/d/Y', strtotime('first day of July', time())) . "'";
                    $ToDate = "'" . date('m/d/Y', strtotime('last day of September', time())) . "'";
                }
                if ($m > 9 && $m < 13) {
                    $FromDate = "'" . date('m/d/Y', strtotime('first day of October', time())) . "'";
                    $ToDate = "'" . date('m/d/Y', strtotime('last day of December', time())) . "'";
                }
                break;
        }
    }

    //in báo cáo
    //in báo cáo
    public static function Report($reportid, $subreport, $mainsql, $sqlsub, $param = '')
    {

        $filename = public_path() . "/reports/" . $reportid . ".rpt";

        $ObjectFactory = new \COM("CrystalReports10.ObjectFactory.1") or die ("Error on load"); // call COM port
        $crapp = $ObjectFactory->CreateObject("CrystalRuntime.Application.10"); // create an instance for Crystal

        $creport = $crapp->OpenReport($filename, 1); // call rpt report
        $creport->EnableParameterPrompting = 0;
        //- field prompt or else report will hang - to get through
        //$mainsql="Select * from D27V2244";
        $conn = new \COM("ADODB.Connection", NULL, 65001);

        $server = \Config::get('database.connections.sqlsrv.host', '');
        $user = \Config::get('database.connections.sqlsrv.username', '');
        $pass = \Config::get('database.connections.sqlsrv.password', '');
        $database = \Config::get('database.connections.sqlsrv.database', '');

        //open connection
        //return "Provider=SQLNCLI11;Server=$server;Database=$database;Uid=$user;Pwd=$pass";
        $conn->open("Provider=SQLNCLI11;Server=$server;Database=$database;Uid=$user;Pwd=$pass");
        $recordset = new \COM("ADODB.RecordSet");
        ////\Debugbar::info("mainsql: " . $mainsql);
        $recordset->open($mainsql, $conn, 3);


        try {
            $creport->Database->setDataSource($recordset, 3, 1);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        //Sub report
        $subcount = count($subreport);
        for ($k = 0; $k < $subcount; $k++) {
            $sr = (string)$subreport[$k];
            try {
                //Set datasource for subreport
                $subrecordset = new \COM("ADODB.RecordSet");
                ////\Debugbar::info("subsql: " . $sqlsub[$k]);
                $subrecordset->open($sqlsub[$k], $conn, 3);
                if (strpos($sr, '.rpt') == -1) {
                    $sr = $sr . ".rpt";
                }
                $creport->OpenSubreport($sr)->Database->setDataSource($subrecordset, 3);
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }
        }
        //------ DiscardSavedData make a Refresh in your data -------

        try {
            $creport->DiscardSavedData;
            $creport->ReadRecords();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        $filename = str_replace(".rpt", "", $reportid) . "_" . self::getUserID() . "_" . strtoupper(Str::random(8)) . ".pdf";
        $my_pdf = storage_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $filename;
        $creport->ExportOptions->DiskFileName = $my_pdf; //export to pdf
        $creport->ExportOptions->PDFExportAllPages = true;
        $creport->ExportOptions->DestinationType = 1; // export to file
        $creport->ExportOptions->FormatType = 31; // 31 = PDF, 36 = XLS, 14 =DOC
        $creport->Export(false);

        //------ Release the variables ------
        $recordset->Close();
        $recordset = null;
        $creport = null;
        $crapp = null;
        $ObjectFactory = null;
        return $filename;

    }


    // tiền bằng chữ
    public static function convert_number_to_words($number)
    {
        $number = floatval($number);
        $hyphen = ' ';
        $conjunction = ' và ';
        $separator = ' ';
        $negative = 'âm ';
        $decimal = ' phẩy ';
        $dictionary = array(
            0 => 'không',
            1 => 'một',
            2 => 'hai',
            3 => 'ba',
            4 => 'bốn',
            5 => 'năm',
            6 => 'sáu',
            7 => 'bảy',
            8 => 'tám',
            9 => 'chín',
            10 => 'mười',
            11 => 'mười một',
            12 => 'mười hai',
            13 => 'mười ba',
            14 => 'mười bốn',
            15 => 'mười lăm',
            16 => 'mười sáu',
            17 => 'mười bảy',
            18 => 'mười tám',
            19 => 'mười chín',
            20 => 'hai mươi',
            30 => 'ba mươi',
            40 => 'bốn mươi',
            50 => 'năm mươi',
            60 => 'sáu mươi',
            70 => 'bảy mươi',
            80 => 'tám mươi',
            90 => 'chín mươi',
            100 => 'trăm',
            1000 => 'ngàn',
            1000000 => 'triệu',
            1000000000 => 'tỷ',
            1000000000000 => 'ngàn tỷ',
            1000000000000000 => 'triệu tỷ',
            1000000000000000000 => 'tỷ tỷ'
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int)$number < 0) || (int)$number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . Helpers::convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int)($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $separator . Helpers::convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int)($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = Helpers::convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= Helpers::convert_number_to_words($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            //$string .= $decimal;
//        $words = array();
//        foreach (str_split((string) $fraction) as $number) {
//            $words[] = $dictionary[$number];
//        }
//        $string .= implode(' ', $words);
        }

        return $string;
    }

    // class icon cho loại file
    public static function geticonfile($ext)
    {
        $class_file = "fa-file-o";
        if (strtolower($ext) == "doc" || strtolower($ext) == "docx")
            $class_file = "fa-file-word-o";
        elseif (strtolower($ext) == "xls" || strtolower($ext) == "xlsx")
            $class_file = "fa-file-excel-o";
        elseif (strtolower($ext) == "png" || strtolower($ext) == "bmp" || strtolower($ext) == "jpg" || strtolower($ext) == "jpeg")
            $class_file = "fa-file-image-o";
        elseif (strtolower($ext) == "pdf")
            $class_file = "fa-file-pdf-o";
        elseif (strtolower($ext) == "txt")
            $class_file = "fa-file-text-o";
        elseif (strtolower($ext) == "rar" || strtolower($ext) == "zip")
            $class_file = "fa-file-archive-o";
        return $class_file;
    }

    // tính tổng cho 1 field trên lưới để hiển thị dưới footer
    public static function sumFooter($array, $field, $isArray = false)
    {
        $rs = 0;
        //////\Debugbar::info($array);
        foreach ($array as $row) {
            if ($isArray == false)
                $rs += $row[$field];
            else
                $rs += $row->$field;
        }
        return $rs;
    }

    // chuyền từ d/m/Y sang m/d/Y
    public static function convertDate($dateInput, $isReturnNull = true)
    {
        if ($dateInput == "")
            return ($isReturnNull == true ? "null" : "''");
        return "'" . DateTime::createFromFormat('d/m/Y', $dateInput)->format('m/d/Y') . "'";
    }

    //output Ymd
    public static function convertDateToStringWithFormat($dateInput, $format, $quote = false, $isReturnNull = true)
    {
        if ($dateInput == "")
            return ($isReturnNull == true ? "null" : "''");
        if ($quote) {
            return "'" . DateTime::createFromFormat('d/m/Y', $dateInput)->format($format) . "'";
        } else {
            return DateTime::createFromFormat('d/m/Y', $dateInput)->format($format);
        }
    }

    public static function changeDate($date)
    {
        if ($date == "")
            return '';
        return DateTime::createFromFormat('d/m/Y', $date)->format('d-m-Y');
    }

    // kiểm tra trình duyệt có tương thích hay không
    public static function getUserAgent()
    {
        $agent = new Agent();
        $sBrowseTYpe = $agent->browser();
        $version = $agent->version($sBrowseTYpe);
        if ($sBrowseTYpe == 'Safari') return true;
        elseif ($sBrowseTYpe == 'Chrome' && floatval($version) < 39) return false;
        elseif ($sBrowseTYpe == 'Firefox' && floatval($version) < 39) return false;
        elseif ($sBrowseTYpe == 'IE' && floatval($version) <= 9) return false;
        else return true;
    }

    // lấy resource
    public static function getRS($key)
    {
        if (\session('IsCustomResource') == 1) {
            return __("custom." . $key, [], self::getLocale());
        } else return __("message." . $key, [], self::getLocale());
    }


    // chuyển có dấu sang không dấu
    public static function utf8_to_ascii($str)
    {
        if (!$str) return false;
        $unicode = array(
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'd' => 'đ',
            'D' => 'Đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
        );
        foreach ($unicode as $nonUnicode => $uni)
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        return $str;
    }

    // kiểm tra đăng nhập service
    // dùng để kiểm tra license
    public static function checkLoginWeb($sUserID)
    {
        if (Config::get('app.checkLicense') != false) {
            $agent = new Agent();
            $sBrowseTYpe = $agent->browser();
            $urlService = Config::get('services.diginet.url');
            $sDeviceType = Helpers::getDevice() . Session::getId();
            $gsTYPEENCRYPT = "DED0104";
            $gsCodeString = "CCuL40";

            $objectDLL = new DOTNET("D00D9010,Version=1.0.5911.28757,Culture=neutral,PublicKeyToken=3072ac21984585ef", "D00D9010.D00C9010");
            $xCode = $objectDLL->ChangeValue($gsCodeString, 1, $gsTYPEENCRYPT);

            try {
                $client = new SoapClient($urlService, array('soap_version' => SOAP_1_1));
                $params = ['sXCode' => htmlentities($xCode), 'sDeviceType' => $sDeviceType, 'sBrowseType' => $sBrowseTYpe, 'sUserID' => $sUserID];
                $rsValue = $client->CheckLoginWeb($params)->CheckLogInWebResult;
                $rsValue = $objectDLL->EncodeInformation($rsValue, $gsTYPEENCRYPT);
                //////\Debugbar::info('$rsValue.' . $rsValue);
                $rsValueArr = explode($objectDLL->STR_RESULTLOGININFO, $rsValue);
                //////\Debugbar::info($rsValueArr);
                return $rsValueArr;
            } catch (Exception $e) {
                $e->getMessage();
                return null;
            }
        } else
            return [0 => "ON", 1 => "0"];

    }

    // khi đăng xuất giảm 1 kết nối
    public static function checkLogOut($sText)
    {
        if (Config::get('app.checkLicense') == false)
            return "";
        else {
            $urlService = Config::get('services.diginet.url');
            $objectDLL = new DOTNET("D00D9010,Version=1.0.5911.28757,Culture=neutral,PublicKeyToken=3072ac21984585ef", "D00D9010.D00C9010");
            $xCode = $objectDLL->ChangeValue(Helpers::$gsXCodeString, 1, Helpers::$gsTypeEncrypt);
            try {
                $client = new SoapClient($urlService, array('soap_version' => SOAP_1_1));
                $sText = $objectDLL->CodeLogoutInformation($sText, Helpers::$gsTypeEncrypt);
                return $client->CheckLogOut(['sText' => $sText, 'sXCode' => $xCode]);
            } catch (Exception $e) {
//                Debugbar::info($e->getMessage());
                return null;
            }
        }
    }

    // kiểm tra kết nối đến server
    public static function checkLicensingTimer()
    {
        if (Config::get('app.checkLicense') == false)
            return 0;
        else {
            $urlService = Config::get('services.diginet.url');
            try {
                $client = new SoapClient($urlService, array('soap_version' => SOAP_1_1));
            } catch (SoapFault $e) {
                return 2;
            }
            try {
                $objectDLL = new DOTNET("D00D9010,Version=1.0.5911.28757,Culture=neutral,PublicKeyToken=3072ac21984585ef", "D00D9010.D00C9010");
                $xCode = $objectDLL->ChangeValue(Helpers::$gsXCodeString, 1, Helpers::$gsTypeEncrypt);
                $params = ['sXCode' => htmlentities($xCode)];
                $sText = Session::get('sText');
                $sSession_Segment = $client->CheckLicenseService($params)->CheckLicenseServiceResult;
                $sSession_Segment_EnCode = $objectDLL->EncodeInformation($sSession_Segment, Helpers::$gsTypeEncrypt);

                if ($sSession_Segment_EnCode == substr($sText, 0, 12)) {

                    $sSession_Code = $objectDLL->CodeInformation($sText, Helpers::$gsTypeEncrypt);

                    $sInvalidSession = $client->CheckInvalidSession(['sText' => $sSession_Code, 'sXCode' => $xCode])->CheckInvalidSessionResult;
                    $sInvalidSession_EnCode = $objectDLL->EncodeInformation($sInvalidSession, Helpers::$gsTypeEncrypt);

                    if ($sInvalidSession_EnCode == $objectDLL->ChangeValue($sText, 1, Helpers::$gsTypeEncrypt)) {
                        return 0;
                    } else {
                        //echo "<script>alert($sInvalidSession)</script>";
                        //die;
                        return 4;
                    }


                } else
                    return 1;
            } catch (Exception $e) {
                return 3;
            }
        }
    }

    // kiểm tra có phải là mobile không
    // nếu là mobile thì return "M" không thì trả ra ""
    public static function mobileSuffix()
    {
        $agent = new Agent();
        if ($agent->isTablet()) return "M";
        if ($agent->isMobile()) return "M";
        return "";
    }

    // get Device
    public static function getDevice()
    {
        $agent = new Agent();
        if ($agent->isTablet()) return "TABLET";
        if ($agent->isMobile()) return "MOBILE";
        return "DESKTOP";
    }

    public static function ExportFile($dataExport)
    {
        $filename = "so_" . time();
        Excel::create($filename, function ($excel) use ($dataExport) {
            $excel->sheet('sheet1', function ($sheet) use ($dataExport) {
                $sheet->fromArray($dataExport);
            });
        })->store('xls', Config::get('app.path_export'));

        return url(Config::get('app.path_export') . "/" . $filename . ".xls");
    }

    public static function encryptKey($str)
    {
        $key = 'aLw02Wdkl0InGsa0CpD1WEsanLemonweb4';
        // initialization vector
        $iv = md5(md5($key));
        $qEncoded = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $str, MCRYPT_MODE_CBC, $iv));
        return str_replace("%2F", "%252F", urlencode($qEncoded));
    }

    public static function decryptKey($str)
    {
        $key = 'aLw02Wdkl0InGsa0CpD1WEsanLemonweb4';
        $str = urldecode(str_replace("%252F", "%2F", urlencode($str)));
        // initialization vector
        $iv = md5(md5($key));
        $qDecoded = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($str), MCRYPT_MODE_CBC, $iv), "\0");
        return ($qDecoded);
    }

    //lấy chuỗi format bằng cách truyền vào số format
    public static function getStringFormat($decimal, $prefix = '', $suffix = '')
    {
        switch ($decimal) {
            case 0:
                return $prefix . '##,###' . $suffix;
            case 1:
                return $prefix . '##,###.0' . $suffix;
            case 2:
                return $prefix . '##,###.00' . $suffix;
            case 3:
                return $prefix . '##,###.000' . $suffix;
            case 4:
                return $prefix . '##,###.0000' . $suffix;
            case 5:
                return $prefix . '##,###.00000' . $suffix;
            case 6:
                return $prefix . '##,###.000000' . $suffix;
            case 7:
                return $prefix . '##,###.0000000' . $suffix;
            case 8:
                return $prefix . '##,###.00000000' . $suffix;
            default:
                return $prefix . '##,###.00' . $suffix;
        }
    }

    //lấy format từ row mà sql trả ra
    public static function getStringFormat2($arr, $field, $prefix = '', $suffix = '')
    {
        if (count($arr) > 0)
            $decimal = $arr[0][$field];
        else
            $decimal = 2;
        switch ($decimal) {
            case 0:
                return $prefix . '##,###' . $suffix;
            case 1:
                return $prefix . '##,###.0' . $suffix;
            case 2:
                return $prefix . '##,###.00' . $suffix;
            case 3:
                return $prefix . '##,###.000' . $suffix;
            case 4:
                return $prefix . '##,###.0000' . $suffix;
            case 5:
                return $prefix . '##,###.00000' . $suffix;
            case 6:
                return $prefix . '##,###.000000' . $suffix;
            case 7:
                return $prefix . '##,###.0000000' . $suffix;
            case 8:
                return $prefix . '##,###.00000000' . $suffix;
            default:
                return $prefix . '##,###.00' . $suffix;
        }
    }


    public static function decode_string($string)
    {
        $str = $string;
        $str = str_replace('%2F', '/', $str);
        $str = str_replace('%26', '&', $str);
        $str = str_replace('%2C', ',', $str);
        $str = str_replace('%5C', '\\', $str);
        $str = str_replace('%2E', '.', $str);
        $str = str_replace('%20', ' ', $str);
        return $str;
    }

    public static function sqlNumber($val)
    {
        if ($val == '' || $val == null) return 0;
        return str_replace(",", "", $val);
    }

    public static function sqlNumberNull($val)
    {
        if ($val == '') return DB::raw('null');
        return str_replace(",", "", $val);
    }

    public static function formatNegativeNumber($num, $dec = 0)
    {
        if ($num >= 0) return number_format($num, $dec);
        $num = number_format($num, $dec);
        return '(' . str_replace('-', '', $num) . ')';
    }

    public static function get_content_type($filename)
    {

//        $mime_types = array(
//            'txt' => 'text/plain',
//            'htm' => 'text/html',
//            'html' => 'text/html',
//            'php' => 'text/html',
//            'css' => 'text/css',
//            'js' => 'application/javascript',
//            'json' => 'application/json',
//            'xml' => 'application/xml',
//            'swf' => 'application/x-shockwave-flash',
//            'flv' => 'video/x-flv',
//
//            // images
//            'png' => 'image/png',
//            'jpe' => 'image/jpeg',
//            'jpeg' => 'image/jpeg',
//            'jpg' => 'image/jpeg',
//            'gif' => 'image/gif',
//            'bmp' => 'image/bmp',
//            'ico' => 'image/vnd.microsoft.icon',
//            'tiff' => 'image/tiff',
//            'tif' => 'image/tiff',
//            'svg' => 'image/svg+xml',
//            'svgz' => 'image/svg+xml',
//
//            // archives
//            'zip' => 'application/zip',
//            'rar' => 'application/x-rar-compressed',
//            'exe' => 'application/x-msdownload',
//            'msi' => 'application/x-msdownload',
//            'cab' => 'application/vnd.ms-cab-compressed',
//
//            // audio/video
//            'mp3' => 'audio/mpeg',
//            'qt' => 'video/quicktime',
//            'mov' => 'video/quicktime',
//
//            // adobe
//            'pdf' => 'application/pdf',
//            'psd' => 'image/vnd.adobe.photoshop',
//            'ai' => 'application/postscript',
//            'eps' => 'application/postscript',
//            'ps' => 'application/postscript',
//
//            // ms office
//            'doc' => 'application/msword',
//            'docx' => 'application/msword',
//            'rtf' => 'application/rtf',
//            'xls' => 'application/vnd.ms-excel',
//            'xlsx' => 'application/vnd.ms-excel',
//            'ppt' => 'application/vnd.ms-powerpoint',
//            'pptx' => 'application/vnd.ms-powerpoint',
//
//            // open office
//            'odt' => 'application/vnd.oasis.opendocument.text',
//            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
//        );
        $mime_types = array(
            '3dml' => 'text/vnd.in3d.3dml',
            '3ds' => 'image/x-3ds',
            '3g2' => 'video/3gpp2',
            '3gp' => 'video/3gpp',
            '7z' => 'application/x-7z-compressed',
            'aab' => 'application/x-authorware-bin',
            'aac' => 'audio/x-aac',
            'aam' => 'application/x-authorware-map',
            'aas' => 'application/x-authorware-seg',
            'abw' => 'application/x-abiword',
            'ac' => 'application/pkix-attr-cert',
            'acc' => 'application/vnd.americandynamics.acc',
            'ace' => 'application/x-ace-compressed',
            'acu' => 'application/vnd.acucobol',
            'acutc' => 'application/vnd.acucorp',
            'adp' => 'audio/adpcm',
            'aep' => 'application/vnd.audiograph',
            'afm' => 'application/x-font-type1',
            'afp' => 'application/vnd.ibm.modcap',
            'ahead' => 'application/vnd.ahead.space',
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'air' => 'application/vnd.adobe.air-application-installer-package+zip',
            'ait' => 'application/vnd.dvb.ait',
            'ami' => 'application/vnd.amiga.ami',
            'apk' => 'application/vnd.android.package-archive',
            'appcache' => 'text/cache-manifest',
            'apr' => 'application/vnd.lotus-approach',
            'aps' => 'application/postscript',
            'arc' => 'application/x-freearc',
            'asc' => 'application/pgp-signature',
            'asf' => 'video/x-ms-asf',
            'asm' => 'text/x-asm',
            'aso' => 'application/vnd.accpac.simply.aso',
            'asx' => 'video/x-ms-asf',
            'atc' => 'application/vnd.acucorp',
            'atom' => 'application/atom+xml',
            'atomcat' => 'application/atomcat+xml',
            'atomsvc' => 'application/atomsvc+xml',
            'atx' => 'application/vnd.antix.game-component',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'aw' => 'application/applixware',
            'azf' => 'application/vnd.airzip.filesecure.azf',
            'azs' => 'application/vnd.airzip.filesecure.azs',
            'azw' => 'application/vnd.amazon.ebook',
            'bat' => 'application/x-msdownload',
            'bcpio' => 'application/x-bcpio',
            'bdf' => 'application/x-font-bdf',
            'bdm' => 'application/vnd.syncml.dm+wbxml',
            'bed' => 'application/vnd.realvnc.bed',
            'bh2' => 'application/vnd.fujitsu.oasysprs',
            'bin' => 'application/octet-stream',
            'blb' => 'application/x-blorb',
            'blorb' => 'application/x-blorb',
            'bmi' => 'application/vnd.bmi',
            'bmp' => 'image/bmp',
            'book' => 'application/vnd.framemaker',
            'box' => 'application/vnd.previewsystems.box',
            'boz' => 'application/x-bzip2',
            'bpk' => 'application/octet-stream',
            'btif' => 'image/prs.btif',
            'bz' => 'application/x-bzip',
            'bz2' => 'application/x-bzip2',
            'c' => 'text/x-c',
            'c11amc' => 'application/vnd.cluetrust.cartomobile-config',
            'c11amz' => 'application/vnd.cluetrust.cartomobile-config-pkg',
            'c4d' => 'application/vnd.clonk.c4group',
            'c4f' => 'application/vnd.clonk.c4group',
            'c4g' => 'application/vnd.clonk.c4group',
            'c4p' => 'application/vnd.clonk.c4group',
            'c4u' => 'application/vnd.clonk.c4group',
            'cab' => 'application/vnd.ms-cab-compressed',
            'caf' => 'audio/x-caf',
            'cap' => 'application/vnd.tcpdump.pcap',
            'car' => 'application/vnd.curl.car',
            'cat' => 'application/vnd.ms-pki.seccat',
            'cb7' => 'application/x-cbr',
            'cba' => 'application/x-cbr',
            'cbr' => 'application/x-cbr',
            'cbt' => 'application/x-cbr',
            'cbz' => 'application/x-cbr',
            'cc' => 'text/x-c',
            'cct' => 'application/x-director',
            'ccxml' => 'application/ccxml+xml',
            'cdbcmsg' => 'application/vnd.contact.cmsg',
            'cdf' => 'application/x-netcdf',
            'cdkey' => 'application/vnd.mediastation.cdkey',
            'cdmia' => 'application/cdmi-capability',
            'cdmic' => 'application/cdmi-container',
            'cdmid' => 'application/cdmi-domain',
            'cdmio' => 'application/cdmi-object',
            'cdmiq' => 'application/cdmi-queue',
            'cdx' => 'chemical/x-cdx',
            'cdxml' => 'application/vnd.chemdraw+xml',
            'cdy' => 'application/vnd.cinderella',
            'cer' => 'application/pkix-cert',
            'cfs' => 'application/x-cfs-compressed',
            'cgm' => 'image/cgm',
            'chat' => 'application/x-chat',
            'chm' => 'application/vnd.ms-htmlhelp',
            'chrt' => 'application/vnd.kde.kchart',
            'cif' => 'chemical/x-cif',
            'cii' => 'application/vnd.anser-web-certificate-issue-initiation',
            'cil' => 'application/vnd.ms-artgalry',
            'cla' => 'application/vnd.claymore',
            'class' => 'application/java-vm',
            'clkk' => 'application/vnd.crick.clicker.keyboard',
            'clkp' => 'application/vnd.crick.clicker.palette',
            'clkt' => 'application/vnd.crick.clicker.template',
            'clkw' => 'application/vnd.crick.clicker.wordbank',
            'clkx' => 'application/vnd.crick.clicker',
            'clp' => 'application/x-msclip',
            'cmc' => 'application/vnd.cosmocaller',
            'cmdf' => 'chemical/x-cmdf',
            'cml' => 'chemical/x-cml',
            'cmp' => 'application/vnd.yellowriver-custom-menu',
            'cmx' => 'image/x-cmx',
            'cod' => 'application/vnd.rim.cod',
            'com' => 'application/x-msdownload',
            'conf' => 'text/plain',
            'cpio' => 'application/x-cpio',
            'cpp' => 'text/x-c',
            'cpt' => 'application/mac-compactpro',
            'crd' => 'application/x-mscardfile',
            'crl' => 'application/pkix-crl',
            'crt' => 'application/x-x509-ca-cert',
            'csh' => 'application/x-csh',
            'csml' => 'chemical/x-csml',
            'csp' => 'application/vnd.commonspace',
            'css' => 'text/css',
            'cst' => 'application/x-director',
            'csv' => 'text/csv',
            'cu' => 'application/cu-seeme',
            'curl' => 'text/vnd.curl',
            'cww' => 'application/prs.cww',
            'cxt' => 'application/x-director',
            'cxx' => 'text/x-c',
            'dae' => 'model/vnd.collada+xml',
            'daf' => 'application/vnd.mobius.daf',
            'dart' => 'application/vnd.dart',
            'dataless' => 'application/vnd.fdsn.seed',
            'davmount' => 'application/davmount+xml',
            'dbk' => 'application/docbook+xml',
            'dcr' => 'application/x-director',
            'dcurl' => 'text/vnd.curl.dcurl',
            'dd2' => 'application/vnd.oma.dd2+xml',
            'ddd' => 'application/vnd.fujixerox.ddd',
            'deb' => 'application/x-debian-package',
            'def' => 'text/plain',
            'deploy' => 'application/octet-stream',
            'der' => 'application/x-x509-ca-cert',
            'dfac' => 'application/vnd.dreamfactory',
            'dgc' => 'application/x-dgc-compressed',
            'dic' => 'text/x-c',
            'dir' => 'application/x-director',
            'dis' => 'application/vnd.mobius.dis',
            'dist' => 'application/octet-stream',
            'distz' => 'application/octet-stream',
            'djv' => 'image/vnd.djvu',
            'djvu' => 'image/vnd.djvu',
            'dll' => 'application/x-msdownload',
            'dmg' => 'application/x-apple-diskimage',
            'dmp' => 'application/vnd.tcpdump.pcap',
            'dms' => 'application/octet-stream',
            'dna' => 'application/vnd.dna',
            'doc' => 'application/msword',
            'docm' => 'application/vnd.ms-word.document.macroenabled.12',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dot' => 'application/msword',
            'dotm' => 'application/vnd.ms-word.template.macroenabled.12',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dp' => 'application/vnd.osgi.dp',
            'dpg' => 'application/vnd.dpgraph',
            'dra' => 'audio/vnd.dra',
            'dsc' => 'text/prs.lines.tag',
            'dssc' => 'application/dssc+der',
            'dtb' => 'application/x-dtbook+xml',
            'dtd' => 'application/xml-dtd',
            'dts' => 'audio/vnd.dts',
            'dtshd' => 'audio/vnd.dts.hd',
            'dump' => 'application/octet-stream',
            'dvb' => 'video/vnd.dvb.file',
            'dvi' => 'application/x-dvi',
            'dwf' => 'model/vnd.dwf',
            'dwg' => 'image/vnd.dwg',
            'dxf' => 'image/vnd.dxf',
            'dxp' => 'application/vnd.spotfire.dxp',
            'dxr' => 'application/x-director',
            'ecelp4800' => 'audio/vnd.nuera.ecelp4800',
            'ecelp7470' => 'audio/vnd.nuera.ecelp7470',
            'ecelp9600' => 'audio/vnd.nuera.ecelp9600',
            'ecma' => 'application/ecmascript',
            'edm' => 'application/vnd.novadigm.edm',
            'edx' => 'application/vnd.novadigm.edx',
            'efif' => 'application/vnd.picsel',
            'ei6' => 'application/vnd.pg.osasli',
            'elc' => 'application/octet-stream',
            'emf' => 'application/x-msmetafile',
            'eml' => 'message/rfc822',
            'emma' => 'application/emma+xml',
            'emz' => 'application/x-msmetafile',
            'eol' => 'audio/vnd.digital-winds',
            'eot' => 'application/vnd.ms-fontobject',
            'eps' => 'application/postscript',
            'epub' => 'application/epub+zip',
            'es3' => 'application/vnd.eszigno3+xml',
            'esa' => 'application/vnd.osgi.subsystem',
            'esf' => 'application/vnd.epson.esf',
            'et3' => 'application/vnd.eszigno3+xml',
            'etx' => 'text/x-setext',
            'eva' => 'application/x-eva',
            'evy' => 'application/x-envoy',
            'exe' => 'application/x-msdownload',
            'exi' => 'application/exi',
            'ext' => 'application/vnd.novadigm.ext',
            'ez' => 'application/andrew-inset',
            'ez2' => 'application/vnd.ezpix-album',
            'ez3' => 'application/vnd.ezpix-package',
            'f' => 'text/x-fortran',
            'f4v' => 'video/x-f4v',
            'f77' => 'text/x-fortran',
            'f90' => 'text/x-fortran',
            'fbs' => 'image/vnd.fastbidsheet',
            'fcdt' => 'application/vnd.adobe.formscentral.fcdt',
            'fcs' => 'application/vnd.isac.fcs',
            'fdf' => 'application/vnd.fdf',
            'fe_launch' => 'application/vnd.denovo.fcselayout-link',
            'fg5' => 'application/vnd.fujitsu.oasysgp',
            'fgd' => 'application/x-director',
            'fh' => 'image/x-freehand',
            'fh4' => 'image/x-freehand',
            'fh5' => 'image/x-freehand',
            'fh7' => 'image/x-freehand',
            'fhc' => 'image/x-freehand',
            'fig' => 'application/x-xfig',
            'flac' => 'audio/x-flac',
            'fli' => 'video/x-fli',
            'flo' => 'application/vnd.micrografx.flo',
            'flv' => 'video/x-flv',
            'flw' => 'application/vnd.kde.kivio',
            'flx' => 'text/vnd.fmi.flexstor',
            'fly' => 'text/vnd.fly',
            'fm' => 'application/vnd.framemaker',
            'fnc' => 'application/vnd.frogans.fnc',
            'for' => 'text/x-fortran',
            'fpx' => 'image/vnd.fpx',
            'frame' => 'application/vnd.framemaker',
            'fsc' => 'application/vnd.fsc.weblaunch',
            'fst' => 'image/vnd.fst',
            'ftc' => 'application/vnd.fluxtime.clip',
            'fti' => 'application/vnd.anser-web-funds-transfer-initiation',
            'fvt' => 'video/vnd.fvt',
            'fxp' => 'application/vnd.adobe.fxp',
            'fxpl' => 'application/vnd.adobe.fxp',
            'fzs' => 'application/vnd.fuzzysheet',
            'g2w' => 'application/vnd.geoplan',
            'g3' => 'image/g3fax',
            'g3w' => 'application/vnd.geospace',
            'gac' => 'application/vnd.groove-account',
            'gam' => 'application/x-tads',
            'gbr' => 'application/rpki-ghostbusters',
            'gca' => 'application/x-gca-compressed',
            'gdl' => 'model/vnd.gdl',
            'geo' => 'application/vnd.dynageo',
            'gex' => 'application/vnd.geometry-explorer',
            'ggb' => 'application/vnd.geogebra.file',
            'ggt' => 'application/vnd.geogebra.tool',
            'ghf' => 'application/vnd.groove-help',
            'gif' => 'image/gif',
            'gim' => 'application/vnd.groove-identity-message',
            'gml' => 'application/gml+xml',
            'gmx' => 'application/vnd.gmx',
            'gnumeric' => 'application/x-gnumeric',
            'gph' => 'application/vnd.flographit',
            'gpx' => 'application/gpx+xml',
            'gqf' => 'application/vnd.grafeq',
            'gqs' => 'application/vnd.grafeq',
            'gram' => 'application/srgs',
            'gramps' => 'application/x-gramps-xml',
            'gre' => 'application/vnd.geometry-explorer',
            'grv' => 'application/vnd.groove-injector',
            'grxml' => 'application/srgs+xml',
            'gsf' => 'application/x-font-ghostscript',
            'gtar' => 'application/x-gtar',
            'gtm' => 'application/vnd.groove-tool-message',
            'gtw' => 'model/vnd.gtw',
            'gv' => 'text/vnd.graphviz',
            'gxf' => 'application/gxf',
            'gxt' => 'application/vnd.geonext',
            'gz' => 'application/x-gzip',
            'h' => 'text/x-c',
            'h261' => 'video/h261',
            'h263' => 'video/h263',
            'h264' => 'video/h264',
            'hal' => 'application/vnd.hal+xml',
            'hbci' => 'application/vnd.hbci',
            'hdf' => 'application/x-hdf',
            'hh' => 'text/x-c',
            'hlp' => 'application/winhlp',
            'hpgl' => 'application/vnd.hp-hpgl',
            'hpid' => 'application/vnd.hp-hpid',
            'hps' => 'application/vnd.hp-hps',
            'hqx' => 'application/mac-binhex40',
            'htke' => 'application/vnd.kenameaapp',
            'htm' => 'text/html',
            'html' => 'text/html',
            'hvd' => 'application/vnd.yamaha.hv-dic',
            'hvp' => 'application/vnd.yamaha.hv-voice',
            'hvs' => 'application/vnd.yamaha.hv-script',
            'i2g' => 'application/vnd.intergeo',
            'icc' => 'application/vnd.iccprofile',
            'ice' => 'x-conference/x-cooltalk',
            'icm' => 'application/vnd.iccprofile',
            'ico' => 'image/x-icon',
            'ics' => 'text/calendar',
            'ief' => 'image/ief',
            'ifb' => 'text/calendar',
            'ifm' => 'application/vnd.shana.informed.formdata',
            'iges' => 'model/iges',
            'igl' => 'application/vnd.igloader',
            'igm' => 'application/vnd.insors.igm',
            'igs' => 'model/iges',
            'igx' => 'application/vnd.micrografx.igx',
            'iif' => 'application/vnd.shana.informed.interchange',
            'imp' => 'application/vnd.accpac.simply.imp',
            'ims' => 'application/vnd.ms-ims',
            'in' => 'text/plain',
            'ink' => 'application/inkml+xml',
            'inkml' => 'application/inkml+xml',
            'install' => 'application/x-install-instructions',
            'iota' => 'application/vnd.astraea-software.iota',
            'ipfix' => 'application/ipfix',
            'ipk' => 'application/vnd.shana.informed.package',
            'irm' => 'application/vnd.ibm.rights-management',
            'irp' => 'application/vnd.irepository.package+xml',
            'iso' => 'application/x-iso9660-image',
            'itp' => 'application/vnd.shana.informed.formtemplate',
            'ivp' => 'application/vnd.immervision-ivp',
            'ivu' => 'application/vnd.immervision-ivu',
            'jad' => 'text/vnd.sun.j2me.app-descriptor',
            'jam' => 'application/vnd.jam',
            'jar' => 'application/java-archive',
            'java' => 'text/x-java-source',
            'jisp' => 'application/vnd.jisp',
            'jlt' => 'application/vnd.hp-jlyt',
            'jnlp' => 'application/x-java-jnlp-file',
            'joda' => 'application/vnd.joost.joda-archive',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'jpgm' => 'video/jpm',
            'jpgv' => 'video/jpeg',
            'jpm' => 'video/jpm',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'jsonml' => 'application/jsonml+json',
            'kar' => 'audio/midi',
            'karbon' => 'application/vnd.kde.karbon',
            'kfo' => 'application/vnd.kde.kformula',
            'kia' => 'application/vnd.kidspiration',
            'kml' => 'application/vnd.google-earth.kml+xml',
            'kmz' => 'application/vnd.google-earth.kmz',
            'kne' => 'application/vnd.kinar',
            'knp' => 'application/vnd.kinar',
            'kon' => 'application/vnd.kde.kontour',
            'kpr' => 'application/vnd.kde.kpresenter',
            'kpt' => 'application/vnd.kde.kpresenter',
            'kpxx' => 'application/vnd.ds-keypoint',
            'ksp' => 'application/vnd.kde.kspread',
            'ktr' => 'application/vnd.kahootz',
            'ktx' => 'image/ktx',
            'ktz' => 'application/vnd.kahootz',
            'kwd' => 'application/vnd.kde.kword',
            'kwt' => 'application/vnd.kde.kword',
            'lasxml' => 'application/vnd.las.las+xml',
            'latex' => 'application/x-latex',
            'lbd' => 'application/vnd.llamagraphics.life-balance.desktop',
            'lbe' => 'application/vnd.llamagraphics.life-balance.exchange+xml',
            'les' => 'application/vnd.hhe.lesson-player',
            'lha' => 'application/x-lzh-compressed',
            'link66' => 'application/vnd.route66.link66+xml',
            'list' => 'text/plain',
            'list3820' => 'application/vnd.ibm.modcap',
            'listafp' => 'application/vnd.ibm.modcap',
            'lnk' => 'application/x-ms-shortcut',
            'log' => 'text/plain',
            'lostxml' => 'application/lost+xml',
            'lrf' => 'application/octet-stream',
            'lrm' => 'application/vnd.ms-lrm',
            'ltf' => 'application/vnd.frogans.ltf',
            'lvp' => 'audio/vnd.lucent.voice',
            'lwp' => 'application/vnd.lotus-wordpro',
            'lzh' => 'application/x-lzh-compressed',
            'm13' => 'application/x-msmediaview',
            'm14' => 'application/x-msmediaview',
            'm1v' => 'video/mpeg',
            'm21' => 'application/mp21',
            'm2a' => 'audio/mpeg',
            'm2v' => 'video/mpeg',
            'm3a' => 'audio/mpeg',
            'm3u' => 'audio/x-mpegurl',
            'm3u8' => 'application/vnd.apple.mpegurl',
            'm4a' => 'audio/mp4',
            'm4u' => 'video/vnd.mpegurl',
            'm4v' => 'video/x-m4v',
            'ma' => 'application/mathematica',
            'mads' => 'application/mads+xml',
            'mag' => 'application/vnd.ecowin.chart',
            'maker' => 'application/vnd.framemaker',
            'man' => 'text/troff',
            'mar' => 'application/octet-stream',
            'mathml' => 'application/mathml+xml',
            'mb' => 'application/mathematica',
            'mbk' => 'application/vnd.mobius.mbk',
            'mbox' => 'application/mbox',
            'mc1' => 'application/vnd.medcalcdata',
            'mcd' => 'application/vnd.mcd',
            'mcurl' => 'text/vnd.curl.mcurl',
            'mdb' => 'application/x-msaccess',
            'mdi' => 'image/vnd.ms-modi',
            'me' => 'text/troff',
            'mesh' => 'model/mesh',
            'meta4' => 'application/metalink4+xml',
            'metalink' => 'application/metalink+xml',
            'mets' => 'application/mets+xml',
            'mfm' => 'application/vnd.mfmp',
            'mft' => 'application/rpki-manifest',
            'mgp' => 'application/vnd.osgeo.mapguide.package',
            'mgz' => 'application/vnd.proteus.magazine',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mie' => 'application/x-mie',
            'mif' => 'application/vnd.mif',
            'mime' => 'message/rfc822',
            'mj2' => 'video/mj2',
            'mjp2' => 'video/mj2',
            'mk3d' => 'video/x-matroska',
            'mka' => 'audio/x-matroska',
            'mks' => 'video/x-matroska',
            'mkv' => 'video/x-matroska',
            'mlp' => 'application/vnd.dolby.mlp',
            'mmd' => 'application/vnd.chipnuts.karaoke-mmd',
            'mmf' => 'application/vnd.smaf',
            'mmr' => 'image/vnd.fujixerox.edmics-mmr',
            'mng' => 'video/x-mng',
            'mny' => 'application/x-msmoney',
            'mobi' => 'application/x-mobipocket-ebook',
            'mods' => 'application/mods+xml',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp21' => 'application/mp21',
            'mp2a' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mp4a' => 'audio/mp4',
            'mp4s' => 'application/mp4',
            'mp4v' => 'video/mp4',
            'mpc' => 'application/vnd.mophun.certificate',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpg4' => 'video/mp4',
            'mpga' => 'audio/mpeg',
            'mpkg' => 'application/vnd.apple.installer+xml',
            'mpm' => 'application/vnd.blueice.multipass',
            'mpn' => 'application/vnd.mophun.application',
            'mpp' => 'application/vnd.ms-project',
            'mpt' => 'application/vnd.ms-project',
            'mpy' => 'application/vnd.ibm.minipay',
            'mqy' => 'application/vnd.mobius.mqy',
            'mrc' => 'application/marc',
            'mrcx' => 'application/marcxml+xml',
            'ms' => 'text/troff',
            'mscml' => 'application/mediaservercontrol+xml',
            'mseed' => 'application/vnd.fdsn.mseed',
            'mseq' => 'application/vnd.mseq',
            'msf' => 'application/vnd.epson.msf',
            'msh' => 'model/mesh',
            'msi' => 'application/x-msdownload',
            'msl' => 'application/vnd.mobius.msl',
            'msty' => 'application/vnd.muvee.style',
            'mts' => 'model/vnd.mts',
            'mus' => 'application/vnd.musician',
            'musicxml' => 'application/vnd.recordare.musicxml+xml',
            'mvb' => 'application/x-msmediaview',
            'mwf' => 'application/vnd.mfer',
            'mxf' => 'application/mxf',
            'mxl' => 'application/vnd.recordare.musicxml',
            'mxml' => 'application/xv+xml',
            'mxs' => 'application/vnd.triscape.mxs',
            'mxu' => 'video/vnd.mpegurl',
            'n-gage' => 'application/vnd.nokia.n-gage.symbian.install',
            'n3' => 'text/n3',
            'nb' => 'application/mathematica',
            'nbp' => 'application/vnd.wolfram.player',
            'nc' => 'application/x-netcdf',
            'ncx' => 'application/x-dtbncx+xml',
            'nfo' => 'text/x-nfo',
            'ngdat' => 'application/vnd.nokia.n-gage.data',
            'nitf' => 'application/vnd.nitf',
            'nlu' => 'application/vnd.neurolanguage.nlu',
            'nml' => 'application/vnd.enliven',
            'nnd' => 'application/vnd.noblenet-directory',
            'nns' => 'application/vnd.noblenet-sealer',
            'nnw' => 'application/vnd.noblenet-web',
            'npx' => 'image/vnd.net-fpx',
            'nsc' => 'application/x-conference',
            'nsf' => 'application/vnd.lotus-notes',
            'ntf' => 'application/vnd.nitf',
            'nzb' => 'application/x-nzb',
            'oa2' => 'application/vnd.fujitsu.oasys2',
            'oa3' => 'application/vnd.fujitsu.oasys3',
            'oas' => 'application/vnd.fujitsu.oasys',
            'obd' => 'application/x-msbinder',
            'obj' => 'application/x-tgif',
            'oda' => 'application/oda',
            'odb' => 'application/vnd.oasis.opendocument.database',
            'odc' => 'application/vnd.oasis.opendocument.chart',
            'odf' => 'application/vnd.oasis.opendocument.formula',
            'odft' => 'application/vnd.oasis.opendocument.formula-template',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'odi' => 'application/vnd.oasis.opendocument.image',
            'odm' => 'application/vnd.oasis.opendocument.text-master',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'oga' => 'audio/ogg',
            'ogg' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'omdoc' => 'application/omdoc+xml',
            'onepkg' => 'application/onenote',
            'onetmp' => 'application/onenote',
            'onetoc' => 'application/onenote',
            'onetoc2' => 'application/onenote',
            'opf' => 'application/oebps-package+xml',
            'opml' => 'text/x-opml',
            'oprc' => 'application/vnd.palm',
            'org' => 'application/vnd.lotus-organizer',
            'osf' => 'application/vnd.yamaha.openscoreformat',
            'osfpvg' => 'application/vnd.yamaha.openscoreformat.osfpvg+xml',
            'otc' => 'application/vnd.oasis.opendocument.chart-template',
            'otf' => 'application/x-font-otf',
            'otg' => 'application/vnd.oasis.opendocument.graphics-template',
            'oth' => 'application/vnd.oasis.opendocument.text-web',
            'oti' => 'application/vnd.oasis.opendocument.image-template',
            'otp' => 'application/vnd.oasis.opendocument.presentation-template',
            'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'ott' => 'application/vnd.oasis.opendocument.text-template',
            'oxps' => 'application/oxps',
            'oxt' => 'application/vnd.openofficeorg.extension',
            'p' => 'text/x-pascal',
            'p10' => 'application/pkcs10',
            'p12' => 'application/x-pkcs12',
            'p7b' => 'application/x-pkcs7-certificates',
            'p7c' => 'application/pkcs7-mime',
            'p7m' => 'application/pkcs7-mime',
            'p7r' => 'application/x-pkcs7-certreqresp',
            'p7s' => 'application/pkcs7-signature',
            'p8' => 'application/pkcs8',
            'pas' => 'text/x-pascal',
            'paw' => 'application/vnd.pawaafile',
            'pbd' => 'application/vnd.powerbuilder6',
            'pbm' => 'image/x-portable-bitmap',
            'pcap' => 'application/vnd.tcpdump.pcap',
            'pcf' => 'application/x-font-pcf',
            'pcl' => 'application/vnd.hp-pcl',
            'pclxl' => 'application/vnd.hp-pclxl',
            'pct' => 'image/x-pict',
            'pcurl' => 'application/vnd.curl.pcurl',
            'pcx' => 'image/x-pcx',
            'pdb' => 'application/vnd.palm',
            'pdf' => 'application/pdf',
            'pfa' => 'application/x-font-type1',
            'pfb' => 'application/x-font-type1',
            'pfm' => 'application/x-font-type1',
            'pfr' => 'application/font-tdpfr',
            'pfx' => 'application/x-pkcs12',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'pgp' => 'application/pgp-encrypted',
            'php' => 'application/x-php',
            'php3' => 'application/x-php',
            'php4' => 'application/x-php',
            'php5' => 'application/x-php',
            'pic' => 'image/x-pict',
            'pkg' => 'application/octet-stream',
            'pki' => 'application/pkixcmp',
            'pkipath' => 'application/pkix-pkipath',
            'plb' => 'application/vnd.3gpp.pic-bw-large',
            'plc' => 'application/vnd.mobius.plc',
            'plf' => 'application/vnd.pocketlearn',
            'pls' => 'application/pls+xml',
            'pml' => 'application/vnd.ctc-posml',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'portpkg' => 'application/vnd.macports.portpkg',
            'pot' => 'application/vnd.ms-powerpoint',
            'potm' => 'application/vnd.ms-powerpoint.template.macroenabled.12',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroenabled.12',
            'ppd' => 'application/vnd.cups-ppd',
            'ppm' => 'image/x-portable-pixmap',
            'pps' => 'application/vnd.ms-powerpoint',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroenabled.12',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pqa' => 'application/vnd.palm',
            'prc' => 'application/x-mobipocket-ebook',
            'pre' => 'application/vnd.lotus-freelance',
            'prf' => 'application/pics-rules',
            'ps' => 'application/postscript',
            'psb' => 'application/vnd.3gpp.pic-bw-small',
            'psd' => 'image/vnd.adobe.photoshop',
            'psf' => 'application/x-font-linux-psf',
            'pskcxml' => 'application/pskc+xml',
            'ptid' => 'application/vnd.pvi.ptid1',
            'pub' => 'application/x-mspublisher',
            'pvb' => 'application/vnd.3gpp.pic-bw-var',
            'pwn' => 'application/vnd.3m.post-it-notes',
            'pya' => 'audio/vnd.ms-playready.media.pya',
            'pyv' => 'video/vnd.ms-playready.media.pyv',
            'qam' => 'application/vnd.epson.quickanime',
            'qbo' => 'application/vnd.intu.qbo',
            'qfx' => 'application/vnd.intu.qfx',
            'qps' => 'application/vnd.publishare-delta-tree',
            'qt' => 'video/quicktime',
            'qwd' => 'application/vnd.quark.quarkxpress',
            'qwt' => 'application/vnd.quark.quarkxpress',
            'qxb' => 'application/vnd.quark.quarkxpress',
            'qxd' => 'application/vnd.quark.quarkxpress',
            'qxl' => 'application/vnd.quark.quarkxpress',
            'qxt' => 'application/vnd.quark.quarkxpress',
            'ra' => 'audio/x-pn-realaudio',
            'ram' => 'audio/x-pn-realaudio',
            'rar' => 'application/x-rar-compressed',
            'ras' => 'image/x-cmu-raster',
            'rcprofile' => 'application/vnd.ipunplugged.rcprofile',
            'rdf' => 'application/rdf+xml',
            'rdz' => 'application/vnd.data-vision.rdz',
            'rep' => 'application/vnd.businessobjects',
            'res' => 'application/x-dtbresource+xml',
            'rgb' => 'image/x-rgb',
            'rif' => 'application/reginfo+xml',
            'rip' => 'audio/vnd.rip',
            'ris' => 'application/x-research-info-systems',
            'rl' => 'application/resource-lists+xml',
            'rlc' => 'image/vnd.fujixerox.edmics-rlc',
            'rld' => 'application/resource-lists-diff+xml',
            'rm' => 'application/vnd.rn-realmedia',
            'rmi' => 'audio/midi',
            'rmp' => 'audio/x-pn-realaudio-plugin',
            'rms' => 'application/vnd.jcp.javame.midlet-rms',
            'rmvb' => 'application/vnd.rn-realmedia-vbr',
            'rnc' => 'application/relax-ng-compact-syntax',
            'roa' => 'application/rpki-roa',
            'roff' => 'text/troff',
            'rp9' => 'application/vnd.cloanto.rp9',
            'rpss' => 'application/vnd.nokia.radio-presets',
            'rpst' => 'application/vnd.nokia.radio-preset',
            'rq' => 'application/sparql-query',
            'rs' => 'application/rls-services+xml',
            'rsd' => 'application/rsd+xml',
            'rss' => 'application/rss+xml',
            'rtf' => 'application/rtf',
            'rtx' => 'text/richtext',
            's' => 'text/x-asm',
            's3m' => 'audio/s3m',
            'saf' => 'application/vnd.yamaha.smaf-audio',
            'sbml' => 'application/sbml+xml',
            'sc' => 'application/vnd.ibm.secure-container',
            'scd' => 'application/x-msschedule',
            'scm' => 'application/vnd.lotus-screencam',
            'scq' => 'application/scvp-cv-request',
            'scs' => 'application/scvp-cv-response',
            'scurl' => 'text/vnd.curl.scurl',
            'sda' => 'application/vnd.stardivision.draw',
            'sdc' => 'application/vnd.stardivision.calc',
            'sdd' => 'application/vnd.stardivision.impress',
            'sdkd' => 'application/vnd.solent.sdkm+xml',
            'sdkm' => 'application/vnd.solent.sdkm+xml',
            'sdp' => 'application/sdp',
            'sdw' => 'application/vnd.stardivision.writer',
            'see' => 'application/vnd.seemail',
            'seed' => 'application/vnd.fdsn.seed',
            'sema' => 'application/vnd.sema',
            'semd' => 'application/vnd.semd',
            'semf' => 'application/vnd.semf',
            'ser' => 'application/java-serialized-object',
            'setpay' => 'application/set-payment-initiation',
            'setreg' => 'application/set-registration-initiation',
            'sfd-hdstx' => 'application/vnd.hydrostatix.sof-data',
            'sfs' => 'application/vnd.spotfire.sfs',
            'sfv' => 'text/x-sfv',
            'sgi' => 'image/sgi',
            'sgl' => 'application/vnd.stardivision.writer-global',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'shf' => 'application/shf+xml',
            'sid' => 'image/x-mrsid-image',
            'sig' => 'application/pgp-signature',
            'sil' => 'audio/silk',
            'silo' => 'model/mesh',
            'sis' => 'application/vnd.symbian.install',
            'sisx' => 'application/vnd.symbian.install',
            'sit' => 'application/x-stuffit',
            'sitx' => 'application/x-stuffitx',
            'skd' => 'application/vnd.koan',
            'skm' => 'application/vnd.koan',
            'skp' => 'application/vnd.koan',
            'skt' => 'application/vnd.koan',
            'sldm' => 'application/vnd.ms-powerpoint.slide.macroenabled.12',
            'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'slt' => 'application/vnd.epson.salt',
            'sm' => 'application/vnd.stepmania.stepchart',
            'smf' => 'application/vnd.stardivision.math',
            'smi' => 'application/smil+xml',
            'smil' => 'application/smil+xml',
            'smv' => 'video/x-smv',
            'smzip' => 'application/vnd.stepmania.package',
            'snd' => 'audio/basic',
            'snf' => 'application/x-font-snf',
            'so' => 'application/octet-stream',
            'spc' => 'application/x-pkcs7-certificates',
            'spf' => 'application/vnd.yamaha.smaf-phrase',
            'spl' => 'application/x-futuresplash',
            'spot' => 'text/vnd.in3d.spot',
            'spp' => 'application/scvp-vp-response',
            'spq' => 'application/scvp-vp-request',
            'spx' => 'audio/ogg',
            'sql' => 'application/x-sql',
            'src' => 'application/x-wais-source',
            'srt' => 'application/x-subrip',
            'sru' => 'application/sru+xml',
            'srx' => 'application/sparql-results+xml',
            'ssdl' => 'application/ssdl+xml',
            'sse' => 'application/vnd.kodak-descriptor',
            'ssf' => 'application/vnd.epson.ssf',
            'ssml' => 'application/ssml+xml',
            'st' => 'application/vnd.sailingtracker.track',
            'stc' => 'application/vnd.sun.xml.calc.template',
            'std' => 'application/vnd.sun.xml.draw.template',
            'stf' => 'application/vnd.wt.stf',
            'sti' => 'application/vnd.sun.xml.impress.template',
            'stk' => 'application/hyperstudio',
            'stl' => 'application/vnd.ms-pki.stl',
            'str' => 'application/vnd.pg.format',
            'stw' => 'application/vnd.sun.xml.writer.template',
            'sub' => 'text/vnd.dvb.subtitle',
            'sus' => 'application/vnd.sus-calendar',
            'susp' => 'application/vnd.sus-calendar',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'svc' => 'application/vnd.dvb.service',
            'svd' => 'application/vnd.svd',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'swa' => 'application/x-director',
            'swf' => 'application/x-shockwave-flash',
            'swi' => 'application/vnd.aristanetworks.swi',
            'sxc' => 'application/vnd.sun.xml.calc',
            'sxd' => 'application/vnd.sun.xml.draw',
            'sxg' => 'application/vnd.sun.xml.writer.global',
            'sxi' => 'application/vnd.sun.xml.impress',
            'sxm' => 'application/vnd.sun.xml.math',
            'sxw' => 'application/vnd.sun.xml.writer',
            't' => 'text/troff',
            't3' => 'application/x-t3vm-image',
            'taglet' => 'application/vnd.mynfc',
            'tao' => 'application/vnd.tao.intent-module-archive',
            'tar' => 'application/x-tar',
            'tcap' => 'application/vnd.3gpp2.tcap',
            'tcl' => 'application/x-tcl',
            'teacher' => 'application/vnd.smart.teacher',
            'tei' => 'application/tei+xml',
            'teicorpus' => 'application/tei+xml',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'text' => 'text/plain',
            'tfi' => 'application/thraud+xml',
            'tfm' => 'application/x-tex-tfm',
            'tga' => 'image/x-tga',
            'thmx' => 'application/vnd.ms-officetheme',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tmo' => 'application/vnd.tmobile-livetv',
            'torrent' => 'application/x-bittorrent',
            'tpl' => 'application/vnd.groove-tool-template',
            'tpt' => 'application/vnd.trid.tpt',
            'tr' => 'text/troff',
            'tra' => 'application/vnd.trueapp',
            'trm' => 'application/x-msterminal',
            'tsd' => 'application/timestamped-data',
            'tsv' => 'text/tab-separated-values',
            'ttc' => 'application/x-font-ttf',
            'ttf' => 'application/x-font-ttf',
            'ttl' => 'text/turtle',
            'twd' => 'application/vnd.simtech-mindmapper',
            'twds' => 'application/vnd.simtech-mindmapper',
            'txd' => 'application/vnd.genomatix.tuxedo',
            'txf' => 'application/vnd.mobius.txf',
            'txt' => 'text/plain',
            'u32' => 'application/x-authorware-bin',
            'udeb' => 'application/x-debian-package',
            'ufd' => 'application/vnd.ufdl',
            'ufdl' => 'application/vnd.ufdl',
            'ulx' => 'application/x-glulx',
            'umj' => 'application/vnd.umajin',
            'unityweb' => 'application/vnd.unity',
            'uoml' => 'application/vnd.uoml+xml',
            'uri' => 'text/uri-list',
            'uris' => 'text/uri-list',
            'urls' => 'text/uri-list',
            'ustar' => 'application/x-ustar',
            'utz' => 'application/vnd.uiq.theme',
            'uu' => 'text/x-uuencode',
            'uva' => 'audio/vnd.dece.audio',
            'uvd' => 'application/vnd.dece.data',
            'uvf' => 'application/vnd.dece.data',
            'uvg' => 'image/vnd.dece.graphic',
            'uvh' => 'video/vnd.dece.hd',
            'uvi' => 'image/vnd.dece.graphic',
            'uvm' => 'video/vnd.dece.mobile',
            'uvp' => 'video/vnd.dece.pd',
            'uvs' => 'video/vnd.dece.sd',
            'uvt' => 'application/vnd.dece.ttml+xml',
            'uvu' => 'video/vnd.uvvu.mp4',
            'uvv' => 'video/vnd.dece.video',
            'uvva' => 'audio/vnd.dece.audio',
            'uvvd' => 'application/vnd.dece.data',
            'uvvf' => 'application/vnd.dece.data',
            'uvvg' => 'image/vnd.dece.graphic',
            'uvvh' => 'video/vnd.dece.hd',
            'uvvi' => 'image/vnd.dece.graphic',
            'uvvm' => 'video/vnd.dece.mobile',
            'uvvp' => 'video/vnd.dece.pd',
            'uvvs' => 'video/vnd.dece.sd',
            'uvvt' => 'application/vnd.dece.ttml+xml',
            'uvvu' => 'video/vnd.uvvu.mp4',
            'uvvv' => 'video/vnd.dece.video',
            'uvvx' => 'application/vnd.dece.unspecified',
            'uvvz' => 'application/vnd.dece.zip',
            'uvx' => 'application/vnd.dece.unspecified',
            'uvz' => 'application/vnd.dece.zip',
            'vcard' => 'text/vcard',
            'vcd' => 'application/x-cdlink',
            'vcf' => 'text/x-vcard',
            'vcg' => 'application/vnd.groove-vcard',
            'vcs' => 'text/x-vcalendar',
            'vcx' => 'application/vnd.vcx',
            'vis' => 'application/vnd.visionary',
            'viv' => 'video/vnd.vivo',
            'vob' => 'video/x-ms-vob',
            'vor' => 'application/vnd.stardivision.writer',
            'vox' => 'application/x-authorware-bin',
            'vrml' => 'model/vrml',
            'vsd' => 'application/vnd.visio',
            'vsf' => 'application/vnd.vsf',
            'vss' => 'application/vnd.visio',
            'vst' => 'application/vnd.visio',
            'vsw' => 'application/vnd.visio',
            'vtu' => 'model/vnd.vtu',
            'vxml' => 'application/voicexml+xml',
            'w3d' => 'application/x-director',
            'wad' => 'application/x-doom',
            'wav' => 'audio/x-wav',
            'wax' => 'audio/x-ms-wax',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbs' => 'application/vnd.criticaltools.wbs+xml',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wcm' => 'application/vnd.ms-works',
            'wdb' => 'application/vnd.ms-works',
            'wdp' => 'image/vnd.ms-photo',
            'weba' => 'audio/webm',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'wg' => 'application/vnd.pmi.widget',
            'wgt' => 'application/widget',
            'wks' => 'application/vnd.ms-works',
            'wm' => 'video/x-ms-wm',
            'wma' => 'audio/x-ms-wma',
            'wmd' => 'application/x-ms-wmd',
            'wmf' => 'application/x-msmetafile',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wmv' => 'video/x-ms-wmv',
            'wmx' => 'video/x-ms-wmx',
            'wmz' => 'application/x-msmetafile',
            'woff' => 'application/font-woff',
            'wpd' => 'application/vnd.wordperfect',
            'wpl' => 'application/vnd.ms-wpl',
            'wps' => 'application/vnd.ms-works',
            'wqd' => 'application/vnd.wqd',
            'wri' => 'application/x-mswrite',
            'wrl' => 'model/vrml',
            'wsdl' => 'application/wsdl+xml',
            'wspolicy' => 'application/wspolicy+xml',
            'wtb' => 'application/vnd.webturbo',
            'wvx' => 'video/x-ms-wvx',
            'x32' => 'application/x-authorware-bin',
            'x3d' => 'model/x3d+xml',
            'x3db' => 'model/x3d+binary',
            'x3dbz' => 'model/x3d+binary',
            'x3dv' => 'model/x3d+vrml',
            'x3dvz' => 'model/x3d+vrml',
            'x3dz' => 'model/x3d+xml',
            'xaml' => 'application/xaml+xml',
            'xap' => 'application/x-silverlight-app',
            'xar' => 'application/vnd.xara',
            'xbap' => 'application/x-ms-xbap',
            'xbd' => 'application/vnd.fujixerox.docuworks.binder',
            'xbm' => 'image/x-xbitmap',
            'xdf' => 'application/xcap-diff+xml',
            'xdm' => 'application/vnd.syncml.dm+xml',
            'xdp' => 'application/vnd.adobe.xdp+xml',
            'xdssc' => 'application/dssc+xml',
            'xdw' => 'application/vnd.fujixerox.docuworks',
            'xenc' => 'application/xenc+xml',
            'xer' => 'application/patch-ops-error+xml',
            'xfdf' => 'application/vnd.adobe.xfdf',
            'xfdl' => 'application/vnd.xfdl',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xhvml' => 'application/xv+xml',
            'xif' => 'image/vnd.xiff',
            'xla' => 'application/vnd.ms-excel',
            'xlam' => 'application/vnd.ms-excel.addin.macroenabled.12',
            'xlc' => 'application/vnd.ms-excel',
            'xlf' => 'application/x-xliff+xml',
            'xlm' => 'application/vnd.ms-excel',
            'xls' => 'application/vnd.ms-excel',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroenabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlt' => 'application/vnd.ms-excel',
            'xltm' => 'application/vnd.ms-excel.template.macroenabled.12',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xlw' => 'application/vnd.ms-excel',
            'xm' => 'audio/xm',
            'xml' => 'application/xml',
            'xo' => 'application/vnd.olpc-sugar',
            'xop' => 'application/xop+xml',
            'xpi' => 'application/x-xpinstall',
            'xpl' => 'application/xproc+xml',
            'xpm' => 'image/x-xpixmap',
            'xpr' => 'application/vnd.is-xpr',
            'xps' => 'application/vnd.ms-xpsdocument',
            'xpw' => 'application/vnd.intercon.formnet',
            'xpx' => 'application/vnd.intercon.formnet',
            'xsl' => 'application/xml',
            'xslt' => 'application/xslt+xml',
            'xsm' => 'application/vnd.syncml+xml',
            'xspf' => 'application/xspf+xml',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'xvm' => 'application/xv+xml',
            'xvml' => 'application/xv+xml',
            'xwd' => 'image/x-xwindowdump',
            'xyz' => 'chemical/x-xyz',
            'xz' => 'application/x-xz',
            'yang' => 'application/yang',
            'yin' => 'application/yin+xml',
            'z1' => 'application/x-zmachine',
            'z2' => 'application/x-zmachine',
            'z3' => 'application/x-zmachine',
            'z4' => 'application/x-zmachine',
            'z5' => 'application/x-zmachine',
            'z6' => 'application/x-zmachine',
            'z7' => 'application/x-zmachine',
            'z8' => 'application/x-zmachine',
            'zaz' => 'application/vnd.zzazz.deck+xml',
            'zip' => 'application/zip',
            'zir' => 'application/vnd.zul',
            'zirz' => 'application/vnd.zul',
            'zmm' => 'application/vnd.handheld-entertainment+xml',
            '123' => 'application/vnd.lotus-1-2-3',
        );

        $tmp = explode('.', $filename);
        $ext = strtolower(end($tmp));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
    }

    public static function delete_folder($folder)
    {
        $glob = glob($folder);
        foreach ($glob as $g) {
            if (!is_dir($g)) {
                unlink($g);
            } else {
                Helpers::delete_folder("$g/*");
                rmdir($g);
            }
        }
    }

    public static function arraySearch($array, $fieldIn, $value)
    {
        $arrayOut = [];
        $list = array_filter($array, function ($row) use ($fieldIn, $value) {
            return $row->$fieldIn == $value;
        });
        if (count($list) > 0) {
            foreach ($list as $row) {
                array_push($arrayOut, $row);
            }
            return $arrayOut;
        } else {
            return $arrayOut;
        }
    }

    public static function arraySearch2($array, $fieldIn, $value)
    {
        $arrayOut = [];
        $list = array_filter($array, function ($row) use ($fieldIn, $value) {
            return $row[$fieldIn] == $value;
        });
        if (count($list) > 0) {
            foreach ($list as $row) {
                array_push($arrayOut, $row);
            }
            return $arrayOut;
        } else {
            return $arrayOut;
        }
    }

    public static function convertBool($val, $blnNumberToBoolean)
    {
        if ($blnNumberToBoolean) {
            if (is_numeric($val))
                return $val == 1 ? true : false;
            else {
                return $val;
            }
        } else {
            if (is_bool($val))
                return $val == true ? 1 : 0;
            else
                return $val;
        }
    }

    //encrypt data when received
    public static function decryptData($value)
    {
//        if (Helpers::checkMobile() == 'M') { //Không mã hóa ở trường hợp là chạy trên mobile
//            return $value;
//        }
        // $secretKey = Config::get("app.secretKey", "");
        return AES::cryptoJsAesDecrypt("Lem@nWebCorporation", base64_decode($value));
    }


    //encrypt data when sending
    public static function encryptData($value)
    {
//        if (Helpers::checkMobile() == 'M') { //Không mã hóa ở trường hợp là chạy trên mobile
//            return $value;
//        }
        //$secretKey = Config::get("service.secretKey", "");
        return base64_encode(AES::cryptoJsAesEncrypt("Lem@nWebCorporation", ($value)));
    }

    //get days in month
    //Lấy số ngày trong tranMonth
    public static function getDaysInTranMonth()
    {
        return date("t", mktime(0, 0, 0, Session::get("W91P0000")['HRTranMonth'], 1, Session::get("W91P0000")['HRTranYear']));
    }

    //get days in this month
    //Lấy số ngày của tháng hiện tại
    public static function getDaysInCurrentMonth()
    {
        return substr(date("t/m/Y"), 0, 2);
    }
    //get days in any month
    //Lấy số ngày của tháng bất kì
    public static function getDaysInAnyMonth($month, $year)
    {
        return date("t", mktime(0, 0, 0, $month, 1, $year));
    }

    //Lấy ngày đầu của kì hiện tại
    public static function beginDateOfPeriod()
    {
        return date("01/m/Y", mktime(0, 0, 0, self::getTranMonth(), 1, self::getTranYear()));
    }

    //Ngày cuối của kì hiện tại
    public static function endDateOfPeriod()
    {
        return date("t/m/Y", mktime(0, 0, 0, self::getTranMonth(), 1, self::getTranYear()));
    }

    //Lấy ngày đầu của kì bat ki
    public static function beginDateOfAnyPeriod($month, $year)
    {
        return date("01/m/Y", mktime(0, 0, 0, $month, 1, $year));
    }

    //Lấy ngày cuối của kì bat ki
    public static function endDateOfAnyPeriod($month, $year)
    {
        return date("t/m/Y", mktime(0, 0, 0, $month, 1, $year));
    }

    //Lấy ngày đầu của thang hiện tại
    public static function beginDateOfCurrentPeriod()
    {
        return date("01/m/Y", mktime(0, 0, 0, date('m'), 1, date('y')));
    }

    //Lấy ngày cuối của kì bat ki
    public static function endDateOfCurrentPeriod()
    {
        return date("t/m/Y", mktime(0, 0, 0, date('m'), 1, date('y')));
    }

    //Lấy mã nhân viên
    public static function getW91P0000($attr)
    {
        return session('w91P0000')->$attr;
    }

    static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    static function convertZeroToEmpty($rsData, $rsCols)
    {
        $result = [];
        foreach ($rsData as $row) {
            foreach ($rsCols as $col) {
                $value = "";
                if ($col["DataType"] == "N") {
                    if (intval($row[$col["FieldName"]]) == 0) {
                        $value = "";
                    }
                }
                $row[$col["FieldName"]] = $value;
            }
            //////\Debugbar::info($row);
            array_push($result, $row);
        }
        return $result;
    }


    static function unichrConvertFont($n, $value)
    {
        $ascii_array = array(
            128 => "€",
            129 => "",
            130 => "‚",
            131 => "ƒ",
            132 => "„",
            133 => "…",
            134 => "†",
            135 => "‡",
            136 => "ˆ",
            137 => "‰",
            138 => "Š",
            139 => "‹",
            140 => "Œ",
            141 => "",
            142 => "Ž",
            143 => "",
            144 => "",
            145 => "‘",
            146 => "’",
            147 => "“",
            148 => "”",
            149 => "•",
            150 => "–",
            151 => "—",
            152 => "˜",
            153 => "™",
            154 => "š",
            155 => "›",
            156 => "œ",
            157 => "",
            158 => "ž",
            159 => "Ÿ",
            160 => " ",
            161 => "¡",
            162 => "¢",
            163 => "£",
            164 => "¤",
            165 => "¥",
            166 => "¦",
            167 => "§",
            168 => "¨",
            169 => "©",
            170 => "ª",
            171 => "«",
            172 => "¬",
            173 => "­",
            174 => "®",
            175 => "¯",
            176 => "°",
            177 => "±",
            178 => "²",
            179 => "³",
            180 => "´",
            181 => "µ",
            182 => "¶",
            183 => "·",
            184 => "¸",
            185 => "¹",
            186 => "º",
            187 => "»",
            188 => "¼",
            189 => "½",
            190 => "¾",
            191 => "¿",
            192 => "À",
            193 => "Á",
            194 => "Â",
            195 => "Ã",
            196 => "Ä",
            197 => "Å",
            198 => "Æ",
            199 => "Ç",
            200 => "È",
            201 => "É",
            202 => "Ê",
            203 => "Ë",
            204 => "Ì",
            205 => "Í",
            206 => "Î",
            207 => "Ï",
            208 => "Ð",
            209 => "Ñ",
            210 => "Ò",
            211 => "Ó",
            212 => "Ô",
            213 => "Õ",
            214 => "Ö",
            215 => "×",
            216 => "Ø",
            217 => "Ù",
            218 => "Ú",
            219 => "Û",
            220 => "Ü",
            221 => "Ý",
            222 => "Þ",
            223 => "ß",
            224 => "à",
            225 => "á",
            226 => "â",
            227 => "ã",
            228 => "ä",
            229 => "å",
            230 => "æ",
            231 => "ç",
            232 => "è",
            233 => "é",
            234 => "ê",
            235 => "ë",
            236 => "ì",
            237 => "í",
            238 => "î",
            239 => "ï",
            240 => "ð",
            241 => "ñ",
            242 => "ò",
            243 => "ó",
            244 => "ô",
            245 => "õ",
            246 => "ö",
            247 => "÷",
            248 => "ø",
            249 => "ù",
            250 => "ú",
            251 => "û",
            252 => "ü",
            253 => "ý",
            254 => "þ",
            255 => "ÿ"
        );
        if ($value != '')
            return array_search($value, $ascii_array);
        else
            return $ascii_array[$n];
    }

    static function detectVietnamese($str)
    {
        $unicode = array("á", "à", "ả", "ã", "ạ", "ă", "ắ", "ặ", "ằ", "ẳ", "ẵ", "â", "ấ", "ầ", "ẩ", "ẫ", "ậ", "đ", "é", "è", "ẻ", "ẽ", "ẹ", "ê", "ế", "ề", "ể", "ễ", "ệ", "í", "ì", "ỉ", "ĩ", "ị", "ó", "ò", "ỏ", "õ", "ọ", "ô", "ố", "ồ", "ổ", "ỗ", "ộ", "ơ", "ớ", "ờ", "ở", "ỡ", "ợ", "ú", "ù", "ủ", "ũ", "ụ", "ư", "ứ", "ừ", "ử", "ữ", "ự", "ý", "ỳ", "ỷ", "ỹ", "ỵ", "Á", "À", "Ả", "Ã", "Ạ", "Ă", "Ắ", "Ặ", "Ằ", "Ẳ", "Ẵ", "Â", "Ấ", "Ầ", "Ẩ", "Ẫ", "Ậ", "Đ", "É", "È", "Ẻ", "Ẽ", "Ẹ", "Ê", "Ế", "Ề", "Ể", "Ễ", "Ệ", "Í", "Ì", "Ỉ", "Ĩ", "Ị", "Ó", "Ò", "Ỏ", "Õ", "Ọ", "Ô", "Ố", "Ồ", "Ổ", "Ỗ", "Ộ", "Ơ", "Ớ", "Ờ", "Ở", "Ỡ", "Ợ", "Ú", "Ù", "Ủ", "Ũ", "Ụ", "Ư", "Ứ", "Ừ", "Ử", "Ữ", "Ự", "Ý", "Ỳ", "Ỷ", "Ỹ", "Ỵ");
        //Chưa code
        if (strlen($str) != strlen(utf8_decode($str))) {
            return true;
        }
        return false;
    }

    //loc dau
    static function unicodeToString($str)
    {

        $unicode = array(

            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',

            'd' => 'đ',

            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',

            'i' => 'í|ì|ỉ|ĩ|ị',

            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',

            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',

            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',

            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',

            'D' => 'Đ',

            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',

            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',

            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',

            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',

            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',

        );
        foreach ($unicode as $nonUnicode => $uni) {

            $str = preg_replace("/($uni)/i", $nonUnicode, $str);

        }
        $str = str_replace(' ', '_', $str);

        return $str;

    }

    static function prefixZero($num, $length)
    {
        return str_pad($num, $length, '0', STR_PAD_LEFT);
    }

    static function getWeekNumber($dateTime)
    {
        return $dateTime->format("W");
    }

    static function sqlstring($str)
    {
        $str = str_replace("'", "''", $str);
        return $str;
    }

    //lay extension dinh kem
    static function getAttExtList()
    {
        $arrFileExt = \Config::get('attachment.fileExtension');
        $arrFileType = array();
        foreach ($arrFileExt as $key => $value) {
            if ($value['val'] == true) {
                if ($key != "zip1") {//lo?i tr? file zip1
                    array_push($arrFileType, '.' . $key);
                }
            }
        }
        return $arrFileType;
    }

    public static function getLocale()
    {
        if (session('locate')) {
            Helpers::setLang(session('locate'));
            return session('locate');
        }
        Helpers::setLang(App::getLocale());
        return App::getLocale();
    }

    public static function getLang()
    {
        if (session('lang')) {
            return session('lang');
        }
        Helpers::setLang(App::getLocale());
        return App::getLocale();
    }

    public static function setLang($lang)
    {
        App::setLocale($lang);
        switch ($lang) {
            case 'en' :
                session(['lang' => '01']);
                session(['i18n' => 'en_US']);
                session(['locate' => 'en']);
                break;
            case 'ja' :
                session(['lang' => '81']);
                session(['i18n' => 'jp_JP']);
                session(['locate' => 'ja']);
                break;
            case 'zh' :
                session(['lang' => '86']);
                session(['i18n' => 'ch_CH']);
                session(['locate' => 'zh']);
                break;
            default :
                session(['lang' => '84']);
                session(['i18n' => 'vi_VI']);
                session(['locate' => 'vi']);

                break;
        }
        return 1;
    }

    public static function getHREmployee()
    {
        return Auth::user()->HREmployeeID;
    }

    public static function getDivision()
    {
        return session('w91P0000')->HRDivisionID;
    }

    public static function getDepartment()
    {
        return session('w91P0000')->DepartmentID;
    }

    public static function getTranMonth()
    {
        return session('w91P0000')->HRTranMonth;
    }

    public static function getTranYear()
    {
        return session('w91P0000')->HRTranYear;
    }

    public static function getCreatorID()
    {
        return session('w91P0000')->CreatorHR;
    }

    public static function getCreatorName()
    {
        return session('w91P0000')->CreatorNameHR;
    }

    public static function getUserID()
    {
        return Auth::user()->UserID;
    }

    public static function getPForm()
    {
        return \Session::get('pFrom', '');
    }

    public static function getHostName()
    {
        return session()->getId();
    }

    public static function getHostID()
    {
        return session()->getId();
    }

    public static function setSessionUser($user)
    {
        self::setSession('current_user', $user);
    }

    public static function setSession($key, $value)
    {
        session([$key => $value]);
    }

    public static function removeSessionByKey($key)
    {
        session()->remove($key);
    }

    public static function getSession($key)
    {
        return session($key);
    }

    public static function isAUserInSession()
    {
        if (self::getSession('current_user')) {
            return true;
        }
        return false;
    }

    /**
     * Parse query from pattern to the real query
     * @param string $queryPattern
     * @param array $params
     */
    public static function parseQuery($queryPattern = '', $params = [])
    {
        $matches = [];
        preg_match_all('/@[a-zA-Z0-9]+/', $queryPattern, $matches);
        $matches = array_pop($matches);

        for ($i = 0; $i < count($matches); $i++) {
            if ($params[$i] != '') {
                if (strpos($params[$i], "N'") !== false) {
                    $value = $params[$i];
                } else {
                    $value = "'" . $params[$i] . "'";
                }
            } else {
                $value = "''";
            }
            $queryPattern = preg_replace("/$matches[$i]\b/", $value, $queryPattern);
        }
        return $queryPattern;

    }


    /**
     * Return the string cast to datetime for store procedure
     * @param $dateString
     * @return string
     */
    public static function formatDateStoreProcedure($dateString)
    {

        $date = explode('/', $dateString);
        if (count($date) > 1) {
            return "N'" . $date[2] . $date[1] . $date[0] . "'";
        } else {
            return "N'" . $dateString . "'";
        }
    }

    public static function changeEnv($data = array())
    {
        if (count($data) > 0) {

            // Read .env-file
            $env = file_get_contents(base_path() . '/.env');
            // Split string on every " " and write into array
            $env = preg_split('/\s+/', $env);;
            // Loop through given data
            foreach ((array)$data as $key => $value) {
                // Loop through .env-data
                $isExist = Helpers::checkExistKeyInEnv($key);
                foreach ($env as $env_key => $env_value) {

                    // Turn the value into an array and stop after the first split
                    // So it's not possible to split e.g. the App-Key by accident
                    $entry = explode("=", $env_value, 2);

                    // Check, if new key fits the actual .env-key
                    if ($entry[0] == $key) { //Key exist
                        // If yes, overwrite it with the new one
                        $env[$env_key] = $key . "=" . $value;

                    } else {
                        // If not, keep the old one
                        $env[$env_key] = $env_value;
                    }
                }
                if (!$isExist) {
                    $env[$key] = $key . "=" . $value;
                }
            }

            // Turn the array back to an String
            $env = implode("\n", $env);

            // And overwrite the .env with the new data
            file_put_contents(base_path() . '/.env', $env);
            return true;
        } else {
            return false;
        }
    }

    public static function checkExistKeyInEnv($key)
    {
        // Read .env-file
        $env = file_get_contents(base_path() . '/.env');
        $env = preg_split('/\s+/', $env);
        $isExist = false;
        foreach ($env as $env_key => $env_value) {
            $entry = explode("=", $env_value, 2);
            if ($entry[0] == $key) { //Key exist
                $isExist = true;
            }
        }
        return $isExist;
    }

    public static function isManager()
    {
        $postionID = \session('positionID', '');
        $userID = Auth::user()->UserID;
        $sql = "--Get IsManager" . PHP_EOL;
        $sql .= "SELECT Case when '$userID' = 'LEMONADMIN' THEN 1 ELSE IsManager END AS IsManager" . PHP_EOL;
        $sql .= "FROM 	D09T0211" . PHP_EOL;
        $sql .= "WHERE 	DutyID = '$postionID'" . PHP_EOL;
        try {
            $result = DB::connection()->selectOne($sql);
            if ($result != null) {
                return $result->IsManager;
            }
            return 0;
        } catch (\Exception $ex) {
            return 0;
        }


    }


    public static function getPermission($formid)
    {
        $lang = Helpers::getLang();
        $user = Auth::user()->UserID;
        $database = config('database.connections.sqlsrv.database');
        if (Session::has($formid)) {
            return Session::get($formid);
        } else {
            //$database = Helpers::decrypt_userpass(Session::get("CONDEFAULT")["database"]);
            $sql = "EXEC W00P1000 '$database', '$user', '%', '$lang',''";
            $mrow = DB::connection('sqlsrvLMS')->select($sql);
            //$mrow = $this->connectionLMS->selectOne($sql);
            //////\Debugbar::info($mrow);
            if ($mrow == null)
                return "";
            else
                return $mrow["Permission"];
        }
    }

    public static function renderBreadcrum($root, $parentIndex, $parentName, $index, $name, $childIndex, $childName)
    {//ham do dong render
        // ////\Debugbar::info($root, $parentIndex, $parentName, $index, $name, $childIndex, $childName);
        try {
            Breadcrumbs::for($parentIndex, function ($trail, $lv1) use ($root, $parentName, $parentIndex) {
                $trail->parent($root);
                $trail->push($parentName, route($parentIndex));
            });
            if ($index == "") {

                return Breadcrumbs::render($parentIndex, $parentName);
            } else {
                Breadcrumbs::for($index, function ($trail, $lv1 = '', $lv2 = '') use ($name, $parentIndex, $index) {
                    $trail->parent($parentIndex, $lv1);
                    $trail->push($name, route($index));
                });
                if ($childIndex == "") {
                    return Breadcrumbs::render($index, $parentName, $name);
                } else {
                    Breadcrumbs::for($childIndex, function ($trail, $lv1 = '', $lv2 = '') use ($name, $index, $childIndex, $childName) {
                        $trail->parent($index, $lv2);
                        $trail->push($childName, route($childIndex));
                    });
                    return Breadcrumbs::render($childIndex, $parentName, $name, $childName);
                }

            }
        } catch (\Exception $e) {
            return "LỖI";
        }
    }

    public static function getCompanyID()
    {
//        $companyID = config::get('database.connections.sqlsrv.database');
        return config('database.connections.sqlsrv.database');
    }

    public static function createNEWID()
    {
        return DB::selectOne('select CAST(newid() AS VARCHAR(36)) as NewsID')->NewsID;

    }

    public static function getFormID()
    {
        $uri = Route::getFacadeRoot()->current()->uri();
        return explode('/', $uri)[0];
    }

    public static function getModuleID()
    {
        $uri = Route::getFacadeRoot()->current()->uri();
        $pForm = explode('/', $uri)[0];
        if (session('pForm', '') != '') {
            $pForm = session('pForm', '');
        }
        return str_replace('W', 'D', substr($pForm, 0, 3));
    }

    public static function rowMenu($pFrom)
    {
        $database = config('database.connections.sqlsrv.database');
        $lang = Helpers::getLang();
        $userID = Auth::user()->UserID;
        $sql = "EXEC W00P1000 '$database', '$userID', '%', '$lang','$pFrom'" . PHP_EOL;
        //////\Debugbar::info($sql);
        if (DB::connection('sqlsrvLMS')->selectOne($sql) == null) {
            $sql = "EXEC W00P1001 '$database', '$userID', '$lang' , 'ESS', '$pFrom'" . PHP_EOL;
            if (DB::connection('sqlsrvLMS')->selectOne($sql) == null) {
                $sql = "EXEC W00P1001 '$database', '$userID', '$lang' , 'MSS', '$pFrom'" . PHP_EOL;
                //////\Debugbar::info($sql);
                if (DB::connection('sqlsrvLMS')->selectOne($sql) == null) {
                    return null;
                } else {
                    return DB::connection('sqlsrvLMS')->selectOne($sql);
                }
            } else {
                return DB::connection('sqlsrvLMS')->selectOne($sql);
            }
        } else {
            return DB::connection('sqlsrvLMS')->selectOne($sql);
        }
    }

    public static function getTitle($pFrom)
    {
        $database = config('database.connections.sqlsrv.database');
        $lang = Helpers::getLang();
        $sql = "EXEC W00P1005 '" . Auth::user()->UserID . "','$database','$lang','$pFrom'";
        $rs = DB::connection('sqlsrvLMS')->selectOne($sql);
        $FormName = "";
        if (isset($rs->FormName)) {
            $FormName = $rs->FormName;
            return $FormName;
        } else {
            // ////\Debugbar::info("ds vao");
            return "";
        }
    }

    public static function getRootMenu($pFrom)
    {
        ////////\Debugbar::info($pFrom);
        $r = self::rowMenu($pFrom);
        //////\Debugbar::info($r);
        if ($r != null) {
            if (isset($r->ScreenType)) {
                if ($r->ScreenType == 'S') {//hệ thống
                    return "hr.system";
                }
                if ($r->ScreenType == 'L') {//danh mục
                    return "hr.listbook";
                }
                if ($r->ScreenType == 'T' && $r->ModuleID == 'D25') {//tuyển dụng
                    return "hr.recruit";
                }
                if (($r->ScreenType == 'T' && $r->ModuleID == 'D09') || ($r->ScreenType == 'T' && $r->ModuleID == 'D21')) {//nhân sự và bảo hiểm
                    return "hr.hrInsure";
                }
                if (($r->ScreenType == 'T' && $r->ModuleID == 'D29') || ($r->ScreenType == 'T' && $r->ModuleID == 'D15') || ($r->ScreenType == 'T' && $r->ModuleID == 'D13') || ($r->ScreenType == 'T' && $r->ModuleID == 'D45')) {//công phép lương
                    return "hr.salary";
                }
                if (($r->ScreenType == 'T' && $r->ModuleID == 'D38') || ($r->ScreenType == 'T' && $r->ModuleID == 'D39')) {//đào tạo và đánh giá
                    return "hr.training";
                }
                if (($r->ScreenType == 'T' && $r->ModuleID == 'D52') || ($r->ScreenType == 'T' && $r->ModuleID == 'D53')) {//trang thiết bị lao động
                    return "hr.toolLabour";
                }
                if (($r->ScreenType == 'R') || ($r->ScreenType == 'BI') || ($r->ScreenType == 'I')) {//báo cáo
                    return "hr.reportBI";
                }
            }
            if ($r->ModuleGroupID == "ESS") {//ESS
                return "hr.ess";
            }
            if ($r->ModuleGroupID == "MSS") {//MSS
                return "hr.mss";
            }
        } else {
            //////\Debugbar::info("da vao");
            return false;
        }

    }

    public static function getBreadcrum()
    {
        $grandPform = Session::get('pFrom');
        $pFrom = self::getFormID();
        $pFrom = explode('?', $pFrom)[0];
        $prePFform = "";
        if (count(explode('/', url()->previous())) > 3) {
            $prePFform = explode('/', url()->previous())[3];
            $prePFform = explode('?', $prePFform)[0];
        }
        $title = self::getTitle($pFrom);
        $routeName = Route::currentRouteName();
        //////\Debugbar::info($prePFform, $pFrom);
        ////////\Debugbar::info(self::rowMenu($pFrom));
        if (self::rowMenu($pFrom) != null) {//la menu cha
            $root = self::getRootMenu($pFrom);
            Session::forget('pFrom');
            return self::renderBreadcrum($root, $routeName, $title, "", "", "", "");
        } else {//menu cap con
            if ($pFrom == $prePFform) {//trường hợp người dùng enter cùng 1 trang
                $prePFform = Session::get('prePFform');
            } else {
                if ($grandPform == $pFrom || $grandPform == null) {
                    Session::put('pFrom', $prePFform);
                }
                Session::put('prePFform', $prePFform);
            }
            if ($prePFform == "W09F4011") {//truong hop menu dinh kem cap 4
                $prePFform = $grandPform;
                $preTitle = self::getTitle($prePFform);
                $preName = app('router')->getRoutes()->match(app('request')->create('/' . $prePFform))->getName();
                $root = self::getRootMenu($prePFform);
                return self::renderBreadcrum($root, $preName, $preTitle, $routeName, $title, "", "");
            }
            $preTitle = self::getTitle($prePFform);
            $preName = app('router')->getRoutes()->match(app('request')->create('/' . $prePFform))->getName();
            if ($grandPform == null || $grandPform == $pFrom || $grandPform == $prePFform) {//menu cap 3
                $root = self::getRootMenu($prePFform);
                //////\Debugbar::info($root, $preName, $preTitle, $routeName , $title);
                return self::renderBreadcrum($root, $preName, $preTitle, $routeName, $title, "", "");
            } else {//menu cap 4
                if ($prePFform == "W25F1051") {//man hinh cap 4 dinh kem
                    $preName = app('router')->getRoutes()->match(app('request')->create('/' . $prePFform . '/history'))->getName();
                }
                $grandPTitle = self::getTitle($grandPform);
                $grandName = app('router')->getRoutes()->match(app('request')->create('/' . $grandPform))->getName();
                $root = self::getRootMenu($grandPform);
                return self::renderBreadcrum($root, $grandName, $grandPTitle, $preName, $preTitle, $routeName, $title);
            }

        }
    }

    public static function CreateIGENewS($g, $tabname, $key01, $key02, $oldIGE, $count, $firsttran)
    {
        if ($oldIGE == "") {
            $ret = Helpers::IGEKeyPrimary($g, $tabname, $key01, $key02);
        } else {
            $keystring = $key01 . $key02;
            $ilen = strlen($keystring);
            $iNo = intval(substr($oldIGE, $ilen)) + 1;
            if ($iNo > $firsttran + $count - 1) {
                throw new Exception("Lỗi sinh mã tự động cho khóa chính của $tabname");
            }
            // $ret = $keystring & str_pad($iNo, 15, "0");
            $ret = $keystring . str_pad($iNo, 15 - strlen($keystring), "0", 0);
            if (strlen($ret) > 15) {
                throw new Exception("Lỗi sinh mã tự động cho khóa chính của $tabname. Chiều dài vượt quá giới hạn.");
            }
        }
        return $ret;
    }

    public static function IGEKeyPrimary($g, $tabname, $key01, $key02)
    {
        try {
            $sql = "--Sinh IGE" . PHP_EOL;
            $sql .= "SET NOCOUNT ON " . PHP_EOL;
            $sql .= "DECLARE @KeyString AS VARCHAR(20), " . PHP_EOL;
            $sql .= "@KeyFrom AS INT, " . PHP_EOL;
            $sql .= "@KeyTo AS INT" . PHP_EOL;
            $sql .= "EXEC D91P9119 '" . Auth::user()->UserID . "','$key01$key02','$tabname','1',@KeyString  OUTPUT, @KeyFrom  OUTPUT, @KeyTo OUTPUT,''" . PHP_EOL;
            $sql .= "SELECT @KeyString AS KeyString, @KeyFrom AS KeyFrom ";
            $rs = DB::connection()->selectOne($sql);
            $ret = "";
            if (isset($rs->KeyString)) {
                $keystring = $rs->KeyString;
                //Session::set("KeyString03", substr($keystring, strlen($key01) + strlen($key02)));
                session(["KeyString03" => substr($keystring, strlen($key01) + strlen($key02))]);
                $keyfrom = $rs->KeyFrom;
                $ret = $keystring . str_pad($keyfrom, 15 - strlen($keystring), "0", 0);
            }
            if ($ret == "" || strlen($ret) > 15) {
                throw new Exception("Lỗi sinh mã tự động cho khóa chính của $tabname");
            } else {
                return $ret;
            }

        } catch (Exception $e) {
            throw new Exception('Caught exception: ' . $e->getMessage() . "\n");
        }
    }

    static function resize_image($file, $w, $h, $crop = FALSE)
    {
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        if ($crop) {
            if ($width > $height) {
                $width = ceil($width - ($width * abs($r - $w / $h)));
            } else {
                $height = ceil($height - ($height * abs($r - $w / $h)));
            }
            $newwidth = $w;
            $newheight = $h;
        } else {
            if ($w / $h > $r) {
                $newwidth = $h * $r;
                $newheight = $h;
            } else {
                $newheight = $w / $r;
                $newwidth = $w;
            }
        }
        $src = imagecreatefromjpeg($file);
        $dst = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

        return $dst;
    }

    static function getAttr($rowData, $attr)
    {
        //////\Debugbar::info(gettype($rowData));
        if (gettype($rowData) == 'array') {
            return isset($rowData[$attr]) ? $rowData[$attr] : "";
        }
        if (gettype($rowData) == 'object') {
            return isset($rowData->$attr) ? $rowData->$attr : "";

        }
        return "";
    }

    static function statementWithDynamicStore($connectionName, $query)
    {
        $storeName = "DYNAMIC_PROC_" . str_random(10) . "_" . time();
        //////\Debugbar::info($storeName);
        DB::connection($connectionName)->statement("SET QUOTED_IDENTIFIER ON");
        DB::connection($connectionName)->statement("SET ANSI_NULLS ON");
        $sql = "--Create temporary table" . PHP_EOL;
        $sql .= "CREATE PROCEDURE " . $storeName . " AS BEGIN" . PHP_EOL;
        $sql .= "SET ANSI_NULL_DFLT_ON ON" . PHP_EOL;
        $sql .= "SET ANSI_DEFAULTS ON" . PHP_EOL;
        $sql .= "SET ARITHABORT ON" . PHP_EOL;
        $sql .= "SET ANSI_WARNINGS OFF" . PHP_EOL;
        $sql .= "SET NOCOUNT ON" . PHP_EOL;
        $sql .= "SET ANSI_PADDING ON" . PHP_EOL;
        $sql .= "SET CONCAT_NULL_YIELDS_NULL ON" . PHP_EOL;
        $sql .= $query . PHP_EOL;
        $sql .= "commit" . PHP_EOL;
        $sql .= "END" . PHP_EOL;

        try {
            $result = DB::connection($connectionName)->statement($sql);
            if ($result) {
                $sql = "--Execute temporary procedure" . PHP_EOL;
                $sql .= "EXEC " . $storeName;
                $rsData = DB::connection($connectionName)->statement($sql);
                Helpers::dropProcedureWithConnectionName($connectionName, $storeName);
                return $rsData;
            }
            return null;
        } catch (\Exception $ex) {
            Helpers::dropProcedureWithConnectionName($connectionName, $storeName);
            return null;
        }
    }

    static function selectWithDynamicStore($connectionName, $query)
    {
        $storeName = "DYNAMIC_PROC_" . str_random(10) . "_" . time();
        //////\Debugbar::info($storeName);
        DB::connection($connectionName)->statement("SET QUOTED_IDENTIFIER ON");
        DB::connection($connectionName)->statement("SET ANSI_NULLS ON");
        $sql = "--Create temporary table" . PHP_EOL;
        $sql .= "CREATE PROCEDURE " . $storeName . " AS BEGIN" . PHP_EOL;
        $sql .= "SET ANSI_NULL_DFLT_ON ON" . PHP_EOL;
        $sql .= "SET ANSI_DEFAULTS ON" . PHP_EOL;
        $sql .= "SET ARITHABORT ON" . PHP_EOL;
        $sql .= "SET ANSI_WARNINGS OFF" . PHP_EOL;
        $sql .= "SET NOCOUNT ON" . PHP_EOL;
        $sql .= "SET ANSI_PADDING ON" . PHP_EOL;
        $sql .= "SET CONCAT_NULL_YIELDS_NULL ON" . PHP_EOL;
        $sql .= $query . PHP_EOL;
        $sql .= "commit" . PHP_EOL;
        $sql .= "END" . PHP_EOL;

        try {
            $result = DB::connection($connectionName)->statement($sql);
            if ($result) {
                $sql = "--Execute temporary procedure" . PHP_EOL;
                $sql .= "EXEC " . $storeName;
                $rsData = DB::connection($connectionName)->select($sql);
                Helpers::dropProcedureWithConnectionName($connectionName, $storeName);
                return $rsData;
            }
            return null;
        } catch (\Exception $ex) {
            Helpers::dropProcedureWithConnectionName($connectionName, $storeName);
            return null;
        }
    }

    static function selectOneWithDynamicStore($connectionName, $query)
    {
        $storeName = "DYNAMIC_PROC_" . str_random(10) . "_" . time();
        //////\Debugbar::info($storeName);
        DB::connection($connectionName)->statement("SET QUOTED_IDENTIFIER ON");
        DB::connection($connectionName)->statement("SET ANSI_NULLS ON");
        $sql = "--Create temporary table" . PHP_EOL;
        $sql .= "CREATE PROCEDURE " . $storeName . " AS BEGIN" . PHP_EOL;
        $sql .= "SET ANSI_NULL_DFLT_ON ON" . PHP_EOL;
        $sql .= "SET ANSI_DEFAULTS ON" . PHP_EOL;
        $sql .= "SET ARITHABORT ON" . PHP_EOL;
        $sql .= "SET ANSI_WARNINGS OFF" . PHP_EOL;
        $sql .= "SET NOCOUNT ON" . PHP_EOL;
        $sql .= "SET ANSI_PADDING ON" . PHP_EOL;
        $sql .= "SET CONCAT_NULL_YIELDS_NULL ON" . PHP_EOL;
        $sql .= $query . PHP_EOL;
        $sql .= "commit" . PHP_EOL;
        $sql .= "END" . PHP_EOL;

        try {
            $result = DB::connection($connectionName)->statement($sql);
            if ($result) {
                $sql = "--Execute temporary procedure" . PHP_EOL;
                $sql .= "EXEC " . $storeName;
                $rsData = DB::connection($connectionName)->selectOne($sql);
                Helpers::dropProcedureWithConnectionName($connectionName, $storeName);
                return $rsData;
            }
            return null;
        } catch (\Exception $ex) {
            Helpers::dropProcedureWithConnectionName($connectionName, $storeName);
            return null;
        }
    }

    static function dropProcedureWithConnectionName($connectionName, $storeName)
    {
        $connection = DB::connection($connectionName);
        $sql = "--Drop procedure" . PHP_EOL;
        $sql .= "USE " . $connection->getDatabaseName() . PHP_EOL;
        $sql .= "IF EXISTS (SELECT TOP 1 1 FROM DBO.SYSOBJECTS WHERE ID = OBJECT_ID(N'[DBO].[" . $storeName . "]') AND  OBJECTPROPERTY(ID, N'IsProcedure') = 1)" . PHP_EOL;
        $sql .= "DROP PROCEDURE [DBO].[" . $storeName . "]" . PHP_EOL;
        try {
            DB::connection($connectionName)->unprepared(DB::raw($sql));
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    static function selectWithFunction($connectionName, $query)
    {

        $connection = DB::connection($connectionName);
        $sql = "USE " . $connection->getDatabaseName() . PHP_EOL;
        $sql .= "SET ANSI_NULL_DFLT_ON ON" . PHP_EOL;
        $sql .= "SET ANSI_DEFAULTS ON" . PHP_EOL;
        $sql .= "SET ARITHABORT ON" . PHP_EOL;
        $sql .= "SET ANSI_WARNINGS OFF" . PHP_EOL;
        $sql .= "SET NOCOUNT ON" . PHP_EOL;
        $sql .= "SET ANSI_PADDING ON" . PHP_EOL;
        $sql .= "SET CONCAT_NULL_YIELDS_NULL ON" . PHP_EOL;
        $sql .= $query . PHP_EOL;
        try {
            return $connection->select($sql);
        } catch (\Exception $ex) {
            ////\Debugbar::info($ex->getMessage());
            return null;
        }
        //DB::unprepared($sql);
    }

    public static function getEnv()
    {
        $env = file_get_contents(base_path() . '/.env');
        // Split string on every " " and write into array
        $env = preg_split('/\s+/', $env);;
        // Loop through given data
        $output = [];
        foreach ($env as $key => $value) {
            $row = explode('=', $value);
            $output[$row[0]] = (isset($row[1]) ? $row[1] : '');
        }
        //////\Debugbar::info($output);
        return $output;
    }

    public static function getEnvWithKey($key)
    {
        $env = file_get_contents(base_path() . '/.env');
        // Split string on every " " and write into array
        $env = preg_split('/\s+/', $env);;
        // Loop through given data
        $output = [];
        foreach ($env as $key => $value) {
            $row = explode('=', $value);
            $output[$row[0]] = (isset($row[1]) ? $row[1] : '');
        }
        if (count($output) > 0) {
            return isset($output[$key]) ? $output[$key] : 'abc123';
        } else {
            return '';
        }

    }

    public static function exportExcel($data, $fileName = '')
    {
        $name = 'data_' . time() . '.xlsx';
        if ($fileName != '') {
            $name = $fileName . "_" . time() . '.xlsx';
        }
        Excel::store(new MyExportExcel($data), $name);
        return $name;
    }

    public static function printWord($reportId, $rowData = '')
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '600');
        ini_set('max_input_time', '600');
        ini_set('post_max_size', '1024M');

        if ($rowData != '') {
            $oldPath2007 = public_path() . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $reportId . '.docx';
            $oldPath2003 = public_path() . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $reportId . '.doc';
            if (File::exists($oldPath2007) == false) {
                return json_encode(['status' => 'NOTEXIST', 'message' => Helpers::getRS("Tap_tin_khong_ton_tai") . ' <b style="color:red">(' . $reportId . '.docx' . ')</b>']);
            }
            try {

                $newName = $rowData->FileName . "_" . time() . '.docx';
                $newPath = storage_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $newName;
                $templateProcessor = new TemplateProcessor($oldPath2007);
                foreach ($rowData as $key => $value) {
                    $templateProcessor->setValue($key, htmlspecialchars($value));
                }
                $templateProcessor->saveAs($newPath);

                return json_encode(['status' => 'SUCCESS', 'fileName' => $newName]);
            } catch (\Exception $ex) {
                ////\Debugbar::info($ex->getMessage());
                return json_encode(['status' => 'ERROR', 'message' => $ex->getMessage()]);
            }
        } else {
            return json_encode(['status' => 'NODATA']);
        }
    }

    public static function isDatabaseReady($connection = 'sqlsrv')
    {
        $isReady = true;
        try {
            DB::connection($connection)->getPdo();
        } catch (\Exception $e) {
            $isReady = false;
        }

        return $isReady;
    }

    public static function isRedisReady($connection = 'default')
    {
        $isReady = true;
        try {
            $redis = Redis::connection($connection);
            $redis->connect();
            $redis->disconnect();
        } catch (\Exception $e) {
            $isReady = false;
        }

        return $isReady;
    }

    public static function writeLog($message)
    {
        $file = storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error.log';
        $prepend = "<pre>" . date("d/m/Y h:i:sa") . "<strong>" . " $message" . "</strong>" . "</br></pre>";
        File::append($file, $prepend);
    }


    public static function mainConnectionName()
    {
        return 'sqlsrv';
    }

    public static function lmsConnectionName()
    {
        return 'sqlsrvLMS';
    }

    public static function select($sql, $transaction = false, $connectionName = 'sqlsrv')
    {
        try {
            $connection = DB::connection($connectionName);
            if ($transaction) {
                $connection->getPdo()->beginTransaction();
            }
            $sql = "SET NOCOUNT ON" . PHP_EOL . $sql;
            $stmt = $connection->getPdo()->prepare($sql);
            if (env('APP_DEBUG', '')) {
                ////\Debugbar::info($stmt->queryString);
            }
            $stmt->execute();
            do {
                $result = $stmt->fetchAll(PDO::FETCH_CLASS);
                ////\Debugbar::info($result);
            } while ($stmt->nextRowset());

            if ($transaction) {
                $connection->getPdo()->commit();
            }

            if (count($result) == 0)
                return null;
            return $result;
        } catch (\Exception $ex) {
            Helpers::writeLog($ex->getMessage());
            if ($transaction) {
                $connection->getPdo()->rollBack();
            }
            return null;
        }
    }

    public static function selectOne($sql, $transaction = false, $connectionName = 'sqlsrv')
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '600');
        ini_set('max_input_time', '600');
        ini_set('post_max_size', '1024M');

        try {
            $connection = DB::connection($connectionName);
            if ($transaction) {
                $connection->getPdo()->beginTransaction();
            }

            $sql = "SET NOCOUNT ON" . PHP_EOL . $sql;

            $stmt = $connection->getPdo()->prepare($sql);
            Helpers::writeLog($sql);
            if (env('APP_DEBUG', '')) {
                ////\Debugbar::info($stmt->queryString);
            }
            $stmt->execute();

//            do {
//                $result = $stmt->fetchAll(PDO::FETCH_CLASS);
//            } while ($stmt->nextRowset());
            while ($stmt->columnCount()) {
                $result[] = $stmt->fetchAll(PDO::FETCH_ASSOC); //or, $pdo->fetchAll()
                $stmt->nextRowset();
            }
            if ($transaction) {
                $connection->getPdo()->commit();
            }
            //var_dump($result);die;
            if (count($result) > 0) {
                return (object)$result[0][0];
            }

        } catch (\Exception $ex) {
            Helpers::writeLog($ex->getMessage());
            return $ex->getMessage();
            if ($transaction) {
                $connection->getPdo()->rollBack();
            }
            return null;
        }
    }

    public static function statement($sql, $connectionName = 'sqlsrv')
    {
        try {
            $connection = DB::connection($connectionName);
            $connection->getPdo()->beginTransaction();
            if (env('APP_DEBUG', '')) {
                ////\Debugbar::info($sql);
            }
            $connection->getPdo()->exec($sql);
            $connection->getPdo()->commit();
            return true;
        } catch (\Exception $ex) {
            Helpers::writeLog($ex->getMessage());
            $connection->getPdo()->rollBack();
            return false;

        }
    }

    CONST TYPE_NULL = "NULL";
    CONST TYPE_EMPTY = "EMPTY";
    CONST TYPE_EMPTY_ARRAY = "NULL";

    public static function getAttrInArray($array, $attr, $default = 0)
    {
        if (isset($array[$attr])) {
            return $array[$attr];
        } else {
            return $default;
        }
    }


//    static function getParams($arrStore, $fields, $func)
//    {
//
//        $fields = array_filter($fields, function ($row) {
//            return count($row["items"]) > 0;
//        });
//        /////\Debugbar::info($fields);
//        foreach ($fields as $field) {
//            ////\Debugbar::info($field);
//            $cell = $field["cell"];
//            $items = $field["items"];
//            $replacedValue = null;
//            //if (strpos($cell, "EmployeeName") !== false){
//                ////\Debugbar::info($items);
//            /*foreach ($items as $item) {
//                ////\Debugbar::info($item);
//                $table = $item["table"];
//                $findStore = array_filter($arrStore, function ($store) use ($item) {
//                    return $item["storeName"] == $store["storeName"];
//                });
//                if ($table) {
//                    $k = 0;
//                    for ($i=0;$i<count($findStore[0]["data"]);$i++){
//                        $row = $findStore[0]["data"][$i];
//                        if ($replacedValue == null) {
//                            ////\Debugbar::info("if");
//                            $f = $item["fieldName"];
//                            $v = $row->$f;
//                            ////\Debugbar::info($v);
//                            ////\Debugbar::info($v);
//                            $temValue = str_replace("[" . $item["storeName"] . "." . $item["fieldName"] . "]", $v, $cell);
//                        } else {
//                            ////\Debugbar::info("else");
//                            ////\Debugbar::info($row);
//                            ////\Debugbar::info($replacedValue);
//                            //\Debugbar::info($replacedValue);
//
//                            $temValue = str_replace("[" . $item["storeName"] . "." . $item["fieldName"] . "]", $replacedValue[$i - 1], $cell);
//                        }
//
//                        $replacedValue[$k] = $temValue;
//                        $k++;
//                    }
//
//                } else {
//                    $f = $item["fieldName"];
//                    ////\Debugbar::info($item["storeName"]);
//                    //$data = $findStore[0]["data"][0];
//                    ////\Debugbar::info($data);
//                    ////\Debugbar::info($f);
//                    ////\Debugbar::info($data->SumOT);
//                    $v = $findStore[0]["data"][0]->$f;
//                    ////\Debugbar::info("1". $replacedValue);
//                    if ($replacedValue == null) {
//                        $replacedValue = str_replace("{" . $item["storeName"] . "." . $item["fieldName"] . "}", $v, $cell);
//                        ////\Debugbar::info("1". $replacedValue);
//                    } else {
//                        ////\Debugbar::info($findStore[0]["data"][0]);
//                        $replacedValue = str_replace("{" . $item["storeName"] . "." . $item["fieldName"] . "}", $v, $replacedValue);
//                        ////\Debugbar::info("2". $replacedValue);
//                    }
//
//                }
//
//
//            }*/
//            //}
//            foreach ($items as $item) {
//                $table = $item["table"];
//                $findStore = array_filter($arrStore, function ($store) use ($item) {
//                    return $item["storeName"] == $store["storeName"];
//                });
//                if ($table) {
//                    if ($item["fieldName"] == "OTFrom"){
//
//                    }
//                    if ($replacedValue == null){
//                        $replacedValue = array();
//                    }
//
//                    $k = 0;
//                    for ($i=0;$i<count($findStore[0]["data"]);$i++){
//                        $row = $findStore[0]["data"][$i];
//                        if ( count($replacedValue) != count($findStore[0]["data"])) {
//                            $f = $item["fieldName"];
//
//                            if ($f == "Null"){
//                                $v = "";
//                            }else{
//                                $v = $row->$f;
//                            }
//                            $temValue = str_replace("[" . $item["storeName"] . "." . $item["fieldName"] . "]", $v, $cell);
//                        }
//                        if (count($replacedValue) == count($findStore[0]["data"]) ){
//                            $f = $item["fieldName"];
//                            $v = $row->$f;
//                            $temValue = str_replace("[" . $item["storeName"] . "." . $item["fieldName"] . "]",$v , $replacedValue[$i]);
//                        }
//                        if ($item["fieldName"] == "OTFrom" || $item["fieldName"] == "OTTo"){
//
//                        }
//                        $replacedValue[$k] = $temValue;
//                        $k++;
//                    }
//
//                } else {
//                    $f = $item["fieldName"];
//                    ////\Debugbar::info($item["storeName"]);
//                    //$data = $findStore[0]["data"][0];
//                    ////\Debugbar::info($data);
//                    ////\Debugbar::info($f);
//                    ////\Debugbar::info($data->SumOT);
//
//                    if ($f == "Null"){
//                        $v = "";
//                    }else{
//                        $v = $findStore[0]["data"][0]->$f;
//                    }
//                    ////\Debugbar::info("1". $replacedValue);
//                    if ($replacedValue == null) {
//                        $replacedValue = str_replace("{" . $item["storeName"] . "." . $item["fieldName"] . "}", $v, $cell);
//                        ////\Debugbar::info("1". $replacedValue);
//                    } else {
//                        ////\Debugbar::info($findStore[0]["data"][0]);
//                        $replacedValue = str_replace("{" . $item["storeName"] . "." . $item["fieldName"] . "}", $v, $replacedValue);
//                        ////\Debugbar::info("2". $replacedValue);
//                    }
//
//                }
//
//
//            }
//            $params[$cell] = $replacedValue;
//        }
//        /*////\Debugbar::info($fields);
//        foreach ($fields as $field) {
//            $replacedValue = "";
//            foreach ($arrStore as $store) {
//                if (strpos($field, $store["storeName"]) !== false && strpos($field, "{") !== false){
//                    //$f = str_replace($store["storeName"].".","",$field);
//                    //$f1 = str_replace("{", "", $f);
//                    //$f1 = str_replace("}", "", $f1);
//
//                    //$v = $store["data"][0]->$f1;
//                    foreach ($store["data"] as $row){
//                        foreach ($row as $key => $val){
//                            ////\Debugbar::info($store["data"]);
//                            $replacedValue = str_replace("{".$store["storeName"].".".$key."}", $val, $field);
//                        }
//                        $params[$field]= $replacedValue;
//                    }
//
//                    //$params[$field]= $v;
//                }
//                if (strpos($field, $store["storeName"]) !== false && strpos($field, "[") !== false){
//                    //$f = str_replace($store["storeName"].".","",$field);
//                    //$f1 = str_replace("[", "", $f);
//                    //$f1 = str_replace("]", "", $f1);
//                    //$v = array_column($store["data"], $f1);
//                    //////\Debugbar::info($f1);
//                    //$params[$field]= $v;
//                    $replacedValueArray = array();
//
//                    foreach ($store["data"] as $row){
//                        foreach ($row as $key=>$val){
//                            $arrayCols = array_column($store["data"], $key);
//                            foreach ($arrayCols as $r){
//                                $replacedValue = str_replace("[".$store["storeName"].".".$key."]", $val , $field);
//                            }
//                            array_push($replacedValueArray, $replacedValue);
//                        }
//
//                    }
//                    $params[$field]= $replacedValueArray;
//                }
//            }
//
//        }*/
//        ////\Debugbar::info($params);
//        if ($func != null) {
//            return $func($params);
//        }
//
//        return $params;
//
//
//    }


    static function getParams($arrStore, $fields, $func)
    {

        $fields = array_filter($fields, function ($row) {
            return count($row["items"]) > 0;
        });
        ////\Debugbar::info($arrStore);
        ////\Debugbar::info($fields);
        foreach ($fields as $field) {
            $cell = $field["cell"];
            $items = $field["items"];
            $replacedValue = null;
            foreach ($items as $item) {
                $table = $item["table"];


                $findStore = Helpers::arraySearch2($arrStore, 'storeName', $item["storeName"]);
                //\Debugbar::info($findStore);
                if ($table) {
                    if ($item["fieldName"] == "OTFrom") {

                    }
                    if ($replacedValue == null) {
                        $replacedValue = array();
                    }

                    $k = 0;

                    for ($i = 0; $i < count($findStore[0]["data"]); $i++) {
                        $row = $findStore[0]["data"][$i];
                        if (count($replacedValue) != count($findStore[0]["data"])) {
                            $f = $item["fieldName"];

                            if ($f == "Null") {
                                $v = "";
                            } else {
                                $v = $row->$f;
                            }
                            $temValue = str_replace("[" . $item["storeName"] . "." . $item["fieldName"] . "]", $v, $cell);
                        }
                        if (count($replacedValue) == count($findStore[0]["data"])) {
                            $f = $item["fieldName"];
                            $v = $row->$f;
                            $temValue = str_replace("[" . $item["storeName"] . "." . $item["fieldName"] . "]", $v, $replacedValue[$i]);
                        }
                        if ($item["fieldName"] == "OTFrom" || $item["fieldName"] == "OTTo") {

                        }
                        $replacedValue[$k] = $temValue;
                        $k++;
                    }

                } else {
                    $f = $item["fieldName"];
                    if ($f == "Null") {
                        $v = "";
                    } else {
                        ////\Debugbar::info($findStore[0]["data"]);
                        $v = $findStore[0]["data"][0]->$f;
						if (substr($v,0,1) === "."){
                            $v = "0".$v;

                        }

                    }
                    if ($replacedValue == null) {
                        $replacedValue = str_replace("{" . $item["storeName"] . "." . $item["fieldName"] . "}", $v, $cell);
                    } else {
                        $replacedValue = str_replace("{" . $item["storeName"] . "." . $item["fieldName"] . "}", $v, $replacedValue);
                    }

                }


            }
            $params[$cell] = $replacedValue;
        }

        if ($func != null) {
            return $func($params);
        }

        return $params;


    }

    static function getFields($templateFile)
    {
        $objPHPExcel = PHPExcel_IOFactory::load($templateFile);

        //Lấy ra số trang sử dụng phương thức getSheetCount();
        // Lấy Ra tên trang sử dụng getSheetNames();

        //Chọn trang cần truy xuất
        $sheet = $objPHPExcel->setActiveSheetIndex(0);

        //Lấy ra số dòng cuối cùng
        $Totalrow = $sheet->getHighestRow();
        //Lấy ra tên cột cuối cùng
        $LastColumn = $sheet->getHighestColumn();

        //Chuyển đổi tên cột đó về vị trí thứ, VD: C là 3,D là 4
        $TotalCol = PHPExcel_Cell::columnIndexFromString($LastColumn);

        //Tạo mảng chứa dữ liệu
        $data = [];
        $fields = [];
        $arrayField = [];
        $k = 0;
        $h = 0;
        //Tiến hành lặp qua từng ô dữ liệu
        //----Lặp dòng, Vì dòng đầu là tiêu đề cột nên chúng ta sẽ lặp giá trị từ dòng 2
        for ($i = 2; $i <= $Totalrow; $i++) {
            //----Lặp cột
            for ($j = 0; $j < $TotalCol; $j++) {
                // Tiến hành lấy giá trị của từng ô đổ vào mảng
                $value = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                if ($value != null && is_string($value)) {
                    $arrayField[$h] = $value;
                    $h++;
                }

                /*$arrTemp = explode(" ", $value);
                foreach ($arrTemp as $row) {
                    if ($row != "" && (strpos($row, "{") !== false || strpos($row, "[") !== false)) {
                        $fields[$k] = $row;
                        $k++;
                    }

                }
                $data[$i - 2][$j] = $sheet->getCellByColumnAndRow($j, $i)->getValue();*/
            }
        }
        ////\Debugbar::info($arrayField);
        return $arrayField;
    }

    static function fieldsToArray($fields)
    {
        $output = [];
        foreach ($fields as $field) {
            $arrTemp = explode(" ", $field);
            $items = [];
            foreach ($arrTemp as $item) {
                $item = trim($item);
                if (strpos($item, "{") !== false || strpos($item, "[") !== false) {
                    $f = str_replace("{", "", $item);
                    $f = str_replace("}", "", $f);
                    $f = str_replace("[", "", $f);
                    $f = str_replace("]", "", $f);
                    $s = substr($f, 0, 8);
                    $p = substr($f, 9, strlen($f));
                    array_push($items, array(
                        "storeName" => $s,
                        "fieldName" => $p,
                        "table" => !((strpos($item, "{")) !== false)
                    ));
                }
            }
            array_push($output, array(
                'cell' => $field,
                'items' => $items
            ));
        }
        return $output;
    }

    static function exportExelTemplate($arrStore, $fileName, $func = null)
    {

        //return "test";
        $templateFile = public_path() . '/reports/' . $fileName;
        $outputFileName = time() . "__" . $fileName;
        $fileName = storage_path() . '/app/' . $outputFileName;
        $fields = Helpers::getFields($templateFile);


        $fieldsFilter = Helpers::fieldsToArray($fields);
        $params = Helpers::getParams($arrStore, $fieldsFilter, $func);

        PhpExcelTemplator::saveToFile($templateFile, $fileName, $params, function () use ($outputFileName, $fileName) {

        });
        return $outputFileName;
    }

    static function arraySum($arr, $fieldName)
    {
        $result = 0;
        foreach ($arr as $row) {
            $result = $result + floatval($row->$fieldName);
        }
        return $result;
    }

    static function getFieldList($beforeValue)
    {
        $arrTemp = explode(" ", $beforeValue);
        $items = [];
        foreach ($arrTemp as $item) {
            $item = trim($item);
            if (strpos($item, "{") !== false || strpos($item, "[") !== false) {
                $f = str_replace("{", "", $item);
                $f = str_replace("}", "", $f);
                $f = str_replace("[", "", $f);
                $f = str_replace("]", "", $f);
                $s = substr($f, 0, 8);
                $p = substr($f, 9, strlen($f));
                array_push($items, array(
                    "storeName" => $s,
                    "fieldName" => $p,
                    "table" => !((strpos($item, "{")) !== false)
                ));
            }
        }
        return $items;
    }

    static function setSumValue($data, $beforeValue)
    {

        $result = '';
        $f = str_replace("{", "", $beforeValue);
        $f = str_replace("}", "", $f);
        $f = str_replace("[", "", $f);
        $f = str_replace("]", "", $f);
        //$store = $master["storeName"];
        $arr = explode(".", $f);

        $result = self::arraySum($data, $arr[1]);
        return $result;
    }

    static function setValueMaster($master, $beforeValue)
    {

        $result = '';
        $f = str_replace("{", "", $beforeValue);
        $f = str_replace("}", "", $f);
        $f = str_replace("[", "", $f);
        $f = str_replace("]", "", $f);
        //$store = $master["storeName"];
        $data = $master;
        $arr = explode(".", $f);
        switch ($arr[0]) {
            case 'Group':
                break;
            case 'Sum':
                break;
            default:
                foreach ($data as $key => $value) {
                    if ($key == $arr[1]) {
                        //\Debugbar::info($key);
                        return $value;
                    }
                }
                break;
        }


        return $result;
    }


    static function createTemplate($master, $detail, $fileName)
    {
        $detail = array_reverse($detail);
        //$fileName = 'SSD_OTTHANG.xlsx';
        $newFileName = time() . "__" . $fileName;
        $fileInput = public_path() . '/reports/' . $fileName;
        $fileOutput = storage_path() . '/app/' . $newFileName;


        $fileType = PHPExcel_IOFactory::identify($fileInput);
        $objReader = PHPExcel_IOFactory::createReader($fileType);
        $objPHPExcel = $objReader->load($fileInput);
        $sheet = $objPHPExcel->setActiveSheetIndex(0);

        $totalRow = $sheet->getHighestRow();
        $alphaColumn = $sheet->getHighestColumn();


        $totalCol = PHPExcel_Cell::columnIndexFromString($alphaColumn);
        $alphaColumn = PHPExcel_Cell::stringFromColumnIndex($totalCol);


        //set master
        for ($i = 0; $i <= $totalRow; $i++) {
            for ($j = 0; $j < $totalCol; $j++) {
                $currentAlpha = PHPExcel_Cell::stringFromColumnIndex($j);
                $beforeValue = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                if ($beforeValue != null) {
                    if (strpos($beforeValue, "{") !== false && strpos($beforeValue, "}") !== false) {
                        $afterValue = self::setValueMaster($master, $beforeValue);
                        $sheet->setCellValue($currentAlpha . $i, $afterValue);
                    }

                }

            }
        }

        $detailIndx = 0;
        $groupIndx = 0;



        $groupByDataIndx = '';
        $groupByDataName = '';
        //get index detail
        for ($i = 0; $i <= $totalRow; $i++) {
            for ($j = 0; $j < $totalCol; $j++) {
                $currentAlpha = PHPExcel_Cell::stringFromColumnIndex($j);
                $beforeValue = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                if ($beforeValue != null) {
                    if (strpos($beforeValue, "Detail.") !== false) {
                        $detailIndx = $i;
                    }
                    if (strpos($beforeValue, "Group.") !== false) {
                        $groupIndx = $i;
                        $f = str_replace("]", "", $beforeValue);
                        $arr = explode(".", $f);
                        $groupByDataIndx = $arr[1];
                        $groupByDataName = $arr[2];
                    }
                }

            }
        }
        $detailIndxFormat = $detailIndx;
        $groupIndxFormat = $groupIndx;
        \Debugbar::info($detailIndx);
        \Debugbar::info($groupByDataName);
        //\Debugbar::info($groupByDataIndx);


        //get list group
        $arrID = array('');
        foreach ($detail as $row) {
            if (array_search($row->$groupByDataIndx, $arrID) == "")
                array_push($arrID, $row->$groupByDataIndx);
        }

        $countGroup = count($arrID);
        $cellValuesGroup = $objPHPExcel->getActiveSheet()->rangeToArray('A' . $groupIndx . ':' . $alphaColumn . $groupIndx);
        $cellStyleGroup = $objPHPExcel->getActiveSheet()->getStyle('A' . $groupIndx . ':' . $alphaColumn . $groupIndx);

        $cellValues = $objPHPExcel->getActiveSheet()->rangeToArray('A' . $detailIndx . ':' . $alphaColumn . $detailIndx);
        //$cellStyle = $objPHPExcel->getActiveSheet()->getStyle('A' . $detailIndx . ':' . $alphaColumn . $detailIndx);

        \Debugbar::info($cellStyleGroup);

        foreach (($arrID) as $groupID) {
            if ($groupID != "") {

                $detailSub = array_reverse(Helpers::arraySearch($detail, $groupByDataIndx, $groupID));

                //insert detail
                $sheet->insertNewRowBefore($groupIndx, count($detailSub));
                $detailIndxFormat = $detailIndxFormat +  count($detailSub);
                $groupIndxFormat = $groupIndxFormat +  count($detailSub);

                $k = 0;
                for ($i = $groupIndx; $i < $groupIndx + count($detailSub); $i++) {
                    $objPHPExcel->getActiveSheet()->fromArray($cellValues, null, 'A' . $i);
                    $totalColFormat = PHPExcel_Cell::columnIndexFromString($alphaColumn) - 3;
                    $alphaColumnFormat = PHPExcel_Cell::stringFromColumnIndex($totalColFormat);
                    $cellStyle = $objPHPExcel->getActiveSheet()->getStyle('A' . $detailIndxFormat . ':' . $alphaColumnFormat . $detailIndxFormat);
                    $objPHPExcel->getActiveSheet()->duplicateStyle($cellStyle, 'A' . $i . ':' . $alphaColumnFormat . $i);

                    for ($j = 0; $j < $totalCol; $j++) {
                        $currentAlpha = PHPExcel_Cell::stringFromColumnIndex($j);
                        $beforeValue = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                        if ($beforeValue != null) {
                            $afterValue = self::setValueMaster($detailSub[$k], $beforeValue);
                            $sheet->setCellValue($currentAlpha . $i, $afterValue);
                        }

                    }
                    $k = $k + 1;

                }



                //insert group
                $sheet->insertNewRowBefore($groupIndx, 1);
                $detailIndxFormat = $detailIndxFormat + 1;
                $groupIndxFormat = $groupIndxFormat + 1;

                $objPHPExcel->getActiveSheet()->fromArray($cellValuesGroup, null, 'A' . $groupIndx);
                $totalColFormat = PHPExcel_Cell::columnIndexFromString($alphaColumn) - 3;
                $alphaColumnFormat = PHPExcel_Cell::stringFromColumnIndex($totalColFormat);

                $cellStyleGroup = $objPHPExcel->getActiveSheet()->getStyle('A' . $groupIndxFormat . ':' . $alphaColumnFormat . $groupIndxFormat);
                $objPHPExcel->getActiveSheet()->duplicateStyle($cellStyleGroup, 'A' . $groupIndx . ':' . $alphaColumnFormat . $groupIndx);

                if (count($detailSub) > 0){
                    \Debugbar::info($detailSub[0]->$groupByDataName);
                }

                for ($j = 0; $j < $totalCol; $j++) {
                    $currentAlpha = PHPExcel_Cell::stringFromColumnIndex($j);
                    $beforeValue = $sheet->getCellByColumnAndRow($j, $groupIndx)->getValue();
                    if ($beforeValue != null) {
                        if (strpos($beforeValue, "Group.") !== false) {
                            $afterValue = count($detailSub) == 0 ? "" : $detailSub[0]->$groupByDataName;
                            $sheet->setCellValue($currentAlpha . $groupIndx, $afterValue);
                        }
                        if (strpos($beforeValue, "Sum.") !== false) {
                            $afterValue = Helpers::setSumValue($detailSub, $beforeValue);
                            $sheet->setCellValue($currentAlpha . $groupIndx, $afterValue);
                        }
                    }

                }

            }


        }
        $sheet->removeRow($groupIndxFormat, 2);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $fileType);
        $objWriter->save($fileOutput);
        return $newFileName;

    }
}



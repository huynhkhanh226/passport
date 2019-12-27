<?php
namespace App\Classes;

/**
 * Created by PhpStorm.
 * User: THANHTRAM
 * Date: 30/07/2018
 * Time: 9:42 AM
 */
class eHelpers
{
    const CONNECTION_LMS = 'sqlsrvLMS';
    const CONNECTION = 'sqlsrv';//Tam thoi eOfice se dung sqlsrv nay

    //do nguon combo chung cho $dataID
    public static function LoadFixData($dataID, $description = '', $isAll = false)
    {
        $sql = "--".$description.PHP_EOL;
        $sql .= "EXEC W91P0500 '$dataID', '" . Session::get('Lang') . "'";
        $rsTemp = DB::connection(eHelpers::CONNECTION)->select($sql);
        $dataset = [];
        if ($isAll){
            array_push($dataset, array('ID'=>'', 'Name'=>Lang::get("message.Tat_ca_Web")));
            foreach ($rsTemp as $row){
                array_push($dataset, $row);
            }
        }else{
            $dataset = $rsTemp;
        }
        return $dataset;
    }

    // do nguon combo cho nhom van ban
    public static function LoadDocGroup($isAll = false)
    {
        $sql = "--"."Do nguon nhom van ban".PHP_EOL;
        $sql .= "select DocGroupCode, DocGroupName,DisplayOrder from D76T1000 where Deleted = 0".PHP_EOL;
        if ($isAll) {
            $sql .= " Union All" . PHP_EOL;
            $sql .= " Select '' as DocGroupCode, N'" . Lang::get("message.Tat_ca_Web") . "' as DocGroupName,  0 as DisplayOrder" . PHP_EOL;
        }
        $sql .= "Order by DisplayOrder asc".PHP_EOL;
        $dataset = DB::connection(eHelpers::CONNECTION)->select($sql);
        return $dataset;
    }

    // do nguon combo cho don vi
    public static function LoadDivision($lang, $key01 = 'DIV', $key02 = 'LemonHR', $key03='', $key04 = '', $key05 ='')
    {
        $sql = "--".'Do nguon cho Division'.PHP_EOL;
        $sql .= "select ID, Name$lang as Name  from W76N5555('$key01','$key02','$key03','$key04','$key05')";
        \Debugbar::info($sql);
        $dataset = DB::connection(eHelpers::CONNECTION)->select($sql);
        return $dataset;
    }

    //do nguon combo cho phong ban
    public static function LoadDepartment($lang, $key01 = 'DEP', $key02 = 'LemonHR', $key03='', $key04 = '', $key05 ='')
    {
        $sql = "--".'Do nguon cho Department'.PHP_EOL;
        $sql .= "select ID, Name$lang as Name, FilterID  from W76N5555('$key01','$key02','$key03','$key04','$key05')";
        $dataset = DB::connection(eHelpers::CONNECTION)->select($sql);
        return $dataset;
    }

    //do nguon combo cho nhan vien
    public static function LoadEmployee($lang, $key01 = 'EMP', $key02 = 'LemonHR', $key03='', $key04 = '', $key05 ='')
    {
        $sql = "--". 'Do nguon cho Employee'.PHP_EOL;
        $sql .= "select ID, Name$lang as Name, FilterID  from W76N5555('$key01','$key02','$key03','$key04','$key05')";
        $dataset = DB::connection(eHelpers::CONNECTION)->select($sql);
        return $dataset;
    }

    //do nguon combo cho nguoi gui
    public static function Loadsender($lang, $key01 = 'EMP', $key02 = 'LemonHR', $key03='', $key04 = '', $key05 ='')
    {
        $sql = "--". 'Do nguon cho Sender'.PHP_EOL;
        $sql .= "select ID, Name$lang as Name  from W76N5555('$key01','$key02','$key03','$key04','$key05')";
        $dataset = DB::connection(eHelpers::CONNECTION)->select($sql);
        return $dataset;
    }

    //lay extension dinh kem
    static function getAttExtList(){
        $arrFileExt = \Config::get('attachment.fileExtension');
        $arrFileType = array();
        if (isset($arrFileExt) && !empty($arrFileExt)) {
            foreach ($arrFileExt as $key => $value) {
                if ($value['val'] == true) {
                    if ($key != "zip1") {//lo?i tr? file zip1
                        array_push($arrFileType, '.' . $key);
                    }
                }
            }
        }
        return $arrFileType;
    }


}
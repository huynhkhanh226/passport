<?php

namespace App;

use App\Classes\Helpers;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Laravel\Passport\HasApiTokens;

/**
 * Class Users
 * Use for store use credentail and information
 * We can use the Auth::user()->getCommonSetting() for get the Procedure variables
 *
 * @package App
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    /**
     * Table name = D00T0030
     * Table description: Entity Folder
     */
    protected $connection = 'sqlsrvLMS';
    protected $table = 'D00T0030';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['UserID','UserPassword','UserNameU','UserDepartmentU','UserGroupID', 'id'];

//    /**
//     * use the UserPassword field for auth
//     * @return string
//     */
//    public function getAuthPassword()
//    {
//        //var_dump($this->UserPassword);die();
//        return $this->UserPassword;
//    }
//
//    public function username()
//    {
//        return 'UserID';
//    }


    public function getRememberToken()
    {
        return $this->NewLogonToken;
    }

    public function setRememberToken($value)
    {
        $this->NewLogonToken = $value;
    }

    public function getRememberTokenName()
    {
        return 'NewLogonToken';
    }

}

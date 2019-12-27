<?php

namespace App\Http\Controllers\Auth;

use App\Classes\Helpers;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use App\Users;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }



    public function login(Request $request) {
        ob_end_clean();
        ob_start();
        $dataPost = $request->input();
        $userId = $dataPost['UserId'];
        $password = $dataPost['password'];
        try {
            $user = User::where("UserID", '=', $userId)->first();
            $remember = true;
            if ($user == null){
                Session::flash('errorMessage',"Người dùng không tồn tại trong hệ thống");
                return redirect('/login');
            }else{
                if (Helpers::encrypt_userpass($password) == $user->UserPassword) {
                    \Auth::login($user, $remember);
                    Helpers::setSession('user', \Auth::user());
                    return redirect()->intended();
                } else {
                    Session::flash('errorMessage',"Thông tin đăng nhập không chính xác");
                    return redirect('/login');
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: dav
 * Date: 2014-09-25
 * Time: 15:19
 */

class CookieStorage {
   //private static $cookieName = "";
   //private static $expiryTime = 108000;
    private static $expiryTime = 108000;
    private  static $usernameCookieName = "somethingusername";
    private  static $tokenCookieName = "token";

   public  function clear($cookieName){
        setcookie($cookieName, NULL, -1);
    }
    public function saveToken($token) {
        setcookie(self::$tokenCookieName, $token,self::$expiryTime+time());

        //var_dump($_COOKIE);
        //die();
        return self::$expiryTime+time();
    }
    public function saveUser($user) {
        setcookie(self::$usernameCookieName, $user,self::$expiryTime);

        //var_dump($_COOKIE);
        //die();
    }
    public function GetUserName(){
        if(isset($_COOKIE[$this::$usernameCookieName])){
            return $_COOKIE[$this::$usernameCookieName];
        }
        return false;
    }
    public function GetToken(){
      if(isset($_COOKIE[$this::$tokenCookieName])){
            return $_COOKIE[$this::$tokenCookieName];
        }
        return false;
    }
} 
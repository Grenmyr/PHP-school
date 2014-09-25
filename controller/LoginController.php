<?php
require_once("model/UserModel.php");
require_once("view/LoginView.php");
require_once("Helpers/Helpers.php");
require_once("CookieStorage/Messages.php");
require_once("CookieStorage/CookieStorage.php");

class LoginController{
	private $loginView;
	private $model;
	private $messages;
    private $cookieStorage;

	//Statics so we can change them easier

	private static $postUsernameLocation = "username";
	private static $postPasswordLocation = "password";
	private static $wrongInfoInCookie = "Felaktig information i cookie";
	private static $loginViaCookieSuccess = "Inloggning lyckades via cookies";
	private static $usernameMissing = "Användarnamn saknas";
	private static $passwordMissing = "Lösenord saknas";
	private static $incorrectInfo = "Felaktigt användarnamn och/eller lösenord";
	private static $loggedOut = "Du har nu loggat ut";
	private static $loggedInAndRemember = "Inloggning lyckades och vi kommer ihåg dig nästa gång";
	private static $loggedIn = "Inloggnig lyckades";

    /**
     *
     */
    function __construct(){
		$this->model = new UserModel();
		$this->messages = new Messages();
		$this->loginView = new LoginView($this->model, $this->messages);
        $this->cookieStorage = new CookieStorage();
	}

    /**
     * @return string
     */
    function getHTML(){
		$this->reloadIfDidLogIn();
		return Helpers::getBaseHTML($this->loginView->getHead(), 
									$this->loginView->getBody());
	}

    /**
     *
     */
	//Reload the page if user logged in now
	private function reloadIfDidLogIn(){
		if($this->checkIfUserLoggedIn()){
            $this->model->reloadPage();
		}
	}
	//Do a check on the token and user
	private function canLoginWithCookie($username, $cookie){
		return $this->model->checkToken($username, $cookie);
	}
	//
	private function loginWithCookie($username, $cookie){
		//If the token in the cookie is correct
		if($this->model->checkToken($username, $cookie)){
			$this->model->loginUser($username);
			//Save a new cookie
			$this->saveRememberMeCookie($username);
		} else {
			//Invalid cookie
			$this->messages->save(self::$wrongInfoInCookie);
			$this->clearRememberMeCookies();
            $this->model->reloadPage();
		}
	}
	//clear the content of Username and Token cookies.
	function clearRememberMeCookies(){
        $this->cookieStorage->clear($this->cookieStorage->GetUserName());
        $this->cookieStorage->clear($this->cookieStorage->GetToken());

	}
	//Gets a new token for the user and then save it in both the user and the cookies
	//So that the user can login again
	private function saveRememberMeCookie($username){
		$token = $this->model->getNewToken();

//        $this->cookieStorage->save(self::$usernameCookieName,$username,time()+self::$expiryTime);
//        $this->cookieStorage->save(self::$tokenCookieName,$username,time()+self::$expiryTime);
        $expiryTime = $this->cookieStorage->saveToken($token);
        $this->cookieStorage->saveUser($username);

        //Save the token for the user and also set the expiry time so that you may not cheat
        $this->model->saveToken($username, $token, $expiryTime);
	}
	//Do the most of the checking to see if the user logged in
	//Or logged out
	//Also check if the user wanted to login with cookies
	function checkIfUserLoggedIn(){
		//Begin the checking to see if the user has a remember me cookie
		if(($this->cookieStorage->GetUserName() && !$this->cookieStorage->GetToken())||(($this->cookieStorage->GetToken() )&& !$this->cookieStorage->GetUserName())){
			$this->messages->save(self::$wrongInfoInCookie);
			$this->clearRememberMeCookies($this->cookieStorage->GetUserName());
            //$this->messages->removeContent($_POST["username"]);
			//$this->model->reloadPage();
		}
        $username = $this->cookieStorage->GetUserName();
        $token = $this->cookieStorage->GetToken();
		if(!empty($username) &&
			//and token must be set
			!empty($token) &&
			//and we must not be logged in already, otherwise -> redirect loop
			!$this->model->isUserLoggedIn()){
			//this is really ugly
			if($this->canLoginWithCookie($this->cookieStorage->GetUserName(), $this->cookieStorage->GetToken())){
				$this->loginWithCookie($this->cookieStorage->GetUserName(), $this->cookieStorage->GetToken());
				session_regenerate_id(true);
				$this->messages->save(self::$loginViaCookieSuccess);
				return true;
			} else {
				$this->messages->save(self::$wrongInfoInCookie);
				$this->clearRememberMeCookies();

                //$this->model->reloadPage();
				return false;
			}
		}
		//If the user didn't want to login iwht cookies
		//Then the user didn't login
		if($_SERVER["REQUEST_METHOD"]!=="POST"){
			return false;
		}
		//If logout is set, then the user wants to logout
		if(isset($_POST["logout"]) && $this->model->isUserLoggedIn()){
			$this->logout();	
		}
		
		//If username is missing from the post we may not login
		if(empty($_POST[self::$postUsernameLocation])){
			$this->messages->save(self::$usernameMissing);
            //$this->model->reloadPage();
			return false;
		}
		//Otherwise we got something in here
		//$postUsername = $_POST["username"];

		//If password is missing from the post we may absolutely not login
		if(empty($_POST[self::$postPasswordLocation])){
			//but save the postUsername so we can use it in the view
			$_SESSION["username"] = $_POST[self::$postUsernameLocation];
			$this->messages->save(self::$passwordMissing);

            $this->model->reloadPage();
			return false;
		}
		//Otherwise we got something in here aswell
		//$postPassword = $_POST["password"];

		//This is also really ugly with 2 nested ifs
		if($this->model->userExists($_POST[self::$postUsernameLocation],$_POST[self::$postPasswordLocation])){
			$this->login($_POST[self::$postUsernameLocation]);	
			return true;
		}
		//If we haven't logged in yet, and not returned yet
		//The username or password is incorrect and as such, we did not login
		$_SESSION["username"] = $_POST[self::$postUsernameLocation];
		$this->messages->save(self::$incorrectInfo);
        $this->model->reloadPage();
		return false;
	}
	//Logout the user, remove the cookies and the reload
	function logout(){
		session_regenerate_id(true);
		$this->model->logoutUser();
		$this->messages->save(self::$loggedOut);
		$this->clearRememberMeCookies();
        $this->model->reloadPage();
	}
	//Login the user
	//Might save cookie if the user wants to be remembered
	function login(){
		$this->model->loginUser($_POST[self::$postUsernameLocation]);	
		session_regenerate_id(true);
		if(!empty($_POST["rememberme"])){
			$this->saveRememberMeCookie($_POST[self::$postUsernameLocation]);
			$this->messages->save(self::$loggedInAndRemember);
			//return true;
		} else {
			$this->messages->save(self::$loggedIn);
			//return true;
		}
	}
}
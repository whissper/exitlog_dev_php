<?php

namespace utils;

use dbcengine\DBEngine;

/**
 * WorkspaceKeeper class
 */
class WorkspaceKeeper {

    //constructor
    function __construct() {
        
    }
    
    /**
     * get GRecaptcha verified answer
     * @param String $grrVal
     * @return boolean
     */
    private function isGRecaptchaValid($grrVal) {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        
        $params = array(
            'secret' => '***',
            'response' => $grrVal,
        );
        
        $proxyAuth = base64_encode('***:***');//A PROXY LIKE THIS
        
        $result = file_get_contents($url, false, stream_context_create(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
            'http' => array(
                'proxy' => 'tcp://proxy.com:8080',//A PROXY LIKE THIS
                'request_fulluri' => true,//A PROXY LIKE THIS
                'header' => array('Proxy-Authorization: Basic ' . $proxyAuth . '\r\n', 'Content-type: application/x-www-form-urlencoded'),//A PROXY LIKE THIS
                'method'  => 'POST',
                'content' => http_build_query($params),
            ),
        )));
        
        $answerData = json_decode($result);
        
        return $answerData->{'success'};
    }
    
    /**
     * check for user's locked state
     * @param int $id
     * @return boolean
     */
    private function isUserLocked($id) {
        $dbEngine = new DBEngine();
        $userDataJSON = $dbEngine->selectDataByID('select_user_by_id', $id);
        $userData = json_decode($userDataJSON);
        if( intval($userData->{'fields'}->{'lockedUserUpd'}) == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * check if user signs in for the first time
     * @param int $id
     * @return boolean
     */
    private function hasUserGotFirstlogin($id) {
        $dbEngine = new DBEngine();
        $userDataJSON = $dbEngine->selectDataByID('select_user_by_id', $id);
        $userData = json_decode($userDataJSON);
        if( intval($userData->{'fields'}->{'firstloginUserUpd'}) == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * do Login
     * @param type $login
     * @param type $password
     * @return JSON string
     */
    public function doLogin($login, $password, $grecaptcha) {
        $dbEngine = new DBEngine();
        $userData = $dbEngine->selectLoginData($login, $password);

        if ($userData['error'] != '') {
            session_destroy();
            return $userData['error'];
        }
        
        //if GRecaptcha verified answer = false
        /* @since 23.04.2018
        if( !$this->isGRecaptchaValid($grecaptcha) ){
            session_destroy();
            return '{ "isvalid" : false, "userid" : "", "userrole" : "" }';
        }
        */
        
        if ($userData['id'] != '' && $userData['role'] != '') {
            $_SESSION['exit_usr_id'] = $userData['id'];
            $_SESSION['exit_usr_role'] = $userData['role'];
            $_SESSION['exit_usr_fio'] = $userData['fio']; //need for display current logged user's fio
            $_SESSION['exit_usr_depid'] = $userData['department_id']; //need for display current logged user's department_id
            $_SESSION['exit_usr_depname'] = $userData['department_name']; //need for display current logged user's department_name
            return '{ "isvalid" : true, "userid" : "' . $_SESSION['exit_usr_id'] . '", "userrole" : "' . $_SESSION['exit_usr_role'] . '" }';
        } else {
            session_destroy();
            return '{ "isvalid" : false, "userid" : "", "userrole" : "" }';
        }
    }

    /**
     * do Logout
     */
    public function doLogout() {
        session_destroy();
    }

    /**
     * get type of loading workspace (session values check with passed parameters)
     * @param type $userID
     * @param type $userRole
     * @return string
     */
    public function loadWorkspace($userID, $userRole) {
        if (isset($_SESSION['exit_usr_id']) &&
            isset($_SESSION['exit_usr_role']) &&
            intval($_SESSION['exit_usr_id']) == intval($userID) &&
            intval($_SESSION['exit_usr_role']) == intval($userRole)) {
            
            //is user locked
            if( $this->isUserLocked($_SESSION['exit_usr_id']) ){
                session_destroy();
                return '-1';
            }
            
            //does user sign in for the first time
            if ($this->hasUserGotFirstlogin($_SESSION['exit_usr_id'])) {
                return '-2';
            }
            
            if (intval($_SESSION['exit_usr_role']) == 1) { //main-inspector
                return '1';
            } else if (intval($_SESSION['exit_usr_role']) == 2) { //not used yet	
                return '2';
            } else if (intval($_SESSION['exit_usr_role']) == 3) { //inspector
                return '3';
            }
        } else {
            session_destroy();
            return '0';
        }
    }

    /**
     * get type of loading workspace according current session values (userID and userRole)
     * @return string
     */
    public function keepWorkspace() {
        if (isset($_SESSION['exit_usr_id']) &&
            isset($_SESSION['exit_usr_role'])) {
            
            //is user locked
            if ( $this->isUserLocked($_SESSION['exit_usr_id']) ) {
                session_destroy();
                return '-1';
            }
            
            //does user sign in for the first time
            if ($this->hasUserGotFirstlogin($_SESSION['exit_usr_id'])) {
                return '-2';
            }
            
            if (intval($_SESSION['exit_usr_role']) == 1) { //main-inspector
                return '1';
            } else if (intval($_SESSION['exit_usr_role']) == 2) { //not used yet	
                return '2';
            } else if (intval($_SESSION['exit_usr_role']) == 3) { //inspector
                return '3';
            }
        } else {
            session_destroy();
            return '0';
        }
    }

}

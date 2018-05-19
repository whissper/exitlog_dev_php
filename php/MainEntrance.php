<?php

/**
 *  EXIT LOG back-end
 *  Main Entrance
 *  special "interface" for calling web-application methods 
 *  kinda "Main" class
 * @author SAV2
 * @since 02.03.2018
 * @version 0.5.3
 */
use utils\TemplateProvider;
use utils\WorkspaceKeeper;
use utils\Utils;
use dbcengine\DBEngine;
use ws\exitsReportSOAP;

//class autoloader
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

//start session
session_start();

//Entrance
if (Utils::postValueIsValid(filter_input(INPUT_GET, 'action'))) {
    switch (filter_input(INPUT_GET, 'action')) {
        case 'login':
            if (Utils::postValueIsValid(filter_input(INPUT_POST, 'id')) &&
                    Utils::postValueIsValid(filter_input(INPUT_POST, 'usr')) &&
                    Utils::postValueIsValid(filter_input(INPUT_POST, 'pwd')) &&
                    Utils::postValueIsValid(filter_input(INPUT_POST, 'grr')) &&
                    filter_input(INPUT_POST, 'id') === 'isuservalid') {
                $workspace = new WorkspaceKeeper();
                echo $workspace->doLogin(filter_input(INPUT_POST, 'usr'), filter_input(INPUT_POST, 'pwd'), filter_input(INPUT_POST, 'grr'));
            } else {
                session_destroy();
                echo 'ERROR_POSTDATA_INCORRECT';
            }
            break;
        case 'logout':
            $workspace = new WorkspaceKeeper();
            $workspace->doLogout();
            break;
        case 'load_workspace':
            if (Utils::postValueIsValid(filter_input(INPUT_POST, 'userid')) &&
                    Utils::postValueIsValid(filter_input(INPUT_POST, 'userrole'))) {
                $workspace = new WorkspaceKeeper();
                echo $workspace->loadWorkspace(filter_input(INPUT_POST, 'userid'), filter_input(INPUT_POST, 'userrole'));
            } else {
                echo '0';
            }
            break;
        case 'keep_workspace':
            $workspace = new WorkspaceKeeper();
            echo $workspace->keepWorkspace();
            break;
        case 'draw_panel':
            $templateProvider = new TemplateProvider();
            $templateProvider->set('userfio', isset($_SESSION['exit_usr_fio']) ? $_SESSION['exit_usr_fio'] : '');
            $templateProvider->set('userid', isset($_SESSION['exit_usr_id']) ? $_SESSION['exit_usr_id'] : '');
            $templateProvider->set('departmentid', isset($_SESSION['exit_usr_depid']) ? $_SESSION['exit_usr_depid'] : '');
            $templateProvider->set('departmentname', isset($_SESSION['exit_usr_depname']) ? $_SESSION['exit_usr_depname'] : '');

            if (Utils::checkPermission(1)) {//1 -- for main-inspector usage
                $templateProvider->set('useridsrch', '');
                $templateProvider->set('hide', '');
            } else if (Utils::checkPermission(3)) {//3 -- for inspector usage
                $templateProvider->set('useridsrch', isset($_SESSION['exit_usr_id']) ? $_SESSION['exit_usr_id'] : '');
                $templateProvider->set('hide', 'style="display:none;"');
            }

            echo $templateProvider->loadTemplate(filter_input(INPUT_POST, 'tmplname'));
            break;
        case 'insert_newrecord':
            //1 -- for main-inspector usage | 3 -- for inspector usage
            if (Utils::checkPermission(1) || Utils::checkPermission(3)) {
                $newrecordData = json_decode(filter_input(INPUT_POST, 'newRecordJSON'));

                $dbEngine = new DBEngine();
                echo $dbEngine->insertNewRecord($newrecordData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'select_points':
            $dbEngine = new DBEngine();
            echo $dbEngine->selectPoints();
            break;
        case 'select_exits':
            //1 -- for main-inspector usage | 3 -- for inspector usage
            if (Utils::checkPermission(1) || Utils::checkPermission(3)) {
                $postData = array();
                $postData['page'] = intval(filter_input(INPUT_POST, 'page'));
                $postData['per_page'] = 25;
                $postData['start_position'] = $postData['per_page'] * $postData['page'];

                $postData['date'] = Utils::createRegExp(Utils::dateConvert(filter_input(INPUT_POST, 'date'), Utils::BACK_END), Utils::EQUALS);
                $postData['userfio'] = Utils::createRegExp(filter_input(INPUT_POST, 'userfio'), Utils::STARTS_FROM);
                $postData['userid'] = Utils::createRegExp(filter_input(INPUT_POST, 'userid'), Utils::EQUALS);
                $postData['objectname'] = Utils::createRegExp(filter_input(INPUT_POST, 'objectname'), Utils::CONTAINS);

                $dbEngine = new DBEngine();
                echo $dbEngine->selectData('select_exits', $postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'select_users':
            //1 -- for main-inspector usage
            if (Utils::checkPermission(1)) {
                $postData = array();
                $postData['page'] = intval(filter_input(INPUT_POST, 'page'));
                $postData['per_page'] = 25;
                $postData['start_position'] = $postData['per_page'] * $postData['page'];
                
                $postData['userfio'] = Utils::createRegExp(filter_input(INPUT_POST, 'userfio'), Utils::STARTS_FROM);
                
                $dbEngine = new DBEngine();
                echo $dbEngine->selectData('select_users', $postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'lock_user':
            //1 -- for main-inspector usage
            if (Utils::checkPermission(1)) {
                $postData = array();
                $postData['id'] = intval(filter_input(INPUT_POST, 'id'));

                $dbEngine = new DBEngine();
                echo $dbEngine->uncontrolledChangeData('lock_user', $postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'unlock_user':
            //1 -- for main-inspector usage
            if (Utils::checkPermission(1)) {
                $postData = array();
                $postData['id'] = intval(filter_input(INPUT_POST, 'id'));

                $dbEngine = new DBEngine();
                echo $dbEngine->uncontrolledChangeData('unlock_user', $postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'delete_exit':
            //1 -- for main-inspector usage | 3 -- for inspector usage
            if (Utils::checkPermission(1) || Utils::checkPermission(3)) {
                $postData = array();
                $postData['id'] = intval(filter_input(INPUT_POST, 'id'));

                $dbEngine = new DBEngine();
                echo $dbEngine->changeData('delete_exit', $postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'update_exit':
            //1 -- for main-inspector usage | 3 -- for inspector usage
            if (Utils::checkPermission(1) || Utils::checkPermission(3)) {
                $postData = array();
                $postData['id'] = intval(filter_input(INPUT_POST, 'id'));
                $postData['objects'] = filter_input(INPUT_POST, 'objects');

                $dbEngine = new DBEngine();
                $dbEngine->changeData('delete_objects_by_exitid', $postData);
                echo $dbEngine->insertObjects($postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'select_exit_by_id':
            //1 -- for main-inspector usage | 3 -- for inspector usage
            if (Utils::checkPermission(1) || Utils::checkPermission(3)) {             
                $dbEngine = new DBEngine();
                echo $dbEngine->selectDataByID('select_exit_by_id', intval(filter_input(INPUT_POST, 'id')));
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'update_user':
            //1 -- for main-inspector usage | 3 -- for inspector usage
            if (Utils::checkPermission(1) || Utils::checkPermission(3)) { 
                $postData = array();
                $postData['id'] = intval(filter_input(INPUT_POST, 'id'));
                $postData['fio'] = filter_input(INPUT_POST, 'fio');
                $postData['pass'] = filter_input(INPUT_POST, 'pass');
                $postData['firstlogin'] = intval(filter_input(INPUT_POST, 'firstlogin'));
                
                $dbEngine = new DBEngine();
                echo $dbEngine->uncontrolledChangeData('update_user', $postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'select_user_by_id':
            //1 -- for main-inspector usage
            if (Utils::checkPermission(1)) {
                $dbEngine = new DBEngine();
                echo $dbEngine->selectDataByID('select_user_by_id', intval(filter_input(INPUT_POST, 'id')));
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
        case 'exitsReportSOAP':
            //1 -- for main-inspector usage
            if (Utils::checkPermission(1)) {             
                $postData = array();
                $postData['startDate'] = filter_input(INPUT_POST, 'startDate');
                $postData['endDate']   = filter_input(INPUT_POST, 'endDate');

                echo exitsReportSOAP::writeDataIntoXLSX($postData);
            } else {
                echo 'ERROR_ACCESS_DENIED';
            }
            break;
    }
}

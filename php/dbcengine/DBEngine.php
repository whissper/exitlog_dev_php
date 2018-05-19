<?php

namespace dbcengine;

use PDO;
use PDOException;
use utils\Utils;

/**
 * DBEngine class
 */
class DBEngine {

    private $pdo;

    //constructor
    function __construct() {
        
    }

    //create PDO object
    private function createPDO() {
        $dsn = 'mysql:host=' . DBconf::DB_SERVER . ';dbname=' . DBconf::DB_NAME . ';charset=' . DBconf::CHARSET;

        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO($dsn, DBconf::DB_USER, DBconf::DB_PASS, $opt);
    }

    //destroy PDO object
    private function destroyPDO() {
        $this->pdo = null;
    }

    /**
     * select Login data
     * @param type $login
     * @param type $password
     * @return JSON string
     */
    public function selectLoginData($login, $password) {
        $params = array();
        $params[] = new BoundParameter(':login', $login, PDO::PARAM_STR);
        //$params[] = new BoundParameter(':password', $password, PDO::PARAM_STR);

        $rows = array();
        $rows['error'] = '';
        $rows['id'] = '';
        $rows['role'] = '';
        $rows['fio'] = '';

        try {
            $this->createPDO();

            $queryEngine = new QueryEngine($this->pdo);

            $queryString = 'SELECT `users`.`id`,
                                   `users`.`fio`,
                                   `users`.`login`,
                                   `users`.`pass`,
                                   `users`.`role`,
                                   `users`.`department_id` AS depid, 
                                   `departments`.`name` AS depname
                            FROM `users`
                            LEFT JOIN `departments` ON `departments`.`id` = `users`.`department_id` 
                            WHERE `users`.`login` = :login  
                            ORDER BY `users`.`id` DESC';

            $resultSet = $queryEngine->getResultSet($queryString, $params);

            while ($row = $resultSet->fetch(PDO::FETCH_LAZY)) {
                if( password_verify($password, $row->pass) ) {
                    $rows['id'] = $row->id;
                    $rows['role'] = $row->role;
                    $rows['fio'] = $row->fio;
                    $rows['department_id'] = $row->depid;
                    $rows['department_name'] = $row->depname;
                }
            }
        } catch (PDOException $e) {
            $rows['error'] = 'ERROR_PDO|' . $e->getMessage();
        }

        $this->destroyPDO();

        return $rows;
    }

    /**
     * Select Points to form select-list component in HTML
     */
    public function selectPoints() {
        $resultString = '';
        $params = array();

        $resultString .= '{ "points" : [ ';

        try {
            $this->createPDO();

            $queryEngine = new QueryEngine($this->pdo);

            $queryString = 'SELECT * FROM `points` WHERE `points`.`id` <> 5';//skip if point is "Другое:"

            $resultSet = $queryEngine->getResultSet($queryString, $params);

            while ($row = $resultSet->fetch(PDO::FETCH_LAZY)) {
                $resultString .= '["' . $row['id'] . '", "' . $row['name'] . '"], ';
            }
            
            $resultString .= '[] ] }';
        } catch (PDOException $e) {
            $resultString = 'ERROR_PDO|' . $e->getMessage();
        }

        $this->destroyPDO();

        return $resultString;
    }

    /**
     * SELECT some data
     * @param type $queryName
     * @param type $postData
     * @return JSON string
     */
    public function selectData($queryName, $postData) {
        $resultString = '';

        $queryStringCount = '';
        $queryString = '';
        $params = array();
        $dataColumns = array();
        $count_rows = '0';

        switch ($queryName) {
            case 'select_exits':
                $queryStringCount = 'SELECT COUNT(`exits`.`id`) AS "countrows" 
                                    FROM `exits` 
                                    LEFT JOIN `users` ON `users`.`id`=`exits`.`user_id` 
                                    WHERE `exits`.`deleted` = :deleted AND 
                                          `exits`.`date` REGEXP :date AND 
                                          `users`.`department_id` = :departmentid AND 
                                          `users`.`fio` REGEXP :userfio AND 
                                          `users`.`id` REGEXP :userid ';
				if ($postData['objectname'] !== '^.*$') {
					$queryStringCount .=
                                          ' AND `exits`.`id` IN (SELECT DISTINCT `objects`.`exit_id` 
																 FROM `objects` 
																 WHERE `objects`.`name` REGEXP :objectname OR
																 `objects`.`name` IS NULL)';
				}

                $queryString = 'SELECT `exits`.`id`,
                                        DAYOFWEEK(`exits`.`date`) AS "dayofweek",
                                       `exits`.`date`, 
                                       `users`.`fio`, 
                                       `points`.`name` AS "point", 
                                       `exits`.`point_description`, 
                                       `exits`.`time_exit`, 
                                       `exits`.`time_return`, 
                                        TIMEDIFF(`exits`.`time_return`, `exits`.`time_exit`) AS "hours"
                                FROM `exits`
                                LEFT JOIN `users` ON `users`.`id`=`exits`.`user_id` 
                                LEFT JOIN `points` ON `points`.`id` = `exits`.`point_id` 
                                WHERE `exits`.`deleted` = :deleted AND 
                                      `exits`.`date` REGEXP :date AND
                                      `users`.`department_id` = :departmentid AND 
                                      `users`.`fio` REGEXP :userfio AND
                                      `users`.`id` REGEXP :userid ';
				if ($postData['objectname'] !== '^.*$') {
					$queryString .=
                                      'AND `exits`.`id` IN (SELECT DISTINCT `objects`.`exit_id` 
                                                       FROM `objects` 
                                                       WHERE `objects`.`name` REGEXP :objectname OR
                                                             `objects`.`name` IS NULL) ';
				}
					$queryString .=
                                'ORDER BY `exits`.`id` DESC 
                                LIMIT ' . $postData['start_position'] . ', ' . $postData['per_page'];

                $params[] = new BoundParameter(':deleted', 0, PDO::PARAM_INT);
                $params[] = new BoundParameter(':date', $postData['date'], PDO::PARAM_STR);
                $params[] = new BoundParameter(':departmentid', $_SESSION['exit_usr_depid'], PDO::PARAM_INT);
                $params[] = new BoundParameter(':userfio', $postData['userfio'], PDO::PARAM_STR);
                $params[] = new BoundParameter(':userid', $postData['userid'], PDO::PARAM_STR);
				if ($postData['objectname'] !== '^.*$') {
					$params[] = new BoundParameter(':objectname', $postData['objectname'], PDO::PARAM_STR);
				}

                $dataColumns[] = 'id';
                $dataColumns[] = 'dayofweek';
                $dataColumns[] = 'date';
                $dataColumns[] = 'fio';
                $dataColumns[] = 'name';
                $dataColumns[] = 'point';
                $dataColumns[] = 'point_description';
                $dataColumns[] = 'time_exit';
                $dataColumns[] = 'time_return';
                $dataColumns[] = 'hours';
                break;
            case 'select_users':
                $queryStringCount = 'SELECT COUNT(`users`.`id`) AS "countrows"
                                     FROM `users`
                                     WHERE `users`.`department_id` = :departmentid AND  
                                           `users`.`fio` REGEXP :userfio';
                
                $queryString = 'SELECT `users`.`id`, `users`.`fio`, `users`.`locked`
                                FROM `users`
                                WHERE `users`.`department_id` = :departmentid AND 
                                      `users`.`fio` REGEXP :userfio
                                ORDER BY `users`.`fio` ASC
                                LIMIT ' . $postData['start_position'] . ', ' . $postData['per_page'];
                
                $params[] = new BoundParameter(':departmentid', $_SESSION['exit_usr_depid'], PDO::PARAM_INT);
                $params[] = new BoundParameter(':userfio', $postData['userfio'], PDO::PARAM_STR);
                
                $dataColumns[] = 'id';
                $dataColumns[] = 'locked';
                $dataColumns[] = 'fio';
                break;
        }

        try {
            $this->createPDO();
            $queryEngine = new QueryEngine($this->pdo);
            $resultSet = $queryEngine->getResultSet($queryStringCount, $params);
            while ($row = $resultSet->fetch(PDO::FETCH_LAZY)) {
                $count_rows = $row->countrows;
            }
            $resultString = '{ "countrows" : "' . $count_rows . '", ';
            $resultString .= '"page" : "' . $postData['page'] . '", ';
            $resultString .= '"perpage" : "' . $postData['per_page'] . '", ';
            $resultString .= '"rowitems" : [ ';

            $resultSet = $queryEngine->getResultSet($queryString, $params);

            $dataRows = $resultSet->fetchAll();

            foreach ($dataRows as $row) {
                $resultString .= '["';
                
                $count = count($dataColumns);
                foreach ($dataColumns as $columnName) {
                    $count--;
                    if ($count != 0) {
                        if ($columnName === 'dayofweek') {
                            $resultString .= Utils::json_string_encode(Utils::getWeekDay($row[$columnName])) . '", "';
                        } else if ($columnName === 'date') {
                            $resultString .= Utils::json_string_encode(Utils::dateConvert($row[$columnName], Utils::FRONT_END)) . '", "';
                        } else if ($columnName === 'fio') {
                            $resultString .= Utils::json_string_encode($row[$columnName]) . '", ';
                        } else if ($columnName === 'name') {
                            $objectsString = '';

                            $queryObjects = 'SELECT `objects`.`name`,
                                                    `objects`.`note`,
                                                    `objects`.`postal_index`,
                                                    `objects`.`region`,
                                                    `objects`.`town`,
                                                    `objects`.`street`,
                                                    `objects`.`building`,
                                                    `objects`.`apartment`,
                                                    `objects`.`geo_lat`,
                                                    `objects`.`geo_lon`,
                                                    `objects`.`old_format`
                                            FROM `objects` 
                                            WHERE `objects`.`exit_id` = :exit_id';

                            $paramsObjects = array();
                            $paramsObjects[] = new BoundParameter(':exit_id', $row['id'], PDO::PARAM_INT);

                            $objectsSet = $queryEngine->getResultSet($queryObjects, $paramsObjects);

                            $objectsRows = $objectsSet->fetchAll();

                            foreach ($objectsRows as $objectRow) {
                                if (intval($objectRow['old_format'])==1) {
                                    $objectNameStr = str_replace('г Сыктывкар, ', '', $objectRow['name']);
                                    $objectNameStr = '- ' . str_replace('г. Сыктывкар, ', '', $objectNameStr);
                                } else {
                                    $objectNameStr = '- ' . $objectRow['street'] .', '. $objectRow['building'];
                                    if($objectRow['apartment'] != null || $objectRow['apartment'] != '') {
                                       $objectNameStr .= ', '. $objectRow['apartment'];
                                    }
                                }
                                $objectsString .= '["' . Utils::json_string_encode($objectNameStr) . '", "' . Utils::json_string_encode($objectRow['note']) . '"], ';
                            }

                            $resultString .= '[' . substr($objectsString, 0, -2) . '], "';
                        } else if ($columnName === 'time_exit' || $columnName === 'time_return') {
                            $resultString .= Utils::json_string_encode(substr($row[$columnName], 0, -3)) . '", "';
                        } else {
                            $resultString .= Utils::json_string_encode($row[$columnName]) . '", "';
                        }
                    } else {
                        if ($columnName === 'hours') {
                            $resultString .= Utils::json_string_encode(substr($row[$columnName], 0, -3));
                        } else {
                            $resultString .= Utils::json_string_encode($row[$columnName]);
                        }
                    }
                }

                $resultString .= '"],';
            }

            $resultString .= '[] ] }';
        } catch (PDOException $e) {
            $resultString = 'ERROR_PDO|' . $e->getMessage();
        }
        $this->destroyPDO();
        return $resultString;
    }

    /**
     * SELECT some data BY ID
     * @param type $queryName
     * @param type $id
     * @return JSON string
     */
    public function selectDataByID($queryName, $id) {
        $resultString = '';
        $queryString = '';
        $entityName = '';
        $propertyNames = array();
        $dataColumns = array();

        $params = array();
        $params[] = new BoundParameter(':id', $id, PDO::PARAM_INT);

        switch ($queryName) {
            case 'select_exit_by_id':
                $queryString = 'SELECT `exits`.`id`,
                                       `exits`.`date`, 
                                       `users`.`fio`, 
                                       `points`.`id` AS "point", 
                                       `exits`.`point_description`, 
                                       `exits`.`time_exit`, 
                                       `exits`.`time_return`, 
                                       `objects`.`name` AS "objectname",
                                       `objects`.`note` AS "objectnote",
                                       `objects`.`postal_index` AS "objectpostalindex",
                                       `objects`.`region` AS "objectregion",
                                       `objects`.`town` AS "objecttown",
                                       `objects`.`street` AS "objectstreet",
                                       `objects`.`building` AS "objectbuilding",
                                       `objects`.`apartment` AS "objectapartment",
                                       `objects`.`geo_lat` AS "objectgeolat",
                                       `objects`.`geo_lon` AS "objectgeolon",
                                       `objects`.`old_format` AS "objectoldformat"
                                FROM `exits`
                                LEFT JOIN `users` ON `users`.`id`=`exits`.`user_id` 
                                LEFT JOIN `points` ON `points`.`id` = `exits`.`point_id`
                                LEFT JOIN `objects` ON `objects`.`exit_id` = `exits`.`id`
                                WHERE `exits`.`id` = :id';
                
                $entityName = 'exit';
                
                $propertyNames[] = 'idUpd';
                $propertyNames[] = 'fioExitUpd';
                $propertyNames[] = 'dateExitUpd';
                $propertyNames[] = 'timeexitExitUpd';
                $propertyNames[] = 'timereturnExitUpd';
                $propertyNames[] = 'pointExitUpd';
                $propertyNames[] = 'pointDescriptionExitUpd';
                $propertyNames[] = 'objectsExitUpd';
                
                $dataColumns[] = 'id';
                $dataColumns[] = 'fio';
                $dataColumns[] = 'date';
                $dataColumns[] = 'time_exit';
                $dataColumns[] = 'time_return';
                $dataColumns[] = 'point';
                $dataColumns[] = 'point_description';
                $dataColumns[] = 'objectname';
                $dataColumns[] = 'objectnote';
                $dataColumns[] = 'objectpostalindex';
                $dataColumns[] = 'objectregion';
                $dataColumns[] = 'objecttown';
                $dataColumns[] = 'objectstreet';
                $dataColumns[] = 'objectbuilding';
                $dataColumns[] = 'objectapartment';
                $dataColumns[] = 'objectgeolat';
                $dataColumns[] = 'objectgeolon';
                $dataColumns[] = 'objectoldformat';
                break;
            case 'select_user_by_id':
                $queryString = 'SELECT `users`.`id`,
                                       `users`.`fio`,
                                       `users`.`login`,
                                       `users`.`locked`,
                                       `users`.`first_login` AS "firstlogin",
                                       `departments`.`name` AS "departmentname"
                                FROM `users`
                                LEFT JOIN `departments` ON `departments`.`id`=`users`.`department_id` 
                                WHERE `users`.`id` = :id';

                $entityName = 'user';
                
                $propertyNames[] = 'idUpd';
                $propertyNames[] = 'fioUserUpd';
                $propertyNames[] = 'loginUserUpd';
                $propertyNames[] = 'lockedUserUpd';
                $propertyNames[] = 'firstloginUserUpd';
                $propertyNames[] = 'departmentnameUserUpd';
                
                $dataColumns[] = 'id';
                $dataColumns[] = 'fio';
                $dataColumns[] = 'login';
                $dataColumns[] = 'locked';
                $dataColumns[] = 'firstlogin';
                $dataColumns[] = 'departmentname';
                break;
        }

        try {
            $this->createPDO();
            $queryEngine = new QueryEngine($this->pdo);
            $resultSet = $queryEngine->getResultSet($queryString, $params);

            $resultString = '{ ';
            $resultString .= '"entity" : "' . $entityName . '", ';
            
            switch ($entityName) {
                case 'exit':
                    $isFirstRow = true;
                    $resultString .= '"fields" : { ';
                    
                    while ($row = $resultSet->fetch(PDO::FETCH_LAZY)) {
                        
                        if($isFirstRow) {
                            $isFirstRow = false;
                            $count = count($dataColumns);
                            $objdata_start = 8;
                            for ($i = 1; $i <= $count; $i++) {
                                if ($i < $objdata_start) {
                                    switch ($dataColumns[$i - 1]){
                                        case 'date':
                                            $resultString .= '"' . $propertyNames[$i - 1] . '" : "' . Utils::json_string_encode( Utils::dateConvert($row[$dataColumns[$i - 1]], Utils::FRONT_END) ) . '", ';
                                            break;
                                        case 'time_exit':
                                            $resultString .= '"' . $propertyNames[$i - 1] . '" : "' . Utils::json_string_encode( substr($row[$dataColumns[$i - 1]], 0, -3) ) . '", ';
                                            break;
                                        case 'time_return':
                                            $resultString .= '"' . $propertyNames[$i - 1] . '" : "' . Utils::json_string_encode( substr($row[$dataColumns[$i - 1]], 0, -3) ) . '", ';
                                            break;
                                        default:
                                            $resultString .= '"' . $propertyNames[$i - 1] . '" : "' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '", ';
                                            break;
                                    }    
                                } else if ($i == $objdata_start) {
                                    $resultString .= '"' . $propertyNames[$i - 1] . '" : [ ["' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '", ';
                                } else if ($i > $objdata_start && $i < $count) {
                                    $resultString .= '"' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '", ';
                                } else if ($i == $count) {
                                    $resultString .= '"' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '"], ';
                                }
                            }
                        } else {
                            $count = count($dataColumns);
                            $objdata_start = 8;
                            for ($i = 1; $i <= $count; $i++) {
                                if ($i < $objdata_start) {
                                    //do nothing
                                } else if ($i == $objdata_start) { 
                                    $resultString .= '["' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '", ';
                                } else if ($i > $objdata_start && $i < $count) {
                                    $resultString .= '"' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '", ';
                                } else if ($i == $count) {
                                    $resultString .= '"' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '"], ';
                                }
                            }
                        }
                    }
                    
                    $resultString = substr($resultString, 0, -2);
                    $resultString .= ' ] }';
                    break;
                default:
                    while ($row = $resultSet->fetch(PDO::FETCH_LAZY)) {
                        $resultString .= '"fields" : { ';

                        $count = count($dataColumns);
                        for ($i = 1; $i <= $count; $i++) {
                            if ($i != $count) {
                                $resultString .= '"' . $propertyNames[$i - 1] . '" : "' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '", ';
                            } else {
                                $resultString .= '"' . $propertyNames[$i - 1] . '" : "' . Utils::json_string_encode($row[$dataColumns[$i - 1]]) . '"';
                            }
                        }

                        $resultString .= ' }';
                    }
                    break;
            }

            $resultString .= ' }';
        } catch (PDOException $e) {
            $resultString = 'ERROR_PDO|' . $e->getMessage();
        }
        $this->destroyPDO();
        return $resultString;
    }

    /**
     * INSERT new record (contract->heated_object->device)
     * @param type $newrecordData
     * @return String
     */
    public function insertNewRecord($newrecordData) {
        $resultString = '';

        // if (time_return - time_exit) <= 0
        if (strtotime($newrecordData->{'time_return'}) - strtotime($newrecordData->{'time_exit'}) <= 0) {
            return 'ERROR_TIME|Время возвращения должно быть позднее времени выхода';
        }

        try {
            $this->createPDO();
            $queryEngine = new QueryEngine($this->pdo);

            /** EXIT * */
            $queryString = 'INSERT INTO `exits` (`date`, `user_id`, `point_id`, `point_description`, `time_exit`, `time_return`) 
                            VALUES (:date, :user_id, :point_id, :point_description, :time_exit, :time_return)';
            $params = array();
            $params[] = new BoundParameter(':date', date('Y-m-d'), PDO::PARAM_STR);
            $params[] = new BoundParameter(':user_id', Utils::formatValue($newrecordData->{'userid'}), PDO::PARAM_INT);
            $params[] = new BoundParameter(':point_id', Utils::formatValue($newrecordData->{'pointid'}), PDO::PARAM_STR);
            $params[] = new BoundParameter(':point_description', Utils::formatValue($newrecordData->{'point_description'}), PDO::PARAM_STR);
            $params[] = new BoundParameter(':time_exit', Utils::formatValue($newrecordData->{'time_exit'}), PDO::PARAM_STR);
            $params[] = new BoundParameter(':time_return', Utils::formatValue($newrecordData->{'time_return'}), PDO::PARAM_STR);

            $exit_id = $queryEngine->getLastInsertId($queryString, $params);

            /** EXIT OBJECTS * */
            foreach ($newrecordData->{'objects'} as $value) {
                $queryString = 'INSERT INTO `objects` (`name`, `exit_id`, `note`, `postal_index`, `region`, `town`, `street`, `building`, `apartment`, `geo_lat`, `geo_lon`, `old_format`) 
                                VALUES (:name, :exit_id, :note, :postal_index, :region, :town, :street, :building, :apartment, :geo_lat, :geo_lon, :old_format)';
                $params = array();
                $params[] = new BoundParameter(':name', Utils::formatValue($value[0]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':exit_id', $exit_id, PDO::PARAM_INT);
                $params[] = new BoundParameter(':note', Utils::formatValue($value[1]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':postal_index', Utils::formatValue($value[2]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':region', Utils::formatValue($value[3]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':town', Utils::formatValue($value[4]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':street', Utils::formatValue($value[5]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':building', Utils::formatValue($value[6]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':apartment', Utils::formatValue($value[7]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':geo_lat', Utils::formatValue($value[8]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':geo_lon', Utils::formatValue($value[9]), PDO::PARAM_STR);
                $params[] = new BoundParameter(':old_format', $value[10], PDO::PARAM_INT);

                $exit_object_id = $queryEngine->getLastInsertId($queryString, $params);
            }

            $resultString = 'Запись успешно занесена в журнал.';
        } catch (PDOException $e) {
            $resultString = 'ERROR_PDO|' . $e->getMessage();
        }
        $this->destroyPDO();
        return $resultString;
    }
    
    /**
     * INSERT objects (array of objects)
     * @param type $postData
     * @return String
     */
    public function insertObjects($postData) {
        $resultString = '';
        
        if ($this->changeIsPossible($postData['id'])) {
            try {
                $this->createPDO();
                $queryEngine = new QueryEngine($this->pdo);
                
                $objectsJSON = json_decode($postData['objects']);
                
                foreach ($objectsJSON->{'objects'} as $value) {
                    $queryString = 'INSERT INTO `objects` (`name`, `exit_id`, `note`, `postal_index`, `region`, `town`, `street`, `building`, `apartment`, `geo_lat`, `geo_lon`, `old_format`) 
                                    VALUES (:name, :exit_id, :note, :postal_index, :region, :town, :street, :building, :apartment, :geo_lat, :geo_lon, :old_format)';
                    $params = array();
                    $params[] = new BoundParameter(':name', Utils::formatValue($value[0]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':exit_id', $postData['id'], PDO::PARAM_INT);
                    $params[] = new BoundParameter(':note', Utils::formatValue($value[1]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':postal_index', Utils::formatValue($value[2]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':region', Utils::formatValue($value[3]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':town', Utils::formatValue($value[4]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':street', Utils::formatValue($value[5]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':building', Utils::formatValue($value[6]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':apartment', Utils::formatValue($value[7]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':geo_lat', Utils::formatValue($value[8]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':geo_lon', Utils::formatValue($value[9]), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':old_format', $value[10], PDO::PARAM_INT);

                    $exit_object_id = $queryEngine->getLastInsertId($queryString, $params);
                }
                
                $resultString = "Данные по выходу: ". $postData['id'] ." успешно изменены";
                    
            } catch (PDOException $e) {
                $resultString = 'ERROR_PDO|' . $e->getMessage();
            }
        } else {
            $resultString = 'CHANGE_IMPOSSIBLE';
        }
        
        $this->destroyPDO();
        return $resultString;
    }
    
    /**
     * INSERT
     * @param type $queryName
     * @param type $postData
     * @return String
     */
    public function insertData($queryName, $postData) {
        $resultString = '';

        $queryString = '';
        $params = array();

        switch ($queryName) {
            /*
              case '':
              $queryString = ;

              $params[] = new BoundParameter(':', $postData[''], PDO::PARAM_STR);
              $params[] = new BoundParameter(':', $postData[''], PDO::PARAM_INT);

              $resultString = ;
              break;
             */
        }

        try {
            $this->createPDO();
            $queryEngine = new QueryEngine($this->pdo);
            $lastInsertID = $queryEngine->getLastInsertId($queryString, $params);
        } catch (PDOException $e) {
            $resultString = 'ERROR_PDO|' . $e->getMessage();
        }
        $this->destroyPDO();
        return $resultString;
    }

    /**
     * UPDATE or DELETE
     * @param type $queryName
     * @param type $postData
     * @return String
     */
    public function changeData($queryName, $postData) {
        $resultString = '';

        $queryString = '';
        $params = array();
        $changedRowID = -1;

        switch ($queryName) {
            case 'delete_exit':
                $queryString = 'UPDATE `exits` 
                                SET `deleted` = 1 
                                WHERE `exits`.`id` = :id';

                $params[] = new BoundParameter(':id', $postData['id'], PDO::PARAM_INT);

                $resultString = 'Запись под номером id: <b>' . $postData['id'] . '</b> успешно удалена.';
                $changedRowID = $postData['id'];
                break;
            case 'delete_objects_by_exitid':
                $queryString = 'DELETE `objects` 
                                FROM `objects` 
                                LEFT JOIN `exits` ON `exits`.`id` = `objects`.`exit_id` 
                                WHERE `exits`.`id` = :id';

                $params[] = new BoundParameter(':id', $postData['id'], PDO::PARAM_INT);

                $resultString = 'Объекты выхода под номером id: <b>' . $postData['id'] . '</b> успешно удалены.';
                $changedRowID = $postData['id'];
                break;
        }

        if ($this->changeIsPossible($changedRowID)) {
            try {
                $this->createPDO();
                $queryEngine = new QueryEngine($this->pdo);
                $queryEngine->executeQuery($queryString, $params);
            } catch (PDOException $e) {
                $resultString = 'ERROR_PDO|' . $e->getMessage();
            }
        } else {
            $resultString = 'CHANGE_IMPOSSIBLE';
        }
        $this->destroyPDO();
        return $resultString;
    }
    
    /**
     * UPDATE or DELETE
     * w\o checking current timestamp
     * @param type $queryName
     * @param type $postData
     * @return String
     */
    public function uncontrolledChangeData($queryName, $postData) {
        $resultString = '';

        $queryString = '';
        $params = array();
        $changedRowID = -1;
        
        switch ($queryName) {
            case 'lock_user':
                $queryString = 'UPDATE `users` 
                                SET `locked` = 1 
                                WHERE `users`.`id` = :id';

                $params[] = new BoundParameter(':id', $postData['id'], PDO::PARAM_INT);

                $resultString = 'Пользователь с id: <b>' . $postData['id'] . '</b> заблокирован.';
                $changedRowID = $postData['id'];
                break;
            case 'unlock_user':
                $queryString = 'UPDATE `users` 
                                SET `locked` = 0 
                                WHERE `users`.`id` = :id';

                $params[] = new BoundParameter(':id', $postData['id'], PDO::PARAM_INT);

                $resultString = 'Пользователь с id: <b>' . $postData['id'] . '</b> разблокирован.';
                $changedRowID = $postData['id'];
                break;
            case 'update_user':
                //if pass field is empty then don't change the password
                if ($postData['pass'] === '') {
                    $queryString = 'UPDATE `users` 
                                    SET `fio` = :fio, `first_login` = :firstlogin  
                                    WHERE `users`.`id` = :id';

                    $params[] = new BoundParameter(':fio', $postData['fio'], PDO::PARAM_STR);
                    $params[] = new BoundParameter(':firstlogin', $postData['firstlogin'], PDO::PARAM_INT);
                    $params[] = new BoundParameter(':id', $postData['id'], PDO::PARAM_INT);
                //otherwise change the password as well 
                } else {
                    $queryString = 'UPDATE `users` 
                                    SET `fio` = :fio, `pass` = :pass, `first_login` = :firstlogin  
                                    WHERE `users`.`id` = :id';

                    $params[] = new BoundParameter(':fio', $postData['fio'], PDO::PARAM_STR);
                    $params[] = new BoundParameter(':pass', password_hash($postData['pass'], PASSWORD_BCRYPT), PDO::PARAM_STR);
                    $params[] = new BoundParameter(':firstlogin', $postData['firstlogin'], PDO::PARAM_INT);
                    $params[] = new BoundParameter(':id', $postData['id'], PDO::PARAM_INT);
                }
                                
                $resultString = 'Данные пользователя с id: <b>' . $postData['id'] . '</b> успешно изменены.';
                $changedRowID = $postData['id'];
                break;
        }
        
        //special control for locking\unlocking users
        if ($queryName == "lock_user" || $queryName == "unlock_user") {
            if ($this->changeLockedStateIsPossible($changedRowID)) {
                try {
                    $this->createPDO();
                    $queryEngine = new QueryEngine($this->pdo);
                    $queryEngine->executeQuery($queryString, $params);
                } catch (PDOException $e) {
                    $resultString = 'ERROR_PDO|' . $e->getMessage();
                }
            } else {
                $resultString = 'CHANGE_IMPOSSIBLE';
            }
        //for other "uncontrolled" changes
        } else {
            try {
                $this->createPDO();
                $queryEngine = new QueryEngine($this->pdo);
                $queryEngine->executeQuery($queryString, $params);
            } catch (PDOException $e) {
                $resultString = 'ERROR_PDO|' . $e->getMessage();
            }
        }
        $this->destroyPDO();
        return $resultString;
    }

    /**
     * Check for possibility of incoming change
     * @param int $exitID
     * @return boolean
     */
    private function changeIsPossible($exitID) {
        $isPossible = false;

        $queryString = 'SELECT *   
                        FROM `exits` 
                        WHERE `exits`.`id` = :id';

        $params[] = new BoundParameter(':id', $exitID, PDO::PARAM_INT);

        try {
            $this->createPDO();
            $queryEngine = new QueryEngine($this->pdo);
            $resultSet = $queryEngine->getResultSet($queryString, $params);
            while ($row = $resultSet->fetch(PDO::FETCH_LAZY)) {
                if ($row['user_id'] == $_SESSION['exit_usr_id'] && //if user is trying to change his own recorded data
                        time() <= (strtotime($row['date']) + (60 * 60 * 32))) {//if 32+(time_zone ~ 3 hrs) have not passed yet
                    $isPossible = true;
                }
            }
        } catch (PDOException $e) {
            
        }
        $this->destroyPDO();
        return $isPossible;
    }
    
    /**
     * Check if user is trying to lock yourself OR user is trying to lock user from other department
     * @param int $userID
     * @return boolean
     */
    private function changeLockedStateIsPossible($userID) {
        $isPossible = false;
        
        $queryString = 'SELECT *   
                        FROM `users` 
                        WHERE `users`.`id` = :id';

        $params[] = new BoundParameter(':id', $userID, PDO::PARAM_INT);
        
        try {
            $this->createPDO();
            $queryEngine = new QueryEngine($this->pdo);
            $resultSet = $queryEngine->getResultSet($queryString, $params);
            while ($row = $resultSet->fetch(PDO::FETCH_LAZY)) {
                if( $row['id'] == $_SESSION['exit_usr_id'] || 
                        $row['department_id'] != $_SESSION['exit_usr_depid'] ) {
                    //if user is trying to lock yourself OR user is trying to lock user from other department
                    $isPossible = false;
                } else {
                    $isPossible = true;
                }
            }
        } catch (PDOException $e) {
            
        }
        $this->destroyPDO();
        return $isPossible;
    }

}

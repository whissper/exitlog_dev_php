<?php

namespace ws;

use SoapClient;
use utils\Utils;

/**
 * exitsReportSOAP class
 */
class exitsReportSOAP {
    
    /**
     * get formed XLSX document
     * @param type $postData
     * @return type String (reference to file)
     */
    public static function writeDataIntoXLSX($postData) {
        try {
            $client = new SoapClient("http://kom-ts01-dev01:8080/ExitsReportWS/ExitsReportWS?wsdl");

            if (!isset($postData['startDate'])) {
                $startDate = date('Y-m-d');
            } else {
                $startDate = Utils::dateConvert($postData['startDate'], Utils::BACK_END);
            }

            if (!isset($postData['endDate'])) {
                $endDate = date('Y-m-d');
            } else {
                $endDate = Utils::dateConvert($postData['endDate'], Utils::BACK_END);
            }

            $params = array('startDate' => $startDate, 'endDate' => $endDate, 'depID' => $_SESSION['exit_usr_depid']);

            $result = $client->loadXLSX($params);

            return $result->{'reference'};
        } catch (Exception $ex) {
            return 'ERROR_WS|' . $ex->getMessage();
        }
    }
    
}

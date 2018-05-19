<?php

namespace utils;

use DateTime;

/**
 * class Utils
 */
class Utils {

    const CONTAINS = 0;
    const STARTS_FROM = 1;
    const EQUALS = 2;
    const FRONT_END = 1;
    const BACK_END = 2;

    /**
     * escape string
     * @param type $regexp_str
     * @return type
     */
    public static function escape($regexp_str) {
        //. + ? [ ] ( ) { } = ! < > | : -
        $formed_string = str_replace(
            array('.', '+', '?', '[', ']', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':', '-'), 
            array('\.', '\+', '\?', '\[', '\]', '\(', '\)', '\{', '\}', '\=', '\!', '\<', '\>', '\|', '\:', '\-'), 
            $regexp_str
        );

        return $formed_string;
    }

    /**
     * Check for empty string value
     * @param type $val
     * @return type NULL or "not empty" string
     */
    public static function formatValue($val) {
        $check_val = trim($val);
        if ($check_val === '') {
            return null;
        } else {
            return $check_val;
        }
    }

    /**
     * Check permission
     * @param type $userRole
     * @return boolean
     */
    public static function checkPermission($userRole) {
        if (isset($_SESSION['exit_usr_id']) &&
                isset($_SESSION['exit_usr_role']) &&
                intval($_SESSION['exit_usr_role']) == $userRole) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Escape special chars
     * @param type $str
     * @return type String - JSON encoded string
     */
    public static function json_string_encode($str) {
        $from = array('"');    // Array of values to replace
        $to = array('\\"');    // Array of values to replace with
        // Replace the string passed
        return str_replace($from, $to, $str);
    }

    /**
     * Create regular expression 
     * @param type $str
     * @param type $searchType
     * @return string
     */
    public static function createRegExp($str, $searchType) {
        $regExp = '';

        switch ($searchType) {
            case self::CONTAINS:
                $regExp = '^.*' . self::escape($str) . '.*$';
                if ($regExp === '^.*.*$') {
                    $regExp = '^.*$';
                }
                break;
            case self::STARTS_FROM:
                $regExp = '^' . self::escape($str) . '.*$';
                break;
            case self::EQUALS:
                $regExp = '^' . self::escape($str) . '$';
                if ($regExp === '^$') {
                    $regExp = '^.*$';
                }
                break;
            default:
                $regExp = '^.*' . self::escape($str) . '.*$';
                if ($regExp === '^.*.*$') {
                    $regExp = '^.*$';
                }
                break;
        }

        return $regExp;
    }

    /**
     * for checking return value of filter_input() method
     * @param type $postValue
     * @return boolean
     */
    public static function postValueIsValid($postValue) {
        if ($postValue === false || is_null($postValue)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * convert MySQL DAYOFWEEK() numeric variant into String
     * @param number
     * @return String
     */
    public static function getWeekDay($number) {
        $weekDay = '';

        switch ($number) {
            case '2':
                $weekDay = 'Пн';
                break;
            case '3':
                $weekDay = 'Вт';
                break;
            case '4':
                $weekDay = 'Ср';
                break;
            case '5':
                $weekDay = 'Чт';
                break;
            case '6':
                $weekDay = 'Пт';
                break;
            case '7':
                $weekDay = 'Сб';
                break;
            case '1':
                $weekDay = 'Вс';
                break;
        }

        return $weekDay;
    }

    /**
     * Convert data into string according special format
     * @param string $stringVal
     * @param type $direction
     * @return string
     */
    public static function dateConvert($stringVal, $direction) {
        $DateStr = '';

        if ($stringVal !== '') {
            $date = new DateTime($stringVal);

            switch ($direction) {
                case self::FRONT_END:
                    $DateStr = $date->format('d-m-Y');
                    break;
                case self::BACK_END:
                    $DateStr = $date->format('Y-m-d');
                    break;
                default :
                    $DateStr = $date->format('Y-m-d');
                    break;
            }
        }

        return $DateStr;
    }

}

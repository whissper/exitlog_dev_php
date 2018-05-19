<?php

namespace dbcengine;

use PDO;

/**
 * QueryEngine class
 */
class QueryEngine {

    private $pdo;
    private $statement;
    private $queryString;
    private $params;

    //constructor
    function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * - prepare statement
     * - bind parameters
     * - execute
     */
    private function execute() {
        $this->statement = $this->pdo->prepare($this->queryString);

        foreach ($this->params as $boundParam) {
            $this->statement->bindValue($boundParam->name, $boundParam->value, $boundParam->type);
        }

        $this->statement->execute();
    }

    /**
     * get Statement
     * @param type $queryString
     * @param type $params
     * @return type Statement
     */
    public function getResultSet($queryString, $params) {
        $this->queryString = $queryString;
        $this->params = $params;
        $this->execute();
        return $this->statement;
    }

    /**
     * get Last Inserted ID
     * @param type $queryString
     * @param type $params
     * @return type
     */
    public function getLastInsertId($queryString, $params) {
        $this->queryString = $queryString;
        $this->params = $params;
        $this->execute();
        return $this->pdo->lastInsertId();
    }

    /**
     * just execute query
     * @param type $queryString
     * @param type $params
     */
    public function executeQuery($queryString, $params) {
        $this->queryString = $queryString;
        $this->params = $params;
        $this->execute();
    }

}

<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

interface DbQueryManagerInterface
{
    /**
     * @param array<string,mixed> $columnsToInsert
     */
    public function insert(string $tableName, array $columnsToInsert) : ?int;
    /**
     * @param array<string,mixed> $columnsToUpdate
     * @param array<string,mixed> $whereConditions
     */
    public function update(string $tableName, array $columnsToUpdate, array $whereConditions) : ?int;
    /**
     * @param array<int,mixed> $queryArgs
     */
    public function getVar(string $queryToPrepare, array $queryArgs) : ?string;
    /**
     * @param array<int,mixed> $queryArgs
     *
     * @return array<string,mixed>|null
     */
    public function getRow(string $queryToPrepare, array $queryArgs) : ?array;
    /**
     * @param array<int,mixed> $queryArgs
     *
     * @return array<array<string,mixed>>|null
     */
    public function getResults(string $queryToPrepare, array $queryArgs) : ?array;
    /**
     * @param array<string,mixed> $whereConditions
     */
    public function delete(string $tableName, array $whereConditions) : ?int;
    public function tablePosts() : string;
    public function setIsQueriesLogEnabled(bool $isQueriesLogEnabled) : void;
    public function isQueriesLogsEnabled() : bool;
    /**
     * @return array<int,array<string,mixed>>
     */
    public function getQueriesLog() : array;
    public function getCountOfQueries() : int;
    public function getSpentTimeInSeconds() : float;
    public function getLastInsertedId() : int;
    public function getOptionsPostId() : int;
}

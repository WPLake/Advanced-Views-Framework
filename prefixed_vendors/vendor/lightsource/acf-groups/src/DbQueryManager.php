<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\DbQueryManagerInterface;
use wpdb;
// separate class for abstraction, also allows to mock up methods in tests
class DbQueryManager implements DbQueryManagerInterface
{
    const OPTION_SETTINGS_POST_ID = 'light_source__acf_groups__settings_post_id';
    private bool $isQueriesLogsEnabled;
    /**
     * @var array<int,array<string,mixed>>
     */
    private array $queriesLog;
    private int $countOfQueries;
    private float $spentTimeInSeconds;
    private int $optionsPostId;
    public function __construct(bool $isQueriesLogEnabled = \false)
    {
        $this->isQueriesLogsEnabled = $isQueriesLogEnabled;
        $this->queriesLog = [];
        $this->countOfQueries = 0;
        $this->spentTimeInSeconds = 0;
        $this->optionsPostId = 0;
    }
    /**
     * @param array<int,mixed> $args
     */
    protected function maybeLog(string $method, array $args) : void
    {
        if (\false === $this->isQueriesLogsEnabled) {
            return;
        }
        $this->queriesLog[] = ['type' => $method, 'args' => $args];
    }
    protected function getWpDb() : ?wpdb
    {
        global $wpdb;
        return $wpdb;
    }
    /**
     * @param array<int,mixed> $args
     *
     * @return mixed
     */
    protected function executeWpDbMethod(string $method, array $args)
    {
        $wpDb = $this->getWpDb();
        if (null === $wpDb) {
            return null;
        }
        // @phpstan-ignore-next-line
        return \call_user_func_array([$wpDb, $method], $args);
    }
    /**
     * @param array<int,mixed> $args
     *
     * @return mixed
     */
    protected function execute(string $method, array $args)
    {
        $start = null;
        if ('prepare' !== $method) {
            $this->maybeLog($method, $args);
            $this->countOfQueries++;
            $start = \microtime(\true);
        }
        $result = $this->executeWpDbMethod($method, $args);
        if (null !== $start) {
            $this->spentTimeInSeconds += \microtime(\true) - $start;
        }
        return $result;
    }
    /**
     * @param array<string,mixed> $columnsToInsert
     */
    public function insert(string $tableName, array $columnsToInsert) : ?int
    {
        $insertedRowsNumber = $this->execute('insert', [$tableName, $columnsToInsert]);
        return \true === \is_int($insertedRowsNumber) ? $insertedRowsNumber : null;
    }
    /**
     * @param array<string,mixed> $columnsToUpdate
     * @param array<string,mixed> $whereConditions
     */
    public function update(string $tableName, array $columnsToUpdate, array $whereConditions) : ?int
    {
        $updatedRowsNumber = $this->execute('update', [$tableName, $columnsToUpdate, $whereConditions]);
        return \true === \is_int($updatedRowsNumber) ? $updatedRowsNumber : null;
    }
    /**
     * @param array<int,mixed> $queryArgs
     */
    public function getVar(string $queryToPrepare, array $queryArgs) : ?string
    {
        \array_unshift($queryArgs, $queryToPrepare);
        $selectQuery = $this->execute('prepare', $queryArgs);
        if (\false === \is_string($selectQuery)) {
            return null;
        }
        $result = $this->execute('get_var', [$selectQuery]);
        return \true === \is_string($result) ? $result : null;
    }
    /**
     * @param array<int,mixed> $queryArgs
     *
     * @return array<string,mixed>|null
     */
    public function getRow(string $queryToPrepare, array $queryArgs) : ?array
    {
        \array_unshift($queryArgs, $queryToPrepare);
        $selectQuery = $this->execute('prepare', $queryArgs);
        if (\false === \is_string($selectQuery)) {
            return null;
        }
        $result = $this->execute('get_row', [$selectQuery, ARRAY_A]);
        return \true === \is_array($result) ? $result : null;
    }
    /**
     * @param array<int,mixed> $queryArgs
     *
     * @return array<array<string,mixed>>|null
     */
    public function getResults(string $queryToPrepare, array $queryArgs) : ?array
    {
        \array_unshift($queryArgs, $queryToPrepare);
        $selectQuery = $this->execute('prepare', $queryArgs);
        if (\false === \is_string($selectQuery)) {
            return null;
        }
        $result = $this->execute('get_results', [$selectQuery, ARRAY_A]);
        return \true === \is_array($result) ? $result : null;
    }
    /**
     * @param array<string,mixed> $whereConditions
     */
    public function delete(string $tableName, array $whereConditions) : ?int
    {
        $deletedRowsNumber = $this->execute('delete', [$tableName, $whereConditions]);
        return \true === \is_int($deletedRowsNumber) ? $deletedRowsNumber : null;
    }
    public function tablePosts() : string
    {
        $wpdb = $this->getWpDb();
        if (null === $wpdb) {
            return '';
        }
        return $wpdb->posts;
    }
    public function setIsQueriesLogEnabled(bool $isQueriesLogEnabled) : void
    {
        $this->isQueriesLogsEnabled = $isQueriesLogEnabled;
    }
    public function isQueriesLogsEnabled() : bool
    {
        return $this->isQueriesLogsEnabled;
    }
    /**
     * @return array<int,array<string,mixed>>
     */
    public function getQueriesLog() : array
    {
        return $this->queriesLog;
    }
    public function getCountOfQueries() : int
    {
        return $this->countOfQueries;
    }
    public function getSpentTimeInSeconds() : float
    {
        return $this->spentTimeInSeconds;
    }
    public function getLastInsertedId() : int
    {
        $wpDb = $this->getWpDb();
        return null !== $wpDb ? $wpDb->insert_id : 0;
    }
    public function getOptionsPostId() : int
    {
        if (0 === $this->optionsPostId) {
            $optionSettingsPostId = get_option(static::OPTION_SETTINGS_POST_ID, 0);
            // cast to int.
            $optionSettingsPostId = \true === \is_numeric($optionSettingsPostId) ? (int) $optionSettingsPostId : 0;
            // validate if the post exists.
            $this->optionsPostId = 0 !== $optionSettingsPostId && null !== get_post($optionSettingsPostId) ? $optionSettingsPostId : 0;
            // insert the post if it does not exist.
            if (0 === $this->optionsPostId) {
                $insertedId = wp_insert_post(['post_title' => 'Storage for fields from Options Pages (do not remove)', 'post_type' => 'page', 'post_status' => 'private']);
                // @phpstan-ignore-next-line
                if (\false === is_wp_error($insertedId)) {
                    $this->optionsPostId = $insertedId;
                    update_option(static::OPTION_SETTINGS_POST_ID, $insertedId);
                }
            }
        }
        return $this->optionsPostId;
    }
}

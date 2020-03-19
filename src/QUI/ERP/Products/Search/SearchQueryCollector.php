<?php

namespace QUI\ERP\Products\Search;

/**
 * Class SearchQueryCollector
 *
 * Dynamically add search query instructions to a product search
 */
class SearchQueryCollector
{
    /**
     * WHERE statements
     *
     * @var array
     */
    protected $where = [];

    /**
     * PDO binds
     *
     * @var array
     */
    protected $binds = [];

    /**
     * The search params that are applied to the search the
     * SearchQueryCollector collects statements for
     *
     * @var array
     */
    protected $searchParams = [];

    /**
     * SearchQueryCollector constructor.
     *
     * @param array $searchParams (optional) - The search params that are applied to the search the
     * SearchQueryCollector collects statements for
     */
    public function __construct($searchParams = [])
    {
        $this->searchParams = $searchParams;
    }

    /**
     * Add a WHERE statement
     *
     * @param string $statement - WHERE statement (e.g. "`productId` IS NOT NULL"); do NOT add "WHERE"
     * @param array $binds (optional)
     *
     * $binds example:
     * [
     *      'varName' => [
     *          'value' => 'Patrick',
     *          'type' => \PDO::PARAM_STR
     *      ]
     * ]
     *
     */
    public function addWhere($statement, $binds = [])
    {
        $this->where[] = $statement;
        $this->binds   = array_merge($this->binds, $binds);
    }

    /**
     * @return array
     */
    public function getWhereStatements()
    {
        return $this->where;
    }

    /**
     * @return array
     */
    public function getBinds()
    {
        return $this->binds;
    }

    /**
     * @return array
     */
    public function getSearchParams()
    {
        return $this->searchParams;
    }

    /**
     * @param array $searchParams
     */
    public function setSearchParams(array $searchParams)
    {
        $this->searchParams = $searchParams;
    }
}

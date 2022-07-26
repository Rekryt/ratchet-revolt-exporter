<?php

namespace RatchetRevoltExporter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use React\Promise\PromiseInterface;

class Query {
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * DataBase Abstract Layer Connection
     * @var QueryBuilder
     */
    private QueryBuilder $query;

    /**
     * @var MySQLPlatform
     */
    private MySQLPlatform $platform;

    /**
     * Query constructor
     * @throws Exception
     */
    public function __construct() {
        $this->connection = DB::getInstance()->getConnection();
        $this->platform = DB::getInstance()->getPlatform();

        $this->query = $this->connection->createQueryBuilder();
    }

    /**
     * @param string|array|null $select
     * @return $this
     */
    public function select($select = null): Query {
        if (is_array($select)) {
            if (count($select) > 0) {
                $items = [];
                foreach ($select as $alias => $fields) {
                    if (is_string($fields)) {
                        $items[] = $fields;
                    } else {
                        foreach ($fields as $field_name) {
                            $items[] =
                                $this->platform->quoteIdentifier($alias) .
                                '.' .
                                $this->platform->quoteIdentifier($field_name) .
                                ' AS ' .
                                "`$alias.$field_name`";
                        }
                    }
                    $this->query->addSelect(implode(', ', $items));
                }
            }
        } else {
            $this->query->addSelect($select);
        }
        return $this;
    }

    /**
     * @param string $from
     * @param null $alias
     * @return $this
     */
    public function from(string $from, $alias = null): Query {
        $this->query->from($from, is_null($alias) ? null : $this->platform->quoteIdentifier($alias));
        return $this;
    }

    /**
     * @param string|array $fromAlias
     * @param string $join
     * @param string $alias
     * @param null $condition
     * @return $this
     */
    public function join(array|string $fromAlias, $join = '', $alias = '', $condition = null): Query {
        if (is_array($fromAlias)) {
            foreach ($fromAlias as $args) {
                $this->query->join($args[0], $args[1], $args[2], $args[3]);
            }
        } else {
            $this->query->join($fromAlias, $join, $alias, $condition);
        }
        return $this;
    }

    /**
     * @param string|array $fromAlias
     * @param string $join
     * @param string $alias
     * @param null $condition
     * @return $this
     */
    public function leftJoin(array|string $fromAlias, $join = '', $alias = '', $condition = null): Query {
        if (is_array($fromAlias)) {
            foreach ($fromAlias as $args) {
                $this->query->leftJoin(
                    $this->platform->quoteIdentifier($args[0]),
                    $args[1],
                    $this->platform->quoteIdentifier($args[2]),
                    $args[3]
                );
            }
        } else {
            $this->query->leftJoin(
                $this->platform->quoteIdentifier($fromAlias),
                $join,
                $this->platform->quoteIdentifier($alias),
                $condition
            );
        }
        return $this;
    }

    /**
     * @param string|array $where
     * @return $this
     */
    public function where(array|string $where): Query {
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $array = [];
                    foreach ($value as $v) {
                        $array[] = $this->platform->quoteStringLiteral($v);
                    }
                    $this->query->andWhere(
                        $this->platform->quoteIdentifier($key) . ' IN (' . implode(',', $array) . ')'
                    );
                } else {
                    $this->query->andWhere(
                        $this->platform->quoteIdentifier($key) . ' = ' . $this->platform->quoteStringLiteral($value)
                    );
                }
            }
        } else {
            $this->query->andWhere($where);
        }
        return $this;
    }

    /**
     * @param array $order
     * @return $this
     */
    public function order(array $order): Query {
        if (count($order)) {
            $splice = array_splice($order, 0, 1);
            foreach ($splice as $key => $value) {
                $this->query->orderBy($key, $value ? $value : 'ASC');
            }
        }
        foreach ($order as $key => $value) {
            $this->query->addOrderBy($key, $value ? $value : 'ASC');
        }
        return $this;
    }

    /**
     * @param array $limit
     * @return $this
     */
    public function limit(array $limit): Query {
        if (count($limit) > 1) {
            $this->query->setFirstResult($limit[0])->setMaxResults($limit[1]);
        } elseif (count($limit)) {
            $this->query->setMaxResults($limit[0]);
        }
        return $this;
    }

    /**
     * @param string|array $group
     * @return $this
     */
    public function group(array|string $group): Query {
        if (is_array($group)) {
            foreach ($group as $value) {
                $this->query->addGroupBy($value);
            }
        } else {
            $this->query->addGroupBy($group);
        }

        return $this;
    }

    /**
     * @param string|array $having
     * @return $this
     */
    public function having(array|string $having): Query {
        if (is_array($having)) {
            foreach ($having as $value) {
                $this->query->andHaving($value);
            }
        } else {
            $this->query->andHaving($having);
        }

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function insert(string $table): Query {
        $this->query->insert($table);
        return $this;
    }

    /**
     * @param array<string,string> $values
     * @return $this
     */
    public function values(array $values): Query {
        $escape = [];
        foreach ($values as $key => $value) {
            $escape[$this->platform->quoteIdentifier($key)] = !is_null($value)
                ? $this->platform->quoteStringLiteral($value)
                : 'NULL';
        }
        $this->query->values($escape);
        return $this;
    }

    /**
     * @param string $table
     * @param null $alias
     * @return $this
     */
    public function update(string $table, $alias = null): Query {
        $this->query->update($table, $alias);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function set($data = []): Query {
        foreach ($data as $key => $val) {
            $this->query->set(
                $this->platform->quoteIdentifier($key),
                !is_null($val) ? $this->platform->quoteStringLiteral($val) : 'NULL'
            );
        }
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function delete(string $table): Query {
        $this->query->delete($table);
        return $this;
    }

    /**
     * @return PromiseInterface
     */
    public function execute(): PromiseInterface {
        return $this->connection->query($this->query)->then(
            function (Result $result) {
                return $result;
            },
            function (\Exception $exception) {
                echo $exception->getMessage() . PHP_EOL;
            }
        );
    }

    /**
     * @return PromiseInterface
     */
    public function fetch(): PromiseInterface {
        return $this->execute()->then(
            function (Result $result) {
                return new ArrayCollection($result->fetchAllRows());
            },
            function (\RuntimeException $exception) {
                echo $exception->getMessage() . PHP_EOL;
            }
        );
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery(): QueryBuilder {
        return $this->query;
    }
}

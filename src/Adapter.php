<?php

/**
 * An Ornament adapter compatible with the Dabble database abstraction layer.
 *
 * To use, inject a new Adapter(Dabble\Adapter) with addAdapter instead of
 * Ornament's default Adapter\Pdo.
 *
 * The main advantage of using the Dormant adapter is that your `query` calls
 * now have full support for Dabble's where/options syntax.
 */
namespace Dormant;

use Ornament\Adapter\Pdo as PdoAdapter;
use Ornament\Adapter\Defaults;
use Ornament\Container;
use Dabble\Adapter as Dabble;
use Dabble\Query\Select;
use Dabble\Query\Where;
use Dabble\Query\Options;
use PDO;
use PDOException;

/**
 * The adapter class. Note that it extends the Pdo Adapter from Ornament and
 * simply augments it with its own `query` method that has full support for
 * Dabble $where and $options parameters.
 */
class Adapter extends PdoAdapter
{
    use Defaults;
    
    /**
     * Constructor. Pass in the Dabble adapter instead of the PDO adapter.
     *
     * @param Dabble\Adapter $adapter
     * @return void
     */
    public function __construct(Dabble $adapter)
    {
        parent::__construct($adapter);
    }

    /**
     * Query. The interface is identical to Ornament\Adapter::query, except that
     * it wraps $parameters and $options in the corresponding Where and Options
     * classes for extended functionality.
     *
     * @param object $object The model to query into.
     * @param array $parameters Array of `WHERE` parameters.
     * @param array $options Hash of `OPTIONS` options.
     * @param array $ctor Optional constructor arguments.
     * @return array|false An array of initialized models of the same type as
     *  $object, or false on failure.
     */
    public function query($object, array $parameters, array $options = [], array $ctor = [])
    {
        $identifier = $this->identifier;
        $fields = $this->fields;
        foreach ($fields as &$field) {
            $field = "$identifier.$field";
        }
        $identifier .= $this->generateJoin($fields);
        $query = new Select(
            $this->adapter,
            $identifier,
            $fields,
            new Where($parameters),
            new Options($options)
        );
        $stmt = $this->getStatement($query->__toString());
        try {
            $stmt->execute($query->getBindings());
            $stmt->setFetchMode(PDO::FETCH_CLASS, get_class($object), $ctor);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
}


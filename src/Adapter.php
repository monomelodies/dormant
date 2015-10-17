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
use Dabble\Adapter as Dab;
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

    public function __construct(Dab $adapter)
    {
        parent::__construct($adapter);
    }

    public function query($object, array $parameters, array $options = [])
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
        $stmt->execute($query->getBindings());
        try {
            $found = [];
            $stmt->setFetchMode(PDO::FETCH_INTO, clone $object);
            while ($entry = $stmt->fetch()) {
                $found[] = $entry;
                $stmt->setFetchMode(PDO::FETCH_INTO, clone $object);
            }
            return $found;
        } catch (PDOException $e) {
            return false;
        }
    }
}


<?php

namespace Dormant;

use Ornament\Adapter as Base;
use Ornament\Exception;
use Ornament\Model;
use Dabble\Adapter as Dab;
use Dabble\Query\SelectException;
use Dabble\Query\Select;
use Dabble\Query\Insert;
use Dabble\Query\Update;
use Dabble\Query\Delete;
use Dabble\Query\Where;
use Dabble\Query\Options;
use PDO;

final class Adapter implements Base
{
    private $adapter;
    private $table;
    private $fields;
    private $primaryKey = [];
    private $statements = [];

    public function __construct(Dab $adapter, $table, array $fields)
    {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->fields = $fields;
    }

    public function setPrimaryKey($field)
    {
        $this->primaryKey = func_get_args();
        return $this;
    }

    public function query($object, array $parameters, array $options = [])
    {
        $query = new Select(
            $this->adapter,
            $this->table,
            $this->fields,
            new Where($parameters),
            new Options($options)
        );   
        $stmt = $this->getStatement($query->__toString());
        $stmt->execute($query->getBindings());
        return $stmt->fetchAll(PDO::FETCH_CLASS, get_class($object));
    }

    public function load(Model $object)
    {
        $where = [];
        foreach ($this->primaryKey as $key) {
            if (isset($object->$key)) {
                $where[$key] = $object->$key;
            } else {
                throw new Exception\PrimaryKey($object, $key);
            }
        }
        $query = new Select(
            $this->adapter,
            $this->table,
            $this->fields,
            new Where($where),
            new Options
        );   
        $stmt = $this->getStatement($query->__toString());
        $stmt->setFetchMode(PDO::FETCH_INTO, $object);
        $stmt->execute($query->getBindings());
        $stmt->fetch();
        $object->markClean();
        return $this;
    }

    private function getStatement($sql)
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->adapter->prepare($sql);
        }
        return $this->statements[$sql];
    }

    public function create(Model $object)
    {
        $data = [];
        foreach ($this->fields as $field) {
            if (isset($object->$field)) {
                $data[$field] = $object->$field;
            }
        }
        $query = new Insert(
            $this->adapter,
            $this->table,
            $data
        );
        $stmt = $this->getStatement($query->__toString());
        $retval = $stmt->execute($query->getBindings());
        if (count($this->primaryKey) == 1) {
            $pk = $this->primaryKey[0];
            try {
                $object->$pk = $this->adapter->lastInsertId($this->table);
                $this->load($object);
            } catch (PDOException $e) {
                // Means this is not supported by this engine.
            }
        }
        return $retval;
    }

    public function update(Model $object)
    {
        $data = [];
        foreach ($this->fields as $field) {
            if (property_exists($object, $field)) {
                $data[$field] = $object->$field;
            }
        }
        $where = [];
        foreach ($this->primaryKey as $key) {
            $where[$key] = $object->$key;
        }
        $query = new Update(
            $this->adapter,
            $this->table,
            $data,
            new Where($where),
            new Options
        );
        $stmt = $this->getStatement($query->__toString());
        $retval = $stmt->execute($query->getBindings());
        $this->load($object);
        return $retval;
    }

    public function delete(Model $object)
    {
        $where = [];
        foreach ($this->primaryKey as $key) {
            $where[$key] = $object->$key;
        }
        $query = new Delete(
            $this->adapter,
            $this->table,
            $where
        );
        $stmt = $this->getStatement($query->__toString());
        $retval = $stmt->execute($query->getBindings());
        return $retval;
    }
}


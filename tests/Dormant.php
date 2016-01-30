<?php

namespace Dormant\Tests;

use Dabble\Adapter\Sqlite;
use Dormant\Demo\MyTableModel;

/**
 * Dormant models behave as expected
 */
class Dormant
{
    /**
     * {0}::save should persist a new model, so that {0}::$comment was updated.
     */
    public function creating(MyTableModel &$model = null)
    {
        $model = new MyTableModel($this->db);
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $stmt = $this->db->prepare("SELECT * FROM my_table ORDER BY id");
        yield function ($result) use ($stmt) {
            $stmt->execute();
            $rows = $stmt->fetchAll();
            return count($rows) == 4;
        };
    }
    
    /**
     * {0}::$comment should be updated
     */
    public function updating(MyTableModel &$model = null)
    {
        $model = MyTableModel::query(['id' => 1], [], [$this->db]);
        $model->comment = 'Awesome';
        $model->save();
        unset($model);
        $model = MyTableModel::query(['id' => 1], [], [$this->db]);
        yield 'Awesome';
    }

    /**
     * {0}::query should return a list of three models, and the first result of
     * {0}::query must be a MyTableModel
     */
    public function testQuery(MyTableModel &$model = null, $where = [], $options = [], &$ctor = null)
    {
        $model = new MyTableModel($this->db);
        $ctor = [$this->db];
        yield 'count' => 3;
        yield function ($result) {
            return get_class($result[0]) == 'Dormant\Demo\MyTableModel';
        };
    }

    public function __wakeup()
    {
        $this->db = new Sqlite(':memory:');
        $this->db->exec("CREATE TABLE my_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(30),
            comment VARCHAR(140)
        )");
        $this->db->exec("CREATE TABLE linked_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mytable INTEGER NOT NULL REFERENCES my_table(id),
            points INTEGER NOT NULL
        )");
        $this->db->exec("CREATE TABLE bitflag (
            status INTEGER NOT NULL DEFAULT NULL
        )");
        $this->db->exec("INSERT INTO my_table VALUES
            (1, 'John', 'A Beatle'),
            (2, 'Keith', 'A Rolling Stone'),
            (3, 'Roger', 'A Byred')");
    }
}


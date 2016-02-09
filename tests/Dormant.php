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
     * Save should persist a new model {?}, so that $comment was updated {?}.
     */
    public function creating(MyTableModel $model)
    {
        $model->__gentryConstruct($this->db);
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        yield assert($model->save() == null);
        $stmt = $this->db->prepare("SELECT * FROM my_table ORDER BY id");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        yield assert(count($rows) == 4);
    }
    
    /**
     * $comment should be updated
     */
    public function updating(MyTableModel $model)
    {
        $model->__gentryConstruct($this->db);
        $test = $model::find(['id' => 1], []);
        $test->comment = 'Awesome';
        $test->save();
        unset($test);
        $test = $model::find(['id' => 1], []);
        yield assert($test->comment == 'Awesome');
    }

    /**
     * query should return a list of three models {?}, and the first result of
     * query must be a MyTableModel {?}
     */
    public function testQuery(MyTableModel $model)
    {
        $model->__gentryConstruct($this->db);
        $result = $model::query([], []);
        yield assert(count($result) == 3);
        $result->rewind();
        foreach ($result as $check) {
            yield assert($check instanceof MyTableModel);
            break;
        }
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


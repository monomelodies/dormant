<?php

use Dabble\Adapter\Sqlite;

class MyTableModel
{
    use Ornament\Model;
    use Ornament\Query;

    public $id;
    public $name;
    public $comment;

    public function __construct()
    {
        $this->addAdapter(new Dormant\Adapter($GLOBALS['pdo']));
    }
}

class DabbleTest extends PHPUnit_Extensions_Database_TestCase
{
    private static $pdo;
    private $conn;

    /**
     * @covers Ornament\Storage::dirty
     */
    public function testModel()
    {
        $model = new MyTableModel;
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $model->save();
        $stmt = self::$pdo->prepare("SELECT * FROM my_table");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $this->assertEquals(4, count($rows));
        $model->comment = 'Awesome';
        $model->save();
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertEquals('Awesome', $row[3]['comment']);
        unset($model);
        $model = new MyTableModel(self::$pdo);
        $model->id = 1;
        $model->load();
    }

    public function testQuery()
    {
        $list = MyTableModel::query([]);
        $this->assertEquals(3, count($list));
        $this->assertEquals('MyTableModel', get_class($list[0]));
    }

    public function getConnection()
    {
        if ($this->conn === null) {
            if (!isset(self::$pdo)) {
                self::$pdo = new Sqlite(':memory:');
                self::$pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
                $GLOBALS['pdo'] = self::$pdo;
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'test');
        }
        return $this->conn;
    }
    
    public function getDataSet()
    {
        return $this->createXmlDataSet(__DIR__.'/dataset.xml');
    }
}


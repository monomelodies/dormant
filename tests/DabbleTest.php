<?php

use Dabble\Adapter\Sqlite;

class MyTableModel
{
    use Dormant\Dabble;
    use Ornament\Query;

    public $id;
    public $name;
    public $comment;

    public function __construct()
    {
        $this->addDabbleAdapter($GLOBALS['pdo']);
    }
}

class LinkedTableModel
{
    use Dormant\Dabble;
    use Ornament\Autoload;

    public $id;
    /**
     * @Model MyTableModel
     */
    public $mytable;
    public $points;

    public function __construct()
    {
        $this->addDabbleAdapter($GLOBALS['pdo']);
    }

    public function getPercentage()
    {
        return round(($this->points / 5) * 100);
    }

    public function __index($index)
    {
    }
}

class BitflagModel
{
    use Dormant\Dabble;
    use Ornament\Bitflag;

    const STATUS_NICE = 1;
    const STATUS_CATS = 2;
    const STATUS_CODE = 4;

    public $status;

    public function __construct()
    {
        $this->addDabbleAdapter($GLOBALS['pdo']);
        $this->addBitflag('nice', self::STATUS_NICE, 'status');
        $this->addBitflag('cats', self::STATUS_CATS, 'status');
        $this->addBitflag('code', self::STATUS_CODE, 'status');
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

    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     */
    public function testVirtuals()
    {
        $model = new MyTableModel;
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $model->save();
        $linked = new LinkedTableModel(self::$pdo);
        $linked->mytable = $model->id;
        $linked->points = 4;
        $linked->save();
        $this->assertEquals(80, $linked->percentage);
        $linked->percentage = 70;
    }

    public function testBitflags()
    {
        $model = new BitflagModel;
        $model->code = true;
        $model->cats = true;
        $this->assertEquals(6, $model->status);
        $model->code = false;
        $model->nice = true;
        $this->assertEquals(3, $model->status);
    }

    public function testAutoload()
    {
        $model = new MyTableModel;
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $model->save();
        $linked = new LinkedTableModel(self::$pdo);
        $linked->mytable = $model->id;
        $linked->points = 4;
        $linked->save();
        unset($model, $linked);
        $linked = new LinkedTableModel;
        $linked->id = 1;
        $linked->load();
        $this->assertEquals('MyTableModel', get_class($linked->mytable));
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


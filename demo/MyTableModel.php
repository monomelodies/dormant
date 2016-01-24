<?php

namespace Dormant\Demo;

use Dormant\Adapter;
use Ornament;

/**
 * @Identifier my_table
 */
class MyTableModel
{
    use Ornament\Model;
    use Ornament\Query;
    
    public $id;
    public $name;
    public $comment;
    
    public function __construct($pdo)
    {
        $this->addAdapter(new Adapter($pdo));
    }
}


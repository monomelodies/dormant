<?php

namespace Dormant;

use Ornament\Repository;
use Ornament\Storage;
use Ornament\Table;
use Dabble\Adapter;

trait Dabble
{
    use Storage;
    use Table;

    public function addDabbleAdapter(
        Adapter $adapter,
        $id = null,
        array $fields = null
    ) {
        if (!isset($id)) {
            $id = $this->guessTableName();
        }
        $annotations = $this->annotations()['properties'];
        if (!isset($fields)) {
            $fields = [];
            foreach (Repository::getProperties($this) as $prop) {
                if (property_exists($this, $prop)
                    && !isset($annotations[$prop]['Virtual'])
                ) {
                    $fields[] = $prop;
                }
            }
        }
        $pk = false;
        if (in_array('id', $fields)) {
            $pk = true;
        }
        $adapter = new \Dormant\Adapter($adapter, $id, $fields);
        if ($pk) {
            $adapter->setPrimaryKey('id');
        }
        return $this->addAdapter($adapter, $id, $fields);
    }
}


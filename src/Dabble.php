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

    protected function addDabbleAdapter(
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
        $pk = [];
        foreach ($annotations as $prop => $anno) {
            if (isset($anno['PrimaryKey'])) {
                $pk[] = $prop;
            }
        }
        if (!$pk && in_array('id', $fields)) {
            $pk[] = 'id';
        }
        $adapter = new \Dormant\Adapter($adapter, $id, $fields);
        if ($pk) {
            call_user_func_array([$adapter, 'setPrimaryKey'], $pk);
        }
        return $this->addAdapter($adapter, $id, $fields);
    }
}


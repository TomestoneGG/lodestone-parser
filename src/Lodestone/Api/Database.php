<?php

namespace Lodestone\Api;

use Lodestone\Parser\ParseCharacter;
use Lodestone\Parser\ParseDatabaseItems;

class Database extends ApiAbstract
{
    public function equipment(int $page = 1)
    {
        $query = [];
        if ($page > 1)
            $query['page'] = $page;

        return $this->handle(ParseDatabaseItems::class, [
            'endpoint' => "/lodestone/playguide/db/search",
            'query'    => $query
        ]);
    }

    public function nonEquipment(int $page = 1)
    {
        $query = [ 'category' => 'item_non_equipment' ];
        if ($page > 1)
            $query['page'] = $page;

        return $this->handle(ParseDatabaseItems::class, [
            'endpoint' => "/lodestone/playguide/db/search",
            'query'    => $query
        ]);
    }

    public function item(string $id)
    {
        return $this->handle(ParseCharacter::class, [
            'endpoint' => "/lodestone/playguide/db/item/{$id}",
        ]);
    }
}

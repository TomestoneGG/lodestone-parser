<?php

namespace Lodestone\Api;

use Lodestone\Parser\ParseCharacter;
use Lodestone\Parser\ParseDatabaseSearch;

class Database extends ApiAbstract
{
    public function search(string $category = null, string $patch = null, string $name = null, int $page = 1)
    {
        $name = $name ? str_ireplace(self::STRING_FIXES[0], self::STRING_FIXES[1], $name) : $name;

        $query = [];
        if ($name)
            $query['q'] = '"'. $name .'"';
        if ($category)
            $query['category'] = $category;
        if ($patch)
            $query['patch'] = $patch;
        if ($page > 1)
            $query['page'] = $page;

        return $this->handle(ParseDatabaseSearch::class, [
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

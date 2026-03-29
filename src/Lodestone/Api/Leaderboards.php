<?php

namespace Lodestone\Api;

use Lodestone\Parser\ParseCharacter;
use Lodestone\Parser\ParseCrystallineConflictStandings;

class Leaderboards extends ApiAbstract
{
    /**
     * Params: http://eu.finalfantasyxiv.com/lodestone/ranking/thefeast
     */
    public function feast($season = false, array $params = [])
    {
        $url = "/lodestone/ranking/thefeast";

        // append on season if it's provides
        if ($season !== false && is_numeric($season)) {
            $url .= "/result/{$season}";
        }

        return $this->handle(ParseCharacter::class, [
            'endpoint' => $url,
            'query'    => $params
        ]);
    }

    /**
     * Params: http://eu.finalfantasyxiv.com/lodestone/ranking/deepdungeon
     */
    public function ddPalaceOfTheDead(array $params = [])
    {
        return $this->handle(ParseCharacter::class, [
            'endpoint' => "/lodestone/ranking/deepdungeon",
            'query'    => $params,
        ]);
    }

    /**
     * Params: http://eu.finalfantasyxiv.com/lodestone/ranking/deepdungeon2
     */
    public function ddHeavenOnHigh(array $params = [])
    {
        return $this->handle(ParseCharacter::class, [
            'endpoint' => "/lodestone/ranking/deepdungeon2",
            'query'    => $params,
        ]);
    }

    /**
     * Params: http://eu.finalfantasyxiv.com/lodestone/ranking/deepdungeon3
     */
    public function ddEurekaOrthos(array $params = [])
    {
        return $this->handle(ParseCharacter::class, [
            'endpoint' => "/lodestone/ranking/deepdungeon3",
            'query'    => $params,
        ]);
    }

    /**
     * Params: http://eu.finalfantasyxiv.com/lodestone/ranking/deepdungeon4
     */
    public function ddPilgrimTraverse(array $params = [])
    {
        return $this->handle(ParseCharacter::class, [
            'endpoint' => "/lodestone/ranking/deepdungeon4",
            'query'    => $params,
        ]);
    }

    /**
     * Params: https://na.finalfantasyxiv.com/lodestone/ranking/crystallineconflict/?dcgroup=Light
     */
    public function crystallineConflict(string $dcgroup, $season = false, array $params = [])
    {
        $url = "/lodestone/ranking/crystallineconflict/";

        if (is_array($season)) {
            $params = $season;
            $season = false;
        }

        if ($season !== false && is_numeric($season)) {
            $url .= "result/{$season}/";
        }

        $params['dcgroup'] = $dcgroup;

        return $this->handle(ParseCrystallineConflictStandings::class, [
            'endpoint' => $url,
            'query'    => $params,
        ]);
    }
}

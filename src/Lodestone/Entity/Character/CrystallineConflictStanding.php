<?php

namespace Lodestone\Entity\Character;

use Lodestone\Entity\AbstractEntity;

class CrystallineConflictStanding extends AbstractEntity
{
    public $ID;
    public $Name;
    public $Server;
    public $DC;
    public $Avatar;
    public $Tier;
    public $Points = 0;
    public $Wins = 0;
    public $Position = 0;
    public $PreviousPosition = null;
}

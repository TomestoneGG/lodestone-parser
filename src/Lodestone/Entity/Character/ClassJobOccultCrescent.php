<?php

namespace Lodestone\Entity\Character;

use Lodestone\Entity\AbstractEntity;

class ClassJobOccultCrescent extends AbstractEntity
{
    public $Name = 'OccultCrescent';
    public $Level;
    public $Knowledge;

    public function __construct(string $name)
    {
        $this->Name = $name;
    }
}

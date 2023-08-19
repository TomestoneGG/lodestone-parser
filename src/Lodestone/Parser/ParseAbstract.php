<?php

namespace Lodestone\Parser;

use Rct567\DomQuery\DomQuery;

class ParseAbstract
{
    /** @var DomQuery */
    public $dom;
    
    public function setDom(string $html, bool $mobile = false, bool $db = false)
    {
        $dom = new DomQuery($html);
        if ($db)
            $this->dom = $dom->find("#eorzea_db");
        else
            $this->dom = $dom->find($mobile?'.ldst_main_content':'.ldst__contents');
    }
}

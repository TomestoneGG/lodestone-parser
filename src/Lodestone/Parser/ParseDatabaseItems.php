<?php

namespace Lodestone\Parser;

use Lodestone\Entity\Database\Item;
use Rct567\DomQuery\DomQuery;

class ParseDatabaseItems extends ParseAbstract implements Parser
{
    use HelpersTrait;
    use ListTrait;

    public function handle(string $html)
    {
        // set dom
        $this->setDom($html, false, true);

        // build list
        $this->setDbList();

        // parse list
        /** @var DomQuery $node */
        foreach ($this->dom->find('.latest_patch__major__item') as $node) {
            $obj         = new Item();

            $obj->ID = null;
            $img = $node->find('.db-list__item__icon__inner img');
            $obj->Icon = $img ? explode('?', $img->attr('src'))[0] : null;

            $link = $node->find('.db-table__txt--detail_link');
            $obj->Name   = $link ? $link->text() : null;

            $href = $link->attr('href');
            if ($href) {
                $results = explode('/', $href);
                if (isset($results[5]))
                    $obj->ID = $results[5];
            }

            $obj->ItemLevel = 0;
            $itemLevelCell = $node->next();
            if ($itemLevelCell) {
                $itemLevelText = $itemLevelCell->text();
                $obj->ItemLevel = filter_var($itemLevelText, FILTER_SANITIZE_NUMBER_INT);
            }

            $this->list->Results[] = $obj;
        }


        return $this->list;
    }
}

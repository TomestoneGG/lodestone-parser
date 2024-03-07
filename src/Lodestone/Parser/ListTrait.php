<?php

namespace Lodestone\Parser;

use Lodestone\Entity\Character\CharacterSimple;
use Lodestone\Entity\ListView\ListView;
use Rct567\DomQuery\DomQuery;

trait ListTrait
{
    /** @var ListView */
    protected $list;

    /**
     * Initialize a list view
     */
    protected function setList()
    {
        $this->list = new ListView();

        if (empty($this->dom->find('.btn__pager__current')->html())) {
            return;
        }

        $data = $this->dom->find('.btn__pager__current')->text();
        [$current, $total] = explode(' of ', $data);

        $this->list->Pagination->Page = filter_var($current, FILTER_SANITIZE_NUMBER_INT);
        $this->list->Pagination->PageTotal = filter_var($total, FILTER_SANITIZE_NUMBER_INT);

        // member count
        $count = $this->dom->find('.parts__total')->text();
        $this->list->Pagination->ResultsTotal = filter_var($count, FILTER_SANITIZE_NUMBER_INT);

        // set next+prev
        $this->list->Pagination->setNextPrevious();
    }

    protected function setDbList()
    {
        $this->list = new ListView();

        $resultsTotal = $this->dom->find('.db__l_main__footer .pager .current_list .total')->text();

        if (empty($resultsTotal)) {
            return;
        }

        $this->list->Pagination->ResultsTotal = filter_var($resultsTotal, FILTER_SANITIZE_NUMBER_INT);

        $currentPage = $this->dom->find('.db__l_main__footer .pager .current a')->text();
        $this->list->Pagination->Page = filter_var($currentPage, FILTER_SANITIZE_NUMBER_INT);

        // 50 results per page
        $this->list->Pagination->PageTotal = ceil($this->list->Pagination->ResultsTotal / 50.0);

        // set next+prev
        $this->list->Pagination->setNextPrevious();
    }

    public function handleCharacterList()
    {
        /** @var DomQuery $node */
        foreach ($this->dom->find('.ldst__window div.entry') as $node) {
            $obj         = new CharacterSimple();
            $obj->ID     = $this->getLodestoneId($node);
            $obj->Name   = $node->find('.entry__name')->text();
            $obj->Lang   = trim($node->find('.entry__chara__lang')->text());
            $obj->Server = trim(explode(' ', $node->find('.entry__world')->text())[0]);
            $obj->Avatar = $node->find('.entry__chara__face img')->attr('src');

            $this->list->Results[] = $obj;
        }
    }
}

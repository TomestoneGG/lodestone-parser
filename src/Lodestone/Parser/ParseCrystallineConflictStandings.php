<?php

namespace Lodestone\Parser;

use Lodestone\Entity\Character\CrystallineConflictStanding;
use Rct567\DomQuery\DomQuery;

class ParseCrystallineConflictStandings extends ParseAbstract implements Parser
{
    use HelpersTrait;
    use ListTrait;

    public function handle(string $html)
    {
        $this->setDom($html);
        $this->setList();

        /** @var DomQuery $node */
        foreach ($this->dom->find('.ranking_set') as $node) {
            $obj = new CrystallineConflictStanding();

            $obj->ID = $this->getRankingCharacterId($node);
            $obj->Position = $this->getNumericValue($node->find('.order')->text());
            $obj->PreviousPosition = $this->getNullableNumericValue($node->find('.prev_order')->text());
            $obj->Avatar = $node->find('.face img')->attr('src');
            $obj->Name = html_entity_decode(trim($node->find('.name h3')->text()), ENT_QUOTES, 'UTF-8');

            [$server, $dc] = $this->getServerAndDc($node->find('.name .world')->text());
            $obj->Server = $server;
            $obj->DC = $dc;

            $tier = $node->find('.tier img')->attr('alt') ?: $node->find('.tier img')->attr('data-tooltip');
            $obj->Tier = html_entity_decode(trim((string)$tier), ENT_QUOTES, 'UTF-8');
            $obj->Points = $this->getNumericValue($node->find('.points p')->text());

            $this->list->Results[] = $obj;
        }

        $this->setSinglePagePaginationWhenNeeded();

        return $this->list;
    }

    private function getRankingCharacterId(DomQuery $node)
    {
        $href = $node->attr('data-href') ?: $node->find('a')->attr('href');
        $parts = explode('/', trim((string)$href, '/'));

        return $parts[2] ?? null;
    }

    private function getNumericValue($value): int
    {
        return (int) filter_var((string) $value, FILTER_SANITIZE_NUMBER_INT);
    }

    private function getNullableNumericValue($value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : $this->getNumericValue($value);
    }

    private function setSinglePagePaginationWhenNeeded()
    {
        if ($this->list->Pagination->Page) {
            return;
        }

        $count = count($this->list->Results);

        if (!$count) {
            return;
        }

        $this->list->Pagination->Page = 1;
        $this->list->Pagination->PageTotal = 1;
        $this->list->Pagination->Results = $count;
        $this->list->Pagination->ResultsPerPage = $count;
        $this->list->Pagination->ResultsTotal = $count;
        $this->list->Pagination->PageNext = null;
        $this->list->Pagination->PagePrev = null;
    }
}

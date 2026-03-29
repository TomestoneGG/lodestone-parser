<?php

namespace Lodestone\Tests;

use Lodestone\Parser\ParseCrystallineConflictStandings;
use PHPUnit\Framework\TestCase;

final class ParseCrystallineConflictStandingsTest extends TestCase
{
    public function testParsesPaginatedStandings(): void
    {
        $parser = new ParseCrystallineConflictStandings();
        $result = $parser->handle(<<<HTML
<div class="ldst__contents">
    <div class="btn__pager__current">2 of 6</div>
    <div class="parts__total">300</div>
    <div class="ranking_set" data-href="/lodestone/character/14658770/">
        <div class="order">51</div>
        <div class="prev_order"></div>
        <div class="face"><img src="https://example.com/avatar.jpg" /></div>
        <div class="name">
            <h3>Resilla Frostflow</h3>
            <span class="world">Cerberus [Chaos]</span>
        </div>
        <div class="tier">
            <img alt="Crystal" data-tooltip="Crystal" />
        </div>
        <div class="points"><p>793</p></div>
    </div>
</div>
HTML);

        self::assertEquals(2, $result->Pagination->Page);
        self::assertEquals(6, $result->Pagination->PageTotal);
        self::assertEquals(3, $result->Pagination->PageNext);
        self::assertEquals(1, $result->Pagination->PagePrev);
        self::assertEquals(300, $result->Pagination->ResultsTotal);
        self::assertCount(1, $result->Results);
        self::assertSame('14658770', $result->Results[0]->ID);
        self::assertSame('Resilla Frostflow', $result->Results[0]->Name);
        self::assertSame('Cerberus', $result->Results[0]->Server);
        self::assertSame('Chaos', $result->Results[0]->DC);
        self::assertSame('Crystal', $result->Results[0]->Tier);
        self::assertSame(793, $result->Results[0]->Points);
        self::assertSame(51, $result->Results[0]->Position);
        self::assertNull($result->Results[0]->PreviousPosition);
    }

    public function testParsesSinglePageRankTypeStandings(): void
    {
        $parser = new ParseCrystallineConflictStandings();
        $result = $parser->handle(<<<HTML
<div class="ldst__contents">
    <div class="ranking_set" data-href="/lodestone/character/12345678/">
        <div class="order">1</div>
        <div class="prev_order">2</div>
        <div class="face"><img src="https://example.com/avatar-top10.jpg" /></div>
        <div class="name">
            <h3>Alpha Wolf</h3>
            <span class="world">Phoenix [Light]</span>
        </div>
        <div class="tier">
            <img data-tooltip="Diamond" />
        </div>
        <div class="points"><p>1500</p></div>
    </div>
</div>
HTML);

        self::assertEquals(1, $result->Pagination->Page);
        self::assertEquals(1, $result->Pagination->PageTotal);
        self::assertEquals(1, $result->Pagination->Results);
        self::assertEquals(1, $result->Pagination->ResultsPerPage);
        self::assertEquals(1, $result->Pagination->ResultsTotal);
        self::assertCount(1, $result->Results);
        self::assertSame('12345678', $result->Results[0]->ID);
        self::assertSame('Alpha Wolf', $result->Results[0]->Name);
        self::assertSame('Phoenix', $result->Results[0]->Server);
        self::assertSame('Light', $result->Results[0]->DC);
        self::assertSame('Diamond', $result->Results[0]->Tier);
        self::assertSame(1500, $result->Results[0]->Points);
        self::assertSame(1, $result->Results[0]->Position);
        self::assertSame(2, $result->Results[0]->PreviousPosition);
    }
}

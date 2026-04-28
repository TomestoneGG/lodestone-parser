<?php

namespace Lodestone\Tests;

use Lodestone\Parser\ParseCharacter;
use PHPUnit\Framework\TestCase;

final class ParseCharacterTest extends TestCase
{
    public function testCanSkipGearParsing(): void
    {
        $parser = new ParseCharacter([
            'fetch_gear' => false,
        ]);

        $result = $parser->handle(<<<HTML
<div class="ldst__contents">
    <div id="character">
        <a href="/lodestone/character/12345678/"></a>
        <div class="frame__chara">
            <div class="frame__chara__box">
                <p class="frame__chara__title">The Example</p>
            </div>
        </div>
        <div class="frame__chara__face">
            <img src="https://example.com/avatar-c0.jpg" />
        </div>
    </div>
    <p class="frame__chara__name">Test Character</p>
    <p class="frame__chara__world">Gilgamesh [Aether]</p>
    <div class="character__selfintroduction">Hello there.</div>
    <div class="character__view">
        <div class="item_detail_box">
            <div class="db-tooltip__item__name">Weapon Name</div>
            <div class="db-tooltip__bt_item_detail">
                <a href="/lodestone/playguide/db/item/abcd1234efgh5678/"></a>
            </div>
            <div class="db-tooltip__item__category">Gladiator's Arm</div>
        </div>
    </div>
</div>
HTML);

        self::assertSame('12345678', $result->ID);
        self::assertSame('Test Character', $result->Name);
        self::assertSame('Gilgamesh', $result->Server);
        self::assertSame('Aether', $result->DC);
        self::assertSame('https://example.com/avatar-c0.jpg', $result->Avatar);
        self::assertSame('https://example.com/avatar-l0.jpg', $result->Portrait);
        self::assertSame('Hello there.', $result->Bio);
        self::assertArrayNotHasKey('Gear', $result->GearSet);
    }
}

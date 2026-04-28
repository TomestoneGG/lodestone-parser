<?php

namespace Lodestone\Tests;

use Lodestone\Parser\ParseCharacter;
use PHPUnit\Framework\TestCase;

final class ParseCharacterLazyGearTest extends TestCase
{
    public function testParsesLazyLoadedGearTooltips(): void
    {
        $parser = new ParseCharacter([
            'fetch_gear' => true,
            'gear_tooltips' => [
                '/lodestone/character/34444881/equipment/tooltip/0' => <<<'HTML'
<div class="db-tooltip db-tooltip__wrapper item_detail_box">
    <div class="db-tooltip__item__txt">
        <h2 class="db-tooltip__item__name">Quetzalli Longsword</h2>
        <p class="db-tooltip__item__category">Gladiator's Arm</p>
    </div>
    <div class="db-tooltip__bt_item_detail">
        <a href="/lodestone/playguide/db/item/3ba9d093e01/"></a>
    </div>
    <ul class="db-tooltip__materia">
        <li><div class="db-tooltip__materia__txt">Savage Aim Materia XII<br><span class="db-tooltip__materia__txt--base">Critical Hit +54</span></div></li>
        <li><div class="db-tooltip__materia__txt">Heavens' Eye Materia XII<br><span class="db-tooltip__materia__txt--base">Direct Hit Rate +54</span></div></li>
    </ul>
</div>
HTML,
                '/lodestone/character/34444881/equipment/tooltip/11' => <<<'HTML'
<div class="db-tooltip db-tooltip__wrapper item_detail_box">
    <div class="db-tooltip__item__txt">
        <h2 class="db-tooltip__item__name">Quetzalli Ring of Fending</h2>
        <p class="db-tooltip__item__category">Ring</p>
    </div>
    <div class="db-tooltip__bt_item_detail">
        <a href="/lodestone/playguide/db/item/f5dc1a45321/"></a>
    </div>
</div>
HTML,
                '/lodestone/character/34444881/equipment/tooltip/12' => <<<'HTML'
<div class="db-tooltip db-tooltip__wrapper item_detail_box">
    <div class="db-tooltip__item__txt">
        <h2 class="db-tooltip__item__name">Archeo Kingdom Ring of Fending</h2>
        <p class="db-tooltip__item__category">Ring</p>
    </div>
    <div class="db-tooltip__bt_item_detail">
        <a href="/lodestone/playguide/db/item/f2bc1add947/?hq=1"></a>
    </div>
    <div class="db-tooltip__info_text"><a href="/lodestone/character/10395964/">Archon Azrael</a></div>
</div>
HTML,
            ],
        ]);

        $result = $parser->handle(<<<HTML
<div class="ldst__contents">
    <div id="character">
        <a href="/lodestone/character/34444881/"></a>
        <div class="frame__chara__face"><img src="https://example.com/avatar-c0.jpg" /></div>
    </div>
    <p class="frame__chara__name">Test Character</p>
    <p class="frame__chara__world">Gilgamesh [Aether]</p>
    <div class="character__selfintroduction">Hello there.</div>
    <div class="character__view">
        <div class="js__db_tooltip" data-lazy_load_url="/lodestone/character/34444881/equipment/tooltip/0"></div>
        <div class="js__db_tooltip" data-lazy_load_url="/lodestone/character/34444881/equipment/tooltip/11"></div>
        <div class="js__db_tooltip" data-lazy_load_url="/lodestone/character/34444881/equipment/tooltip/12"></div>
    </div>
</div>
HTML);

        self::assertSame('Quetzalli Longsword', $result->GearSet['Gear']['MainHand']->Name);
        self::assertSame('3ba9d093e01', $result->GearSet['Gear']['MainHand']->ID);
        self::assertCount(2, $result->GearSet['Gear']['MainHand']->Materia);
        self::assertSame('Quetzalli Ring of Fending', $result->GearSet['Gear']['Ring1']->Name);
        self::assertSame('Archeo Kingdom Ring of Fending', $result->GearSet['Gear']['Ring2']->Name);
        self::assertSame('10395964', $result->GearSet['Gear']['Ring2']->Creator);
    }
}

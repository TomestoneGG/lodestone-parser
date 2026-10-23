<?php

namespace Lodestone\Tests;

use Lodestone\Parser\ParseLinkshellCWMembers;
use PHPUnit\Framework\TestCase;

final class ParseLinkshellCWMembersTest extends TestCase
{
    public function testParsesDataCenterWithBlankLinesInHeading(): void
    {
        $parser = new ParseLinkshellCWMembers();
        $result = $parser->handle(<<<'HTML'
<div class="ldst__contents">
    <h2 class="heading__linkshell__name">ANERGIA ULTRAS


        <span class="heading__cwls__dcname">
            Light
        </span>
    </h2>
    <div class="ldst__window">
        <div class="entry">
            <a href="/lodestone/character/12345678/">
                <div class="entry__chara__face"><img src="https://example.com/master.jpg" /></div>
                <p class="entry__name">Test Master</p>
                <p class="entry__world">Phoenix [Light]</p>
                <p class="entry__chara_info__linkshell"><img src="https://example.com/master.png" />Master</p>
            </a>
        </div>
        <div class="entry">
            <a href="/lodestone/character/23456789/">
                <div class="entry__chara__face"><img src="https://example.com/leader.jpg" /></div>
                <p class="entry__name">Test Leader</p>
                <p class="entry__world">Lich [Light]</p>
                <p class="entry__chara_info__linkshell"><img src="https://example.com/leader.png" />Leader</p>
            </a>
        </div>
        <div class="entry">
            <a href="/lodestone/character/34567890/">
                <div class="entry__chara__face"><img src="https://example.com/member.jpg" /></div>
                <p class="entry__name">Test Member</p>
                <p class="entry__world">Twintania [Light]</p>
            </a>
        </div>
    </div>
</div>
HTML);

        self::assertSame('ANERGIA ULTRAS', $result->Profile->Name);
        // Server is the CWLS data center in the public response contract.
        self::assertSame('Light', $result->Profile->Server);
        self::assertCount(3, $result->Results);

        $expected = [
            ['12345678', 'Test Master', 'Phoenix', 'master', 'Master'],
            ['23456789', 'Test Leader', 'Lich', 'leader', 'Leader'],
            ['34567890', 'Test Member', 'Twintania', 'member', null],
        ];
        foreach ($expected as $index => [$id, $name, $server, $image, $rank]) {
            $member = $result->Results[$index];
            self::assertSame($id, $member->ID);
            self::assertSame($name, $member->Name);
            self::assertSame($server, $member->Server);
            self::assertSame("https://example.com/{$image}.jpg", $member->Avatar);
            self::assertSame($rank, $member->Rank);
            self::assertSame($rank === null ? null : "https://example.com/{$image}.png", $member->RankIcon);
        }
    }
}

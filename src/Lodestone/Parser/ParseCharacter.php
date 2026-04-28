<?php

namespace Lodestone\Parser;

use Lodestone\Entity\Character\Attribute;
use Lodestone\Entity\Character\CharacterProfile;
use Lodestone\Entity\Character\GrandCompany;
use Lodestone\Entity\Character\Guardian;
use Lodestone\Entity\Character\Item;
use Lodestone\Entity\Character\ItemSimple;
use Lodestone\Entity\Character\Town;
use Lodestone\Http\Http;
use Rct567\DomQuery\DomQuery;
use Symfony\Component\HttpClient\CurlHttpClient;

class ParseCharacter extends ParseAbstract implements Parser
{
    use HelpersTrait;

    const TX_RACECLANGENDER = ['Race/Clan/Gender', 'Volk / Stamm / Geschlecht', 'Race / Ethnie / Sexe', '種族/部族/性別'];
    const TX_NAMEDAY        = ['Nameday', 'Guardian', 'Namenstag', 'Schutzgott', 'Date de naissance', 'Divinité', '誕生日', '守護神'];
    const TX_TOWN           = ['City-state', 'Stadtstaat', 'Cité de départ', '開始都市'];
    const TX_GRANDCOMPANY   = ['Grand Company', 'Staatliche Gesellschaft', 'Grande compagnie', '所属グランドカンパニー'];
    const GEAR_SLOT_MAP     = [
        0  => 'MainHand',
        1  => 'OffHand',
        2  => 'Head',
        3  => 'Body',
        4  => 'Hands',
        5  => 'Waist',
        6  => 'Legs',
        7  => 'Feet',
        8  => 'Earrings',
        9  => 'Necklace',
        10 => 'Bracelets',
        11 => 'Ring1',
        12 => 'Ring2',
        13 => 'SoulCrystal',
    ];
    
    /** @var CharacterProfile */
    private $profile;
    /** @var bool */
    private $fetchGear = false;
    /** @var array<string, string> */
    private $gearTooltips = [];
    /** @var string */
    private $requestBaseUri = Http::BASE_URI;
    /** @var array */
    private $requestHeaders = [];

    public function __construct(array $options = [])
    {
        $this->fetchGear = $options['fetch_gear'] ?? false;
        $this->gearTooltips = $options['gear_tooltips'] ?? [];
        $this->requestBaseUri = $options['request_base_uri'] ?? Http::BASE_URI;
        $this->requestHeaders = $options['request_headers'] ?? [];
    }
    
    /**
     * Handle Character parsing
     */
    public function handle(string $html)
    {
        // set dom
        $this->setDom($html);
        
        // set profile object
        $this->profile = new CharacterProfile();
        
        // parse main profile
        $this->parseProfile();
        $this->parseAttributes();
        if ($this->fetchGear) {
            $this->parseEquipGear();
        }

        return $this->profile;
    }
    
    /**
     * Parse the "Profile" tab
     */
    private function parseProfile()
    {
        $blocks = $this->dom->find('.character__profile__data__detail .character-block');
        
        /** @var DomQuery $block */
        foreach ($blocks as $block) {
            $blocktitle = $block->find('.character-block__title')->text();
    
            if (in_array($blocktitle, self::TX_RACECLANGENDER)) {
                $this->parseProfileRaceTribeGender($block);
            } elseif (in_array($blocktitle, self::TX_NAMEDAY)) {
                $this->parseProfileNameDay($block);
            } elseif (in_array($blocktitle, self::TX_TOWN)) {
                $this->parseProfileTown($block);
            } elseif (in_array($blocktitle, self::TX_GRANDCOMPANY)) {
                $this->parseProfileGrandCompany($block);
            } else {
                if ($block->find('.character__freecompany__name')->text() != "") {
                    $this->parseProfileFreeCompany($block);
                } elseif ($block->find('.character__pvpteam__name')->find('h4')->text() != "") {
                    $this->parseProfilePvPTeam($block);
                }
            }
        }
    
        $this->parseProfileBasic();
        $this->parseProfileBio();
    }
    
    /**
     * Parse the "Attributes" tab
     */
    private function parseAttributes()
    {
        //
        // Base Param
        //
        
        /** @var DomQuery $tr */
        foreach ($this->dom->find('.character__param__list tr') as $tr) {
            $attr        = new Attribute();
            $attr->Name  = $tr->find('th')->text();
            $attr->Value = $tr->find('td')->text();

            $this->profile->GearSet['Attributes'][] = $attr;
        }
        
        //
        // hp, mp, etc
        //
        /** @var DomQuery $li */
        foreach ($this->dom->find('.character__param ul li') as $li) {
            $attr        = new Attribute();
            $attr->Name  = $li->find('p')->text();
            $attr->Value = $li->find('span')->text();

            $this->profile->GearSet['Attributes'][] = $attr;
        }
    }
    
    /**
     * Parse the characters currently equipped gear
     */
    private function parseEquipGear()
    {
        $gearWrappers = $this->dom->find('.character__view')->eq(0)->find('.js__db_tooltip');
        if ($gearWrappers->length > 0) {
            $this->parseLazyTooltipGear($gearWrappers);
            return;
        }

        /**
         * @var int $i
         * @var DomQuery $node
         */
        foreach ($this->dom->find('.character__view')->eq(0)->find('.item_detail_box') as $i => $node) {
            $item = $this->parseGearItemNode($node, $i === 0 ? 'MainHand' : null);
            if ($item) {
                $this->profile->GearSet['Gear'][$item->Slot] = $item;
            }
        }
    }

    private function parseLazyTooltipGear(DomQuery $gearWrappers): void
    {
        $tooltipHtmlByUrl = $this->resolveTooltipHtmlByUrl($gearWrappers);

        /** @var DomQuery $wrapper */
        foreach ($gearWrappers as $wrapper) {
            $slot = $this->getSlotFromWrapper($wrapper);
            if ($slot === null) {
                continue;
            }

            $tooltipNode = $this->getInlineTooltipNode($wrapper);
            if ($tooltipNode === null) {
                $url = $wrapper->attr('data-lazy_load_url');
                $tooltipHtml = $tooltipHtmlByUrl[$url] ?? null;
                $tooltipNode = $tooltipHtml ? $this->createTooltipNode($tooltipHtml) : null;
            }

            if ($tooltipNode === null) {
                continue;
            }

            $item = $this->parseGearItemNode($tooltipNode, $slot);
            if ($item) {
                $this->profile->GearSet['Gear'][$item->Slot] = $item;
            }
        }
    }

    private function resolveTooltipHtmlByUrl(DomQuery $gearWrappers): array
    {
        $urls = [];

        /** @var DomQuery $wrapper */
        foreach ($gearWrappers as $wrapper) {
            if ($this->getInlineTooltipNode($wrapper) !== null) {
                continue;
            }

            $url = $wrapper->attr('data-lazy_load_url');
            if ($url) {
                $urls[$url] = $url;
            }
        }

        if (empty($urls)) {
            return [];
        }

        if (!empty($this->gearTooltips)) {
            return array_intersect_key($this->gearTooltips, $urls);
        }

        return $this->fetchTooltipHtml(array_values($urls));
    }

    private function fetchTooltipHtml(array $urls): array
    {
        $client = new CurlHttpClient([
            'base_uri' => $this->requestBaseUri ?: Http::BASE_URI,
            'timeout'  => Http::TIMEOUT,
        ]);

        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = $client->request('GET', $url, [
                'headers' => $this->requestHeaders,
            ]);
        }

        $content = [];
        foreach ($client->stream($responses) as $response => $chunk) {
            if (!$chunk->isLast()) {
                continue;
            }

            $url = array_search($response, $responses, true);
            if ($url === false || $response->getStatusCode() !== 200) {
                continue;
            }

            $content[$url] = $response->getContent();
        }

        return $content;
    }

    private function getInlineTooltipNode(DomQuery $wrapper): ?DomQuery
    {
        $node = $wrapper->find('.item_detail_box')->eq(0);
        if (!$node->length) {
            return null;
        }

        return trim((string) $node->find('.db-tooltip__item__name')->text()) !== '' ? $node : null;
    }

    private function createTooltipNode(string $html): ?DomQuery
    {
        $dom = new DomQuery($html);
        $node = $dom->find('.item_detail_box')->eq(0);

        if ($node->length) {
            return $node;
        }

        return $dom->length ? $dom : null;
    }

    private function getSlotFromWrapper(DomQuery $wrapper): ?string
    {
        $url = $wrapper->attr('data-lazy_load_url') ?? '';
        if (preg_match('/\/tooltip\/(\d+)$/', $url, $match)) {
            return self::GEAR_SLOT_MAP[(int) $match[1]] ?? null;
        }

        return null;
    }

    private function parseGearItemNode(DomQuery $node, ?string $slotHint = null): ?Item
    {
        $item = new Item();

        $name = $node->find('.db-tooltip__item__name')->text();
        if (!$name) {
            return null;
        }

        $item->Name = strip_tags($name);

        $lodestoneId = $node->find('.db-tooltip__bt_item_detail a')->attr('href');
        $explodedLodestoneId = explode('/', (string) $lodestoneId);
        $isFaceAccessory = false;
        $faceLink = null;
        if (count($explodedLodestoneId) < 2) {
            $faceLink = $node->find('.db-tooltip__item-info_faceaccessory a');
            $lodestoneId = $faceLink->attr('href');
            $explodedLodestoneId = explode('/', (string) $lodestoneId);
            if (count($explodedLodestoneId) >= 2) {
                $isFaceAccessory = true;
            }
        }

        if (count($explodedLodestoneId) < 2) {
            return null;
        }

        $item->ID = trim($explodedLodestoneId[count($explodedLodestoneId) - 2]);

        $category = $isFaceAccessory ? 'Miscellany' : $node->find('.db-tooltip__item__category')->text();
        $category = trim(strip_tags((string) $category));
        $catData = explode("'", $category);
        $catName = $catData[0];
        $catSecond = $catData[1] ?? '';
        $catName = trim(str_ireplace(['Two-handed', 'One-handed'], '', $catName));
        $catName = ucwords(strtolower($catName));
        $item->Category = $catName;

        $slot = $slotHint ?: ($isFaceAccessory ? 'Facewear' : $catName);
        if (!$slotHint) {
            $slot = (stripos($catSecond, 'secondary tool') !== false) ? 'OffHand' : $slot;
            $slot = ($slot === 'Shield') ? 'OffHand' : $slot;
            if ($slot === 'Ring') {
                $slot = isset($this->profile->GearSet['Gear']['Ring1']) ? 'Ring2' : 'Ring1';
            }
        }

        $item->Slot = str_ireplace(' ', '', $slot);

        $mirage = $node->find('.db-tooltip__item__mirage');
        if (trim((string) $mirage->html())) {
            $mirageId = $mirage->find('a')->attr('href');
            $mirageId = trim(explode('/', (string) $mirageId)[5] ?? '');

            $mirageItem = new ItemSimple();
            $mirageItem->ID = $mirageId;
            $mirageItem->Name = $mirage->find('p')->text();
            $item->Mirage = $mirageItem;
        }

        $creator = $node->find('.db-tooltip__signature-character');
        if (trim((string) $creator->html())) {
            $creator = explode("/", (string) $creator->find('a')->attr('href'));
            $item->Creator = trim($creator[3] ?? '');
        } elseif (trim((string) $node->find('.db-tooltip__info_text a')->text())) {
            $creator = explode("/", (string) $node->find('.db-tooltip__info_text a')->attr('href'));
            $item->Creator = trim($creator[3] ?? '');
        } elseif ($isFaceAccessory) {
            $item->Creator = trim((string) $faceLink->attr('data-tooltip'));
        }

        $dyes = $node->find('.stain');
        foreach ($dyes as $dye) {
            if (trim((string) $dye->html())) {
                $dyeUrl = $dye->find('a')->attr('href');
                $dyeName = $dye->find('a')->text();
                $dyeId = trim(explode("/", (string) $dyeUrl)[5] ?? '');

                $dyeObject = new ItemSimple();
                $dyeObject->ID = $dyeId;
                $dyeObject->Name = $dyeName;
                $item->Dye[] = $dyeObject;
            }
        }

        $materiaNodes = $node->find('.db-tooltip__materia');
        if (trim((string) $materiaNodes->html())) {
            if ($materiaNodes = $materiaNodes->find('li')) {
                /** @var DomQuery $mnode */
                foreach ($materiaNodes as $mnode) {
                    $mhtml = $mnode->find('.db-tooltip__materia__txt')->html();
                    if (!$mhtml) {
                        continue;
                    }

                    $mdetails = preg_split('/<br\s*\/?>/i', html_entity_decode($mhtml));
                    if (empty($mdetails[1])) {
                        $mdetails[1] = '';
                    }

                    $materiaObject = new ItemSimple();
                    $materiaObject->Name  = trim(strip_tags($mdetails[0]));
                    $materiaObject->Value = trim(strip_tags($mdetails[1]));
                    $item->Materia[] = $materiaObject;
                }
            }
        }

        return $item;
    }
    
    /**
     * Parse basic profile information (name, server, avatar, etc)
     */
    private function parseProfileBasic()
    {
        // id
        $lodestoneId = $this->dom->find('#character a')->attr('href') ?? '';
        $explodedLodestoneId = explode('/', $lodestoneId);
        $this->profile->ID = trim($explodedLodestoneId[3] ?? '');

        // name
        $name = $this->dom->find('.frame__chara__name')->eq(0)->html();
        $name = trim(strip_tags($name));
        $name = html_entity_decode($name, ENT_QUOTES, "UTF-8");
        $this->profile->Name = trim($name);
        
        // server
        [$server, $dc] = $this->getServerAndDc(
            $this->dom->find('.frame__chara__world')->eq(0)->text()
        );
    
        $this->profile->Server = $server;
        $this->profile->DC = $dc;
        
        // title
        if ($title = $this->dom->find('.frame__chara__title')) {
            $this->profile->Title = html_entity_decode(trim(strip_tags($title[0])), ENT_QUOTES, "UTF-8");
            $this->profile->TitleTop = $this->dom->find('.frame__chara .frame__chara__box p')->eq(0)->hasClass('frame__chara__title');
        }

        // avatar
        $avatar = $this->dom->find('#character .frame__chara__face img')->attr('src');
        $this->profile->Avatar   = $avatar;
        $this->profile->Portrait = str_ireplace('c0.jpg', 'l0.jpg', $avatar ?? '');

    }
    
    /**
     * Parse a players bio field
     */
    private function parseProfileBio()
    {
        $bio = $this->dom->find('.character__selfintroduction')->html();
        $bio = str_replace(['<br>', '<br />', '<br/>'], "\n", $bio);
        $bio = html_entity_decode($bio, ENT_QUOTES, "UTF-8");
        $bio = str_ireplace('Character Profile', '', $bio);
        
        if ($bio = strip_tags($bio)) {
            $this->profile->Bio = $bio;
        }
    
        $this->profile->Bio = mb_convert_encoding($this->profile->Bio, 'UTF-8', 'UTF-8');
    }

    /**
     * @param DomQuery $node
     */
    private function parseProfileRaceTribeGender($node)
    {
        $html = $node->find('.character-block__name')->html();
        $html = str_ireplace(['<br />', '<br>', '<br/>'], ' / ', $html);
        
        [$race, $tribe, $gender] = explode('/', strip_tags($html));
        
        $this->profile->Race   = strip_tags(trim($race));
        $this->profile->Tribe  = strip_tags(trim($tribe));
        $this->profile->Gender = strip_tags(trim($gender)) == '♀' ? 'female' : 'male';
    }
    
    /**
     * @param DomQuery $node
     */
    private function parseProfileNameDay($node)
    {
        $this->profile->Nameday = $node->find('.character-block__birth')->text();
        
        $obj = new Guardian();
        $obj->Name = html_entity_decode($node->find('.character-block__name')->text(), ENT_QUOTES, "UTF-8");
        $obj->Icon = $node->find('img')->attr('src');
        
        $this->profile->GuardianDeity = $obj;
    }
    
    /**
     * @param DomQuery $node
     */
    private function parseProfileTown($node)
    {
        $obj = new Town();
        $obj->Name = html_entity_decode($node->find('.character-block__name')->text(), ENT_QUOTES, "UTF-8");
        $obj->Icon = $node->find('img')->attr('src');
        $this->profile->Town = $obj;
    }
    
    /**
     * @param DomQuery $node
     */
    private function parseProfileGrandCompany($node)
    {
        $html = $node->find('.character-block__name')->html();
        
        // not all characters have a grand company
        [$name, $rank] = explode('/', strip_tags($html));
        
        $gc = new GrandCompany();
        $gc->Name = trim($name);
        $gc->Icon = $node->find('img')->attr('src');
        $gc->Rank = trim($rank);
        $this->profile->GrandCompany = $gc;
    }
    
    /**
     * @param DomQuery $node
     */
    private function parseProfileFreeCompany($node)
    {
        $this->profile->FreeCompanyId = $this->getLodestoneId($node);
        $this->profile->FreeCompanyName = trim($node->find('a')->text());
    }
    
    /**
     * @param DomQuery $node
     */
    private function parseProfilePvPTeam($node)
    {
        $this->profile->PvPTeamId = $this->getLodestoneId($node);
    }
}

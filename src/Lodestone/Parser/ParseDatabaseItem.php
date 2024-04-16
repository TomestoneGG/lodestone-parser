<?php

namespace Lodestone\Parser;

use Lodestone\Entity\Database\Item;

class ParseDatabaseItem extends ParseAbstract implements Parser
{
    use HelpersTrait;
    use ListTrait;

    public function handle(string $content): Item
    {
        // set dom
        $this->setDom($content, false, true);

        $item = new Item();

        $nameNode = $this->dom->find('.db-view__item__text__name');
        $imgNode = $this->dom->find('.db-view__item__icon__item_image');
        $item->ID = null;
        $item->Name = $nameNode ? trim($nameNode->text()) : "";
        $item->Icon = $imgNode ? explode('?', $imgNode->attr('src'))[0] : null;
        $item->ItemLevel = 0;
        $itemLevelNode = $this->dom->find('.db-view__item__item_level');
        $itemLevelText = $itemLevelNode?->text();
        if ($itemLevelText && str_starts_with($itemLevelText, "Item Level "))
            $item->ItemLevel = intval(filter_var(str_replace("Item Level ", "", $itemLevelText), FILTER_SANITIZE_NUMBER_INT));

        return $item;
    }
}

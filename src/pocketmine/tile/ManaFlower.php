<?php


namespace pocketmine\tile;


use LTItem\Mana\ManaSystem;
use pocketmine\utils\Utils;

class ManaFlower extends Tile
{
    const MAX_MANA = 1000;
    protected int $mana = 0;
    public function exportMana(){
        $manaCaches = ManaSystem::searchManaCache($this);
        Utils::vector3Sort($manaCaches, $this);
        $max = 100;
        /** @var \pocketmine\tile\ManaCache $manaCache */
        foreach ($manaCaches as $manaCache){
            if ($manaCache->getMana() < \pocketmine\tile\ManaCache::MAX_MANA){
                $m = min(\pocketmine\tile\ManaCache::MAX_MANA - $manaCache->getMana(), $max, $this->mana);
                $max -= $manaCache->enterMana($m, $this);
            }
            if ($max <= 0)break;
        }
        $this->mana -= 100 - $max;
    }
}
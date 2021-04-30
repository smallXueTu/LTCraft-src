<?php


namespace pocketmine\tile;


use LTItem\Mana\ManaSystem;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\Utils;

class ManaFlower extends Tile
{
    const MAX_MANA = 1000;
    protected int $mana = 0;
    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        if (isset($nbt['mana']))$this->mana = (int)$nbt['mana'];
    }

    public function exportMana(){
        if ($this->mana <= 0)return;
        $manaCaches = ManaSystem::searchManaCache($this);
        Utils::vector3Sort($manaCaches, $this);
        $max = 100;
        /** @var \pocketmine\tile\ManaCache $manaCache */
        foreach ($manaCaches as $manaCache){
            if ($manaCache->getMana() < \pocketmine\tile\ManaCache::MAX_MANA){
                $m = min(\pocketmine\tile\ManaCache::MAX_MANA - $manaCache->getMana(), $max, $this->mana);
                $manaCache->enterMana($m, $this);
                $max -= $m;
            }
            if ($max <= 0 or $this->mana <= 0)break;
        }
        $this->mana -= 100 - $max;
    }

    public function saveNBT()
    {
        parent::saveNBT();
        $this->namedtag->mana = new IntTag("mana", $this->mana);
    }

    /**
     * @return int
     */
    public function getMana(): int
    {
        return $this->mana;
    }
}
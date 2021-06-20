<?php


namespace pocketmine\tile;


use pocketmine\block\Air;
use pocketmine\block\Cobblestone;
use pocketmine\block\Stone;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;

class ShiZhongji extends ManaFlower
{

    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        // $this->scheduleUpdate();//加入更新队列
        $this->lastUpdate = 0;
    }

    public function onUpdate()
    {
        if (Server::getInstance()->getTick() - $this->lastUpdate > 10){
            $this->lastUpdate = Server::getInstance()->getTick();
            $blocks = [];
            $blocks[] = $this->level->getBlock($this->add(1));
            $blocks[] = $this->level->getBlock($this->add(-1));
            $blocks[] = $this->level->getBlock($this->add(0, 0, 1));
            $blocks[] = $this->level->getBlock($this->add(0, 0, -1));
            if ($this->mana < self::MAX_MANA)foreach ($blocks as $block){
                if ($block instanceof Stone or $block instanceof Cobblestone){
                    $this->mana += min(1, self::MAX_MANA - $this->mana);
                    $this->level->setBlock($block, new Air());
                }
                if ($this->mana >= self::MAX_MANA)break;
            }
            $this->exportMana();
        }
        return true;
    }
}
<?php


namespace pocketmine\inventory;


use pocketmine\block\Air;
use pocketmine\Player;

class ManaInventory extends ChestInventory
{
    public function close(Player $who)
    {
        if (!($this->getHolder()->getBlock() instanceof Air)) {
            $this->getHolder()->getBlock()->close($who);
        }
        parent::close($who);
    }
}
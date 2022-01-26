<?php

namespace LTItem\SpecialItems\Material;

use LTItem\Main;
use LTItem\SpecialItems\Material;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class Character extends Material
{

    /**
     * @param Level $level
     * @param Player $player
     * @param Block $block
     * @param Block $target
     * @param $face
     * @param $fx
     * @param $fy
     * @param $fz
     * @return bool|void
     */
    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz)
    {
        if($level->getName()=='pve' and $target->getX()==-287 and $target->getY()==102 and $target->getZ()==455) {
            $Craft = "LCraft";
            $rand=mt_rand(0, strlen($Craft));
            $character = $Craft[$rand];
            $item = Main::getInstance()->createMaterial("§d" . $character);
            $this->count--;
            $player->getInventory()->addItem($item);
            $player->sendMessage('§e§l哇 你打开神秘字符获得了:'.$item->getLTName());
            $player->getServer()->BroadCastMessage('§l§e恭喜玩家'.$player->getName().'打开'.$this->getLTName().'获得了:'.Item::getItemString($item));
        }else{
            return parent::onActivate($level, $player, $block, $target, $face, $fx, $fy, $fz);
        }
        return true;
    }
}
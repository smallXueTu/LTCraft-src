<?php


namespace LTItem\SpecialItems\Material;


use LTItem\Cooling;
use pocketmine\Player;

class MagicStick extends \LTItem\SpecialItems\Material
{

    public function getHandMessage(Player $player): string
    {
        if(isset(Cooling::$material[$player->getName()][$this->getLTName()]) and Cooling::$material[$player->getName()][$this->getLTName()]>time())
            return parent::getHandMessage($player)."\n".'§c技能剩余时间:'.ceil(Cooling::$material[$player->getName()][$this->getLTName()]-time()).'秒';
        else
            return parent::getHandMessage($player)."\n".'§d点击地面可释放技能';
    }
}
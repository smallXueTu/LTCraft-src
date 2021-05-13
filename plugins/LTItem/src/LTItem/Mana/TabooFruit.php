<?php
namespace LTItem\Mana;

use pocketmine\Player;

/**
 * 禁忌之果
 * 消耗魔力恢复饱食度
 * Class TabooFruit
 * @package LTItem\Mana
 */
class TabooFruit extends ManaFood{

    /**
     * @return bool
     */
    public function canBeConsumed() : bool{
        return false;
    }

    /**
     * 玩家正在啃苹果
     * @param Player $player
     */
    public function eatIng(Player $player)
    {
        //增加饱食度 消耗魔力
        $mana = $player->getBuff()->getMana();
        if ($mana > 30 and $player->getBuff()->consumptionMana(10)){
            $player->addSaturation(0.5);
            $player->addFood(1);
            parent::eatIng($player);
        }
    }
}
<?php


namespace LTItem\Mana;


use LTItem\Main;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class FateDice extends BaseMana
{
    public function canBeActivated(): bool
    {
        return true;
    }

    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz)
    {
		if(!$player->isSneaking()){
			$player->sendMessage($this->getUseMessage());
			return true;
		}
        if(!$this->canUse($player)){
            return true;
        }
        $all = [1, 2, 3, 4, 5, 6];
        foreach ($all as $i=>$s){
            if($player->getAStatusIsDone('FateDice'.$s)){
                unset($all[$i]);
            }
        }
        if (count($all) == 0){
//            $player->sendMessage("§c骰子落在了".mt_rand(1, 6)."，接着消失不见了。");
            $player->getInventory()->setItemInHand(self::randItem());
            return true;
        }
        $side = $all[array_rand($all)];
        $item = Item::get(0);
        switch ($side){
            case 1:
                $item = Main::getInstance()->createMana("禁忌之果", $player);
            break;
            case 2:
                $item = Main::getInstance()->createMana("王者之剑", $player);
            break;
            case 3:
                $item = Main::getInstance()->createMana("天翼族之眼", $player);
            break;
            case 4:
                $item = Main::getInstance()->createMana("托尔之戒", $player);
            break;
            case 5:
                $item = Main::getInstance()->createMana("奥丁之戒", $player);
            break;
            case 6:
                $item = Main::getInstance()->createMana("天翼族之冠", $player);
            break;
        }
        $player->getInventory()->setItemInHand($item);
        $player->addAStatus('FateDice'.$side);
        $player->sendMessage("§c骰子落在了".$side."，发现他变成了另一件物品。");
        return true;
    }
    public function canPutMana(): bool
    {
        return false;
    }

    /**
     * 随机一个物品
     * @return Item
     */
    public function randItem(): Item{
        $rand = mt_rand(0, 99);
        $item = Item::get(0);
        switch (true){
            case $rand >= 90:
                $item = Main::getInstance()->createMaterial("挑战券");
                break;
            case $rand >= 85:
                $item = Main::getInstance()->createMaterial("幻影药水");
                break;
            case $rand >= 60:
                $item = Main::getInstance()->createMaterial("圣布");
                break;
            case $rand >= 50:
                $item = Main::getInstance()->createMaterial("泰拉钢锭");
                break;
            case $rand >= 30:
                $item = Item::get(265, 0, mt_rand(1, 10));
                break;
            case $rand >= 15:
                $item = Item::get(266, 0, mt_rand(1, 10));
                break;
            case $rand >= 5:
                $item = Item::get(264, 0, mt_rand(1, 5));
                break;
            case $rand < 5:
                $item = Item::get(263, 0, mt_rand(5, 32));
                break;
        }
        return $item;
    }
}
<?php


namespace LTItem\SpecialItems\Material;


use LTItem\Main;
use LTItem\SpecialItems\Material;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class BraveManWeaponGiftBag extends Material
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
            $player->getTask()->action('开启神秘礼包', $this->getLTName());
            $rand = mt_rand(1, 100);
            $item = Item::get(0);
            switch (true) {
                case $rand >= 90:
                    $item = Main::getInstance()->createMaterial('高级武器经验水晶');
                    $item->setCount(5);
                    break;
                case $rand >= 70:
                    $item = Main::getInstance()->createWeapon('近战', '远古战刃', $player);
                    break;
                case $rand >= 40:
                    $item = Main::getInstance()->createWeapon('近战', '碧玉刃', $player);
                    break;
                case $rand >= 20:
                    $item = Main::getInstance()->createWeapon('近战', '天魔化血神刀', $player);
                    break;
                case $rand >= 0:
                    $item = Main::getInstance()->createWeapon('近战', '符文之刃', $player);
                    break;
            }
            $this->count--;
            $player->getInventory()->addItem($item);
            $player->sendMessage('§e§l哇 你打开勇者武器礼包获得了:' . $item->getLTName());
            $player->getServer()->BroadCastMessage('§l§e恭喜玩家' . $player->getName() . '打开' . $this->getLTName() . '获得了:' . Item::getItemString($item));
        }else{
            return parent::onActivate($level, $player, $block, $target, $face, $fx, $fy, $fz);
        }
		return true;
    }
}
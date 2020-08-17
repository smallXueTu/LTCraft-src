<?php


namespace LTItem\SpecialItems\Material;


use LTItem\Main;
use LTItem\SpecialItems\Material;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class MysteriousPetPieces extends Material
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
                case $rand <= 100:
                    $item = Main::getInstance()->createMaterial('S级宠物碎片');
                    break;
                case $rand <= 90:
                    $item = Main::getInstance()->createMaterial('A级宠物碎片');
                    break;
                case $rand <= 85:
                    $item = Main::getInstance()->createMaterial('B级宠物碎片');
                    break;
                case $rand <= 65:
                    $item = Main::getInstance()->createMaterial('C级宠物碎片');
                    break;
                case $rand <= 35:
                    $item = Main::getInstance()->createMaterial('D级宠物碎片');
                    break;
                case $rand <= 5:
                    $item = Item::get(322);
                    break;
            }
            $player->getInventory()->addItem($item);
            $player->getServer()->BroadCastMessage('§l§e恭喜玩家' . $player->getName() . '打开' . $this->getLTName() . '获得了:' . Item::getItemString($item));
            $this->count--;
            $player->sendMessage('§e§l哇 你打开神秘宠物碎片获得了:' . $item->getLTName());
        }else{
            return parent::onActivate($level, $player, $block, $target, $face, $fx, $fy, $fz);
        }
		return true;
    }
}
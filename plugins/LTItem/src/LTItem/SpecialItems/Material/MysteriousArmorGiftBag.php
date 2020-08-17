<?php


namespace LTItem\SpecialItems\Material;


use LTItem\Main;
use LTItem\SpecialItems\Material;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class MysteriousArmorGiftBag extends Material
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
            $rand=mt_rand(1, 100);
            $item=Item::get(0);
            switch(true){
                case $rand>=95:
                    $item=Main::getInstance()->createArmor('远古战靴', $player);
                    break;
                case $rand>=90:
                    $item=Main::getInstance()->createArmor('龙鳞之帽', $player);
                    break;
                case $rand>=85:
                    $item=Main::getInstance()->createArmor('远古头盔', $player);
                    break;
                case $rand>=80:
                    $item=Main::getInstance()->createArmor('龙鳞之铠', $player);
                    break;
                case $rand>=72:
                    $item=Main::getInstance()->createArmor('冰雪膝', $player);
                    break;
                case $rand>=64:
                    $item=Main::getInstance()->createArmor('密室熔炼靴', $player);
                    break;
                case $rand>=56:
                    $item=Main::getInstance()->createArmor('密室熔炼护膝', $player);
                    break;
                case $rand>=48:
                    $item=Main::getInstance()->createArmor('冰雪帽', $player);
                    break;
                case $rand>=40:
                    $item=Main::getInstance()->createArmor('冰雪靴', $player);
                    break;
                case $rand>=35:
                    $item=Main::getInstance()->createArmor('龙之甲', $player);
                    break;
                case $rand>=29:
                    $item=Main::getInstance()->createArmor('异界之膝', $player);
                    break;
                case $rand>=24:
                    $item=Main::getInstance()->createArmor('异界之甲', $player);
                    break;
                case $rand>=18:
                    $item=Main::getInstance()->createArmor('符文之膝', $player);
                    break;
                case $rand>=14:
                    $item=Main::getInstance()->createArmor('灭世者之帽', $player);
                    break;
                case $rand>=8:
                    $item=Main::getInstance()->createArmor('灭世者之甲', $player);
                    break;
                case $rand>=0:
                    $item=Main::getInstance()->createArmor('冰雪甲', $player);
                    break;
            }
            $this->count--;
            $player->getInventory()->addItem($item);
            $player->sendMessage('§e§l哇 你打开神秘盔甲礼包获得了:'.$item->getLTName());
            $player->getServer()->BroadCastMessage('§l§e恭喜玩家'.$player->getName().'打开'.$this->getLTName().'获得了:'.Item::getItemString($item));
        }else{
            return parent::onActivate($level, $player, $block, $target, $face, $fx, $fy, $fz);
        }
		return true;
    }
}
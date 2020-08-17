<?php


namespace LTItem\SpecialItems\Material;


use LTItem\Main as LTItem;
use pocketmine\item\Item;
use pocketmine\Player;

class Drawings extends \LTItem\SpecialItems\Material
{
    /**
     * 开始合成
     * @param Player $player
     * @return Item
     */
    public function onSynthetic(Player $player): Item{
        switch ($this->getLTName()){
            case '史诗武器图纸':
                $rand=mt_rand(1, 100);//随机一个1-100的数字
                if($rand>90){//如果这个数字大于90
                    $weapon=LTItem::getInstance()->createWeapon('近战', '烈火金箍棒', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'熔炼史诗武器获得了烈火金箍棒!!');
                }elseif($rand>70){//如果这个数字大于70
                    $weapon=LTItem::getInstance()->createWeapon('近战', '村正妖刀', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'熔炼史诗武器获得了村正妖刀!!');
                }elseif($rand>40){//如果这个数字大于40
                    $weapon=LTItem::getInstance()->createWeapon('近战', '诸神狱焰剑', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'熔炼史诗武器获得了诸神狱焰剑!!');
                }elseif($rand>10){//如果这个数字大于10
                    $weapon=LTItem::getInstance()->createWeapon('近战', '盘古斧', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'熔炼史诗武器获得了盘古斧!!');
                }else{//否者
                    $player->sendMessage('§l§e[提示]§d哎呀!!你合成史诗武器失败了,别灰心,重写获得合成需要武器吧！');
                    $player->sendMessage('§l§e偷偷告诉你哦：熔炼残渣可以融合更好的武器呢！');
                    $weapon=LTItem::getInstance()->createMaterial('熔炼残渣');
                    $weapon->setCount(3);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]恭喜玩家合成史诗武器失败了！ 获得了熔炼残渣×3');
                }
                return $weapon;
            break;
            case '终极武器图纸':
                $rand=mt_rand(1, 100);
                if($rand>96){
                    $weapon=LTItem::getInstance()->createWeapon('近战', '万人斩-冥界的咆哮', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成终极武器获得了万人斩-冥界的咆哮!!');
                }elseif($rand>89){
                    $weapon=LTItem::getInstance()->createWeapon('近战', '死亡之舞', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成终极武器获得了死亡之舞!!');
                }elseif($rand>80){
                    $weapon=LTItem::getInstance()->createWeapon('近战', '尼尔巴斯的饮血镰', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成终极武器获得了尼尔巴斯的饮血镰!!');
                }elseif($rand>65){
                    $weapon=LTItem::getInstance()->createWeapon('近战', '死亡黑狼图腾', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成终极武器获得了死亡黑狼图腾!!');
                }elseif($rand>40){
                    $weapon=LTItem::getInstance()->createWeapon('近战', '流星锤', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成终极武器获得了流星锤!!');
                }elseif($rand>10){
                    $weapon=LTItem::getInstance()->createWeapon('近战', '幽灵手弩', $player);
                    $player->sendMessage('§l§e[警告]§a你的武器质量过低，推荐你再次合成一个！');
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成终极武器获得了死亡幽灵手弩!!');
                }else{
                    $weapon=LTItem::getInstance()->createWeapon('近战', '流光星陨刃', $player);
                    $player->sendMessage('§l§e[警告]§a你的武器质量过低，推荐你再次合成一个！');
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成终极武器获得了流光星陨刃!!');
                }
                return $weapon;
            break;
            case '史诗头盔图纸':
                $rand=mt_rand(1, 30);
                if($rand>20){
                    $armor=LTItem::getInstance()->createArmor('虎头皂金帽', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗头盔获得了虎头皂金帽!!');
                }elseif($rand>10){
                    $armor=LTItem::getInstance()->createArmor('赤金之帽', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗头盔获得了赤金之帽!!');
                }else{
                    $armor=LTItem::getInstance()->createArmor('敖龙银帽', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗头盔获得了敖龙银帽!!');
                    // $player->sendMessage('§l§e[警告]§a你的盔甲质量过低，推荐你再次合成一个！');
                }
                return $armor;
            break;
            case '史诗胸甲图纸':
                $rand=mt_rand(1, 30);
                if($rand>20){
                    $armor=LTItem::getInstance()->createArmor('虎头皂金甲', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗胸甲获得了虎头皂金甲!!');
                }elseif($rand>10){
                    $armor=LTItem::getInstance()->createArmor('赤金之甲', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗胸甲获得了赤金之甲!!');
                }else{
                    $armor=LTItem::getInstance()->createArmor('敖龙银甲', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗胸甲获得了敖龙银帽!!');
                    // $player->sendMessage('§l§e[警告]§a你的盔甲质量过低，推荐你再次合成一个！');
                }
                return $armor;
            break;
            case '史诗护膝图纸':
                $rand=mt_rand(1, 30);
                if($rand>20){
                    $armor=LTItem::getInstance()->createArmor('虎头皂金膝', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗护膝获得了虎头皂金膝!!');
                }elseif($rand>10){
                    $armor=LTItem::getInstance()->createArmor('赤金之膝', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗护膝获得了赤金之膝!!');
                }else{
                    $armor=LTItem::getInstance()->createArmor('敖龙银膝', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗护膝获得了敖龙银膝!!');
                    // $player->sendMessage('§l§e[警告]§a你的盔甲质量过低，推荐你再次合成一个！');
                }
                return $armor;
            break;
            case '史诗战靴图纸':
                $rand=mt_rand(1, 30);
                if($rand>20){
                    $armor=LTItem::getInstance()->createArmor('虎头皂金靴', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史战靴盔获得了虎头皂金靴!!');
                }elseif($rand>10){
                    $armor=LTItem::getInstance()->createArmor('赤金之靴', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗战靴获得了赤金之靴!!');
                }else{
                    $armor=LTItem::getInstance()->createArmor('敖龙银靴', $player);
                    $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]玩家'.$player->getName().'合成史诗战靴获得了敖龙银靴!!');
                    // $player->sendMessage('§l§e[警告]§a你的盔甲质量过低，推荐你再次合成一个！');
                }
                return $armor;
            break;
            default:
                return Item::get(0);
            break;
        }
        return Item::get(0);
    }
}
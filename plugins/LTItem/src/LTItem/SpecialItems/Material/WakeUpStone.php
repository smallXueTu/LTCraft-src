<?php
namespace LTItem\SpecialItems\Material;


use LTItem\SpecialItems\Material;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\Player;

class WakeUpStone extends Material
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
     * @return bool
     */
    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz)
    {
        // var_dump("a");
        if($level->getName()=='pve' and $target->getX()==-287 and $target->getY()==102 and $target->getZ()==455) {
            if($player->getGeNeAwakening()>=3){
                $player->sendMessage('§l§c你的职业基因已经觉醒三层了！');
                return true;
            }
            $this->count--;
            if(!mt_rand(0, 19)){
                $player->setGeNeAwakening($player->getGeNeAwakening()+1);
                $player->getServer()->broadcastMessage('§l§a[LTcraft全服公告]恭喜玩家'.$player->getName().'成功觉醒醒职业基因!!');
            }else{
                $player->sendMessage('§l§c哎呀 觉醒失败 再试一次吧~');
            }
        }else{
            return parent::onActivate($level, $player, $block, $target, $face, $fx, $fy, $fz);
        }
		return true;
    }
}
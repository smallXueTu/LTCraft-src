<?php


namespace LTItem\Mana;


use LTMenu\Open;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\inventory\TravelingInventory;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

/**
 * 旅行背包
 * 目前功能已实现 但不确定会不会出现bug
 * TODO: 优化 && 检查代码 保证不会出现BUG
 * Class TravelingBackpack
 * @package LTItem\Mana
 */
class TravelingBackpack extends BaseMana
{
    public function __construct(array $conf, int $count, CompoundTag $nbt, $init = true)
    {
        parent::__construct($conf, $count, $nbt, $init);
        $nbt = $this->getNamedTag();
        if (!isset($nbt['items'])){
            $nbt->items = new ListTag('items', []);
            $nbt->items->setTagType(NBT::TAG_Compound);
            $this->setNamedTag($nbt);
        }
    }

    public function canBeActivated(): bool
    {
        return true;
    }
    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz)
    {
        $this->openInventory($player, $player->getInventory()->getHeldItemIndex());
    }

    /**
     * @param Player $player
     * @param int $index
     */
    public function openInventory(Player $player, int $index)
    {
        $block=$player->getLevel()->getBlock($player);
        if(!($block instanceof Air)){
            $block=$player->getLevel()->getBlock(new Vector3($player->x,$player->y+1,$player->z));
            if(!($block instanceof Air))$block=$this->getAir($player);
        }
        if(!($block instanceof Block)){
            $player->sendMessage('§c附近找不到空位置！');
            return;
        }
        $closePK = Open::getOpenBlock($block, $player, "旅行背包");
        $nbt = $this->getNamedTag();
        $inventory = new TravelingInventory($player, $nbt->items, $index, $block);
        $inventory->setClosePK($closePK);
        $player->addWindow($inventory);
    }
    public function getAir(Player $player){
        $x=(int)$player->getX()+3;
        $y=(int)$player->getY()+3;
        $z=(int)$player->getZ()+3;
        $level=$player->getLevel();
        for($fx=$x - 6;$fx<=$x;$fx++){
            for($fy=$y - 6;$fy<=$y;$fy++){
                for($fz=$z - 6;$fz<=$z;$fz++){
                    $block=$level->getBlock(new Vector3($fx,$fy,$fz));
                    if($block instanceof Air)return $block;
                }
            }
        }
        return false;
    }
}
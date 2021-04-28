<?php


namespace LTEntity\entity\Mana;


use pocketmine\block\Block;
use pocketmine\block\LiveWood;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Entity;

class FairyGate extends Entity
{
    private static array $liveWoodsPos = [
        [-1, 0],
        [-2, 1],
        [-2, 3],
        [-1, 4],
        [1, 0],
        [2, 1],
        [2, 3],
        [1, 4],
    ];
    private static array $glimmerLiveWoodsPos = [
        [0, 0],
        [-2, 2],
        [2, 2],
        [0, 4],
    ];
    /**
     * FairyGate constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
    }

    /**
     * @param Block $coreBlock
     * @return bool
     */
    public static function checkBlocks(Block $coreBlock): bool{
        $level = $coreBlock->getLevel();
        $cx = 0;//x延伸
        if ($level->getBlock($coreBlock->add(1)) instanceof LiveWood and $level->getBlock($coreBlock->add(-1))){//x
            $cx = 1;
        }elseif ($level->getBlock($coreBlock->add(0, 0, 1)) instanceof LiveWood and $level->getBlock($coreBlock->add(0, 0, -1))){//z
            $cx = 2;
        }
        if ($cx == 0)return false;
        foreach (self::$liveWoodsPos as $liveWoodsPos){
            $block = self::getBlock($coreBlock, $cx, $liveWoodsPos[0], $liveWoodsPos[1]);
            if (!($block instanceof LiveWood) or $block->getDamage() !=0){
                return false;
            }
        }
        foreach (self::$glimmerLiveWoodsPos as $glimmerLiveWoodsPos){
            $block = self::getBlock($coreBlock, $cx, $glimmerLiveWoodsPos[0], $glimmerLiveWoodsPos[1]);
            if (!($block instanceof LiveWood) or $block->getDamage() !=7){
                return false;
            }
        }
        return true;
    }

    /**
     * @param $pos Position
     * @param $ys int 延伸 1 = x;2 = z;
     * @param int $ysz 延伸范围
     * @param int $y
     * @return Block
     */
    public static function getBlock(Position $pos, int $ys, int $ysz, int $y): Block
    {
        if ($ys == 1){
            return $pos->getLevel()->getBlock($pos->add($ysz, $y));
        }elseif($ys == 2){
            return $pos->getLevel()->getBlock($pos->add(0, $y, $ysz));
        }
        return Block::get(0);
    }
}
<?php


namespace LTEntity\entity\Mana;


use pocketmine\block\Block;
use pocketmine\block\LiveWood;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

class FairyGate extends Entity
{
    const X_EXTEND = 1;
    const Z_EXTEND = 2;
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
    public int $towards;

    /**
     * FairyGate constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param int $towards 朝向
     */
    public function __construct(Level $level, CompoundTag $nbt, int $towards)
    {
        $this->towards = $towards;
        parent::__construct($level, $nbt);
    }
    protected function initEntity()
    {
        
        parent::initEntity();
    }
    public static function getTowards(Block $coreBlock, int $towards): int{
        $level = $coreBlock->getLevel();
        if ($level->getBlock($coreBlock->add(1)) instanceof LiveWood or $level->getBlock($coreBlock->add(-1))){//x
            $towards = self::X_EXTEND;
        }elseif ($level->getBlock($coreBlock->add(0, 0, 1)) instanceof LiveWood or $level->getBlock($coreBlock->add(0, 0, -1))){//z
            $towards = self::Z_EXTEND;
        }
        return $towards;
    }
    /**
     * @param Block $coreBlock
     * @param Player $player
     * @return array
     */
    public static function checkBlocks(Block $coreBlock, Player $player): array{
        $level = $coreBlock->getLevel();
        $yaw = $player->getYaw();
        if (($yaw <= 45 or $yaw > 315) or ($yaw >= 135 and $yaw < 225))
            $towards = self::X_EXTEND;//x延伸
        else
            $towards = self::Z_EXTEND;
        $towards = self::getTowards($coreBlock, $towards);
        $blocks = self::getBlocks($coreBlock, $towards);
        /** @var Block $block */
        foreach ($blocks as $index => $block){
            $b = $level->getBlock($block);
            if ($b->getId()== $block->getId() and $b->getDamage() == $block->getDamage())unset($blocks[$index]);
        }
        return $blocks;
    }
    public static function getBlocks(Block $coreBlock, $towards = self::X_EXTEND):array {
        $blocks = [];
        foreach (self::$liveWoodsPos as $liveWoodsPos){
            $block = new LiveWood(0);
            $pos = self::getPPosition($coreBlock, $towards, $liveWoodsPos[0], $liveWoodsPos[1]);
            $block->setComponents($pos->x, $pos->y, $pos->z);
            $blocks[] = $block;
        }
        foreach (self::$glimmerLiveWoodsPos as $glimmerLiveWoodsPos){
            $block = new LiveWood(7);
            $pos = self::getPPosition($coreBlock, $towards, $glimmerLiveWoodsPos[0], $glimmerLiveWoodsPos[1]);
            $block->setComponents($pos->x, $pos->y, $pos->z);
            $blocks[] = $block;
        }
        return $blocks;
    }
    /**
     * @param $pos Position
     * @param $towards int 延伸 1 = x;2 = z;
     * @param int $ysz 延伸范围
     * @param int $y
     * @return Block
     */
    public static function getBlock(Position $pos, int $towards, int $ysz, int $y): Block
    {
        return $pos->getLevel()->getBlock(self::getPPosition($pos, $towards, $ysz, $y));
    }
    /**
     * @param $pos Position
     * @param $towards int 延伸 1 = x;2 = z;
     * @param int $ysz 延伸范围
     * @param int $y
     * @return Position
     */
    public static function getPPosition(Position $pos, int $towards, int $ysz, int $y): Position{
        return $towards == self::X_EXTEND?$pos->add($ysz, $y):$pos->add(0, $y, $ysz);
    }
    public function saveNBT()
    {
        $this->namedtag->id = new StringTag("id", $this->getSaveId());
        $this->namedtag->Pos = new ListTag("Pos", [
            new DoubleTag(0, $this->x),
            new DoubleTag(1, $this->y),
            new DoubleTag(2, $this->z)
        ]);
    }
}
<?php


namespace LTEntity\entity\Mana;


use LTItem\Mana\ManaSystem;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\LiveWood;
use pocketmine\block\StillWater;
use pocketmine\block\Water;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\ManaCache;
use pocketmine\utils\Utils;

class FairyGate extends Entity
{
    const DEFAULT_EXTEND = 0;
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
    private static array $gates = [
        [0, 0],
        [1, 0],
        [-1, 0],
        [1, 1],
        [-1, 1],
        [0, 1],
        [0, 2],
        [1, 2],
        [-1, 2],
    ];
    public int $towards;
    public ?Block $coreBlock;
    private bool $flash = true;
    private bool $crash = false;

    /**
     * FairyGate constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param int $towards 朝向
     * @param Block|null $coreBlock
     */
    public function __construct(Level $level, CompoundTag $nbt, int $towards = self::DEFAULT_EXTEND, ?Block $coreBlock = null)
    {
        $this->towards = $towards;
        $this->coreBlock = $coreBlock;
        parent::__construct($level, $nbt);
        $start = self::getPPosition($this, $this->towards, -1, 0);
        $end = self::getPPosition($this, $this->towards, 1, 2);
        $this->boundingBox = new AxisAlignedBB($start->x, $start->y, $start->z, $end->x, $end->y, $end->z);
    }
    public function onUpdate($currentTick)
    {
        if ($this->age % 20 == 0) {
            if (count(self::checkBlocks($this->coreBlock, null, $this->towards)) > 0) {
                $this->kill();
                return false;
            }
        }
        if ($this->age < 20 * 10 + 10){
            if ($this->age % 10 == 0){
                if ($this->flash)
                    $this->constructGates();
                else
                    $this->destroyGates();
                $this->flash = !$this->flash;
                if (!$this->siphonMana(100)){
                    $this->crash = true;
                }
            }
        }elseif ($this->age % 20 == 0){
            if ($this->crash or !$this->checkGates()){
                $this->kill();
                return false;
            }
            foreach ($this->getLevel()->getCollidingEntities($this->boundingBox) as $entity){
                if ($entity instanceof Player and $entity->canSelected()){
                    $level = $this->getServer()->getLevelByName("f10");
                    $entity->teleport($level->getSpawnLocation());
                }
            }
            if (!$this->siphonMana(10)){
                $this->kill();
                return false;
            }
        }
        $this->age++;
        return true;
    }
    public function siphonMana(int $mana): bool
    {
        $searchManaCache = ManaSystem::searchManaCache($this);//搜索附近魔力缓存器来抽取魔力
        Utils::vector3Sort($searchManaCache, $this);
        foreach ($searchManaCache as $manaCeche){
            /** @var ManaCache $manaCeche */
            $m = min($mana, $manaCeche->getMana());
            if ($manaCeche->putMana($m, $this)){//抽取10 Mana
                $mana -= $m;
            }
            if ($mana <= 0)break;
        }
        return $mana <= 0;
    }
    public function checkGates(){
        $coreBlock = $this->coreBlock->add(0, 1);
        foreach (self::$gates as $gate){
            $block = self::getBlock($coreBlock, $this->towards, $gate[0], $gate[1]);
            if (!($block instanceof StillWater)){
                return false;
            }
        }
        return true;
    }
    public function constructGates(){
        $coreBlock = $this->coreBlock->add(0, 1);
        foreach (self::$gates as $gate){
            $block = self::getBlock($coreBlock, $this->towards, $gate[0], $gate[1]);
            if (!($block instanceof StillWater)){
                $this->level->setBlock($block, new StillWater());
            }
        }
    }
    public function destroyGates(){
        $coreBlock = $this->coreBlock->add(0, 1);
        foreach (self::$gates as $gate){
            $block = self::getBlock($coreBlock, $this->towards, $gate[0], $gate[1]);
            $this->level->setBlock($block, new Air());
        }
    }
    public function kill()
    {
        $this->close();
        $this->destroyGates();
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
     * @param Player|null $player
     * @param int $towards
     * @return array
     */
    public static function checkBlocks(Block $coreBlock, Player $player = null, int $towards = self::DEFAULT_EXTEND): array{
        $level = $coreBlock->getLevel();
        if ($towards == self::DEFAULT_EXTEND){
            $yaw = $player->getYaw();
            if (($yaw <= 45 or $yaw > 315) or ($yaw >= 135 and $yaw < 225))
                $towards = self::X_EXTEND;//x延伸
            else
                $towards = self::Z_EXTEND;
            $towards = self::getTowards($coreBlock, $towards);
        }
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
        $this->namedtag->towards = new IntTag("towards", $this->towards);
        $this->namedtag->coreBlock = new ListTag("coreBlock", [
            new DoubleTag(0, $this->coreBlock->x),
            new DoubleTag(1, $this->coreBlock->y),
            new DoubleTag(2, $this->coreBlock->z)
        ]);
        $this->namedtag->Pos = new ListTag("Pos", [
            new DoubleTag(0, $this->x),
            new DoubleTag(1, $this->y),
            new DoubleTag(2, $this->z)
        ]);
    }
    protected function initEntity()
    {
        if (isset($this->namedtag["coreBlock"])){
            $pos = new Position(
                $this->namedtag["coreBlock"][0],
                $this->namedtag["coreBlock"][1],
                $this->namedtag["coreBlock"][2],
                $this->level
            );
            $this->coreBlock = $this->level->getBlock($pos);
        }
        if (isset($this->namedtag["towards"]))$this->towards = (int)$this->namedtag["towards"];
    }
}
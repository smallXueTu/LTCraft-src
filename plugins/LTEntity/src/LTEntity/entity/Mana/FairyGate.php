<?php


namespace LTEntity\entity\Mana;


use LTEntity\Main;
use LTItem\Mana\ManaSystem;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\LiveWood;
use pocketmine\block\StillWater;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\ManaCache;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;

class FairyGate extends Entity implements ChunkLoader
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
    private bool $natural = false;
    private array $players = [];
    private int $loaderId = 0;

    /**
     * FairyGate constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param int $towards 朝向
     * @param Block|null $coreBlock
     */
    public function __construct(Level $level, CompoundTag $nbt, int $towards = self::DEFAULT_EXTEND, ?Block $coreBlock = null)
    {
        $this->loaderId = Level::generateChunkLoaderId($this);
        $this->towards = $towards;
        $this->coreBlock = $coreBlock;
        parent::__construct($level, $nbt);
        $start = self::getPPosition($this, $this->towards, -1, 0);
        $end = self::getPPosition($this, $this->towards, 1, 2);
        $this->boundingBox = new AxisAlignedBB(min($start->x, $end->x), min($start->y, $end->y), min($start->z, $end->z), max($start->x, $end->x) + 0.9, max($start->y, $end->y) + 0.9, max($start->z, $end->z) + 0.9);
        $this->justCreated = false;
    }
    public function onUpdate($currentTick)
    {
        if (!$this->isNatural() and $this->age % 20 == 0) {
            if (count(self::checkBlocks($this->coreBlock, null, $this->towards)) > 0) {
                $this->kill();
                return false;
            }
        }
        if (!$this->isNatural() and $this->age < 20 * 10 + 10){
            if ($this->age % 10 == 0){
                if ($this->flash)
                    $this->constructGates();
                else
                    $this->destroyGates();
                $this->flash = !$this->flash;
                if (!$this->siphonMana(1000)){
                    $this->crash = true;
                }
            }
        }elseif ($this->age % 20 == 0){
            if (!$this->isNatural() and ($this->crash or !$this->checkGates())){
                $this->kill();
                return false;
            }

            foreach ($this->getLevel()->getCollidingEntities($this->boundingBox, $this) as $entity){
                if ($entity instanceof Player and $entity->canSelected(20 * 20)){
                    $position = null;
                    if ($this->level->getName() == 'f10'){
                        $name = strtolower($entity->getName());
                        /** @var FairyGate $gate */
                        $gate = null;
                        if (isset(Main::getInstance()->playerGates[$name])){
                            $gate = Main::getInstance()->playerGates[$name];
                        }
                        if ($gate === null or $gate->closed){
                            if (count(Main::getInstance()->gates) <= 0){
                                $this->kill();
                                $entity->sendMessage('外部没有开放的精灵门！');
                                return true;
                            }
                            /** @var FairyGate $gate */
                            $gate = Main::getInstance()->gates[array_rand(Main::getInstance()->gates)];
                        }
                        $gate->removePlayer($entity);
                        $position = $gate;
                    }else{
                        $this->joinPlayer($entity);
                        $level = $this->getServer()->getLevelByName('f10');
                        if ($level == null){//error
                            MainLogger::getLogger()->error('找不到精灵世界。');
                            $this->kill();
                            return true;
                        }else{
                            $position = $level->getSpawnLocation();
                            self::tryCreateOnlyGate($position);
                            $position = self::getGate($level);
                        }
                    }
                    $entity->teleport($position);
                }
            }
            if (!$this->isNatural() and $this->age % 60 == 0 and !$this->siphonMana(30)){
                $this->kill();
                return false;
            }
        }
        if (!isset(Main::getInstance()->gates[$this->getId()]) and ($this->age >=  20 * 10 + 10 or $this->isNatural())){
            if (!$this->isNatural())self::tryCreateOnlyGate(Server::getInstance()->getLevelByName('f10')->getSafeSpawn());
            Main::getInstance()->gates[$this->getId()] = $this;
        }
        $this->age++;
        return true;
    }

    /**
     * 尝试创建唯一的门
     * @param Position $position
     * @return bool 是否成功
     */
    public static function tryCreateOnlyGate(Position $position):bool {
        $count = 0;
        foreach ($position->getLevel()->getEntities() as $entity){
            if ($entity instanceof FairyGate)$count++;
        }
        if ($count <= 0){
            $nbt = Utils::spawnEntityBaseNBT($position->floor());
            $fg = new FairyGate($position->getLevel(), $nbt, FairyGate::X_EXTEND, $position->getLevel()->getBlock($position->add(0, -1)));
            $fg->setNatural(true);
            $fg->constructGates();
            return true;
        }
        return false;
    }
    public function siphonMana(int $mana): bool
    {
        $searchManaCache = ManaSystem::searchManaCache($this);//搜索附近魔力缓存器来抽取魔力
        Utils::vector3Sort($searchManaCache, $this);
        foreach ($searchManaCache as $manaCeche){
            /** @var ManaCache $manaCeche */
            $m = min($mana, $manaCeche->getMana());
            if ($m <= 0)continue;
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
        unset(Main::getInstance()->gates[$this->getId()]);
        $this->close();
        self::removeClosedGate();
        $this->destroyGates();

        // $this->level->unregisterChunkLoader($this, $this->x >> 4, $this->z >> 4);
    }

    public static function getTowards(Block $coreBlock, int $towards): int{
        $level = $coreBlock->getLevel();
        if ($level->getBlock($coreBlock->add(1)) instanceof LiveWood or $level->getBlock($coreBlock->add(-1)) instanceof LiveWood){//x
            $towards = self::X_EXTEND;
        }elseif ($level->getBlock($coreBlock->add(0, 0, 1)) instanceof LiveWood or $level->getBlock($coreBlock->add(0, 0, -1)) instanceof LiveWood){//z
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
        $towards = self::getTowards($coreBlock, $towards);
        if ($towards == self::DEFAULT_EXTEND){
            $yaw = $player->getYaw();
            if (($yaw <= 45 or $yaw > 315) or ($yaw >= 135 and $yaw < 225))
                $towards = self::X_EXTEND;//x延伸
            else
                $towards = self::Z_EXTEND;
        }
        $blocks = self::getBlocks($coreBlock, $towards);
        /** @var Block $block */
        foreach ($blocks as $index => $block){
            $b = $level->getBlock($block);
            if ($b->getId()== $block->getId() and $b->getDamage() == $block->getDamage())unset($blocks[$index]);
        }
        return $blocks;
    }

    /**
     * @return bool
     */
    public function isNatural(): bool
    {
        return $this->natural;
    }

    /**
     * @param bool $natural
     */
    public function setNatural(bool $natural): void
    {
        $this->natural = $natural;
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
     * @param bool $bb 碰撞盒
     * @return Position
     */
    public static function getPPosition(Position $pos, int $towards, int $ysz, int $y, bool $bb = false): Position{
        if ($bb)
            return $towards == self::X_EXTEND?$pos->add($ysz, $y, $pos->z >= 0 ? 1 : -1):$pos->add($pos->x >= 0 ? 1 : -1, $y, $ysz);
        else
            return $towards == self::X_EXTEND?$pos->add($ysz, $y):$pos->add(0, $y, $ysz);
    }

    /**
     * 保存
     */
    public function saveNBT()
    {
        $this->namedtag->id = new StringTag('id', $this->getSaveId());
        $this->namedtag->towards = new IntTag('towards', $this->towards);
        $this->namedtag->age = new IntTag('age', $this->age);
        $this->namedtag->natural = new ByteTag('natural', $this->natural?1:0);
        $this->namedtag->coreBlock = new ListTag('coreBlock', [
            new DoubleTag(0, $this->coreBlock->x),
            new DoubleTag(1, $this->coreBlock->y),
            new DoubleTag(2, $this->coreBlock->z)
        ]);
        $this->namedtag->Pos = new ListTag('Pos', [
            new DoubleTag(0, $this->x),
            new DoubleTag(1, $this->y),
            new DoubleTag(2, $this->z)
        ]);
        $this->namedtag->players = new ListTag('players', []);
        $this->namedtag->players->setTagType(NBT::TAG_String);
        foreach ($this->players as $player){
            $this->namedtag->players[] = new StringTag("", $player);
        }
    }
    protected function initEntity()
    {
        if (isset($this->namedtag['coreBlock'])){
            $pos = new Position(
                $this->namedtag['coreBlock'][0],
                $this->namedtag['coreBlock'][1],
                $this->namedtag['coreBlock'][2],
                $this->level
            );
            $this->coreBlock = $this->level->getBlock($pos);
        }
        if (isset($this->namedtag['towards']))$this->towards = (int)$this->namedtag['towards'];
        if (isset($this->namedtag['age']))$this->age = (int)$this->namedtag['age'];
        if (isset($this->namedtag['natural']))$this->natural = (bool)$this->namedtag['natural'];
        /** @var StringTag $player */
        if (isset($this->namedtag['players']))foreach ($this->namedtag->players as $player){
            $this->joinPlayer($player->getValue());
        }
        // $this->level->registerChunkLoader($this, $this->x >> 4, $this->z >> 4, true);
    }
    public function joinPlayer($player){
        if ($player instanceof Player){
            $name = strtolower($player->getName());
        }else{
            $name = strtolower($player);
        }
        $this->players[$name] = $name;
        Main::getInstance()->playerGates[$name] = $this;
    }
    public function removePlayer(Player $player){
        if ($player instanceof Player){
            $name = strtolower($player->getName());
        }else{
            $name = strtolower($player);
        }
        unset($this->players[$name], Main::getInstance()->playerGates[$name]);
    }
    /**
     * @param Level $level
     * @return FairyGate|Entity|null
     */
    public static function getGate(Level $level) :?Entity{
        foreach ($level->getEntities() as $entity){
            if ($entity instanceof FairyGate){
                return $entity;
            }
        }
        return null;
    }
    public static function removeClosedGate(){
        /**
         * @var string $playerName
         * @var FairyGate $gate
         */
        foreach (Main::getInstance()->playerGates as $playerName => $gate){
            if ($gate->closed)unset(Main::getInstance()->playerGates[$playerName]);
        }
        /*
        if (count(Main::getInstance()->playerGates) <=0){
            foreach (Main::getInstance()->gates as $gate){
                $gate->kill();
            }
        }
        */
    }

    public function getLoaderId()
    {
        return $this->loaderId;
    }

    public function isLoaderActive()
    {
        return !$this->closed;
    }

    public function onChunkChanged(Chunk $chunk)
    {

    }

    public function onChunkLoaded(Chunk $chunk)
    {

    }

    public function onChunkUnloaded(Chunk $chunk)
    {

    }

    public function onChunkPopulated(Chunk $chunk)
    {

    }

    public function onBlockChanged(Vector3 $block)
    {

    }
}
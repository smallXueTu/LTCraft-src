<?php


namespace LTEntity\entity\Gaia;

use LTEntity\entity\Gaia\SkillEntity\Bomb;
use LTEntity\entity\Gaia\SkillEntity\Landmine;
use LTEntity\entity\Gaia\SkillEntity\Servant;
use LTEntity\Main;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\BaseOrnaments;
use LTItem\SpecialItems\Weapon;
use pocketmine\entity\Attribute;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\UUID;

/**
 * 盖亚守护者3
 * Class GaiaGuardiansIII
 * @package LTEntity\entity\Gaia
 */
class GaiaGuardiansIII extends Creature
{
    /**
     * @var float
     */
    public $width = 0.6;
    /***
     * @var float
     */
    public $height = 1.8;
    /**
     * @var float
     */
    public $eyeHeight = 1.62;
    /** @var int */
    private int $playerCount = 0;
    /** @var ?UUID */
    private ?UUID $uid = null;
    /** @var ?Position  */
    private ?Position $basePos = null;
    /** @var string  */
    private string $skinData = '';
    /** @var array  */
    private array $crystas = [];
    /** @var array  */
    private array $servants = [];
    /** @var array  */
    private $lastMove = 0;
    /** @var int  */
    private int $lastUpdateSee = 0;
    /** @var ?Position  */
    private ?Position $baseTarget = null;
    /** @var int  */
    public int $onSky = 0;
    /** @var int  */
    public int $onPlayerTick = 0;
    private int $nextLaunchInterval = 50;

    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
    }

    /**
     * @param Position $position
     * @param Player $player
     */
    public static function spawn(Position $position, Player $player){
        $playerCount = 0;//获取玩家数量
        foreach ($position->getLevel()->getPlayers() as $p){
            if ($p->isA() and $p->distance($position)<=13){
                $playerCount++;
            }
        }
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag('Pos', [
            new DoubleTag('', $position->x + 0.5),
            new DoubleTag('', $position->y + 1),
            new DoubleTag('', $position->z + 0.5)
        ]);
        $nbt->Rotation = new ListTag('Rotation', [
            new FloatTag('', 0),
            new FloatTag('', 0)
        ]);
        /** @var GaiaGuardiansIII $entity */
        $entity = new GaiaGuardiansIII($position->getLevel(), $nbt);//实例化盖亚守护者III
        $entity->setPlayerCount($playerCount);//设置玩家数量
        $entity->setBasePos($position);//设置信标坐标
        $entity->setMaxHealth(500 + $playerCount * 300);
        $entity->setHealth($entity->getMaxHealth());
        $entity->setNameTagVisible(true);
        $entity->setNameTagAlwaysVisible(true);
        $entity->setArmorV(150);//150护甲
        $entity->setNameTag('盖亚守护者 III');
        $entity->spawnCrysta();
        $entity->lastMove = Server::getInstance()->getTick();
        Main::getInstance()->gaia[$entity->getId()] = $entity;
    }
    /**
     * @return Position
     */
    public function getBasePos(): Position
    {
        return $this->basePos;
    }

    /**
     * @param Position $basePos
     */
    public function setBasePos(Position $basePos): void
    {
        $this->basePos = $basePos;
    }
    /**
     * @return int
     */
    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    /**
     * @param int $playerCount
     */
    public function setPlayerCount(int $playerCount): void
    {
        $this->playerCount = $playerCount;
    }


    public function onUpdate($tick)
    {
        if ($this->getPresencePlayerCount() > $this->getPlayerCount()){
            foreach ($this->getLevel()->getPlayers() as $p){
                /** @var Player $p */
                if ($p->isA() and $p->distance($this->getBasePos())<13){
                    $p->sendMessage("§c在场人数比召唤人数还多！这是弟弟行为。");
                }
            }
            $this->close();
            return false;
        }
        if ($this->getPresencePlayerCount() <= 0){//附近没有玩家
            if ($this->onPlayerTick > 20){
                $this->close();
                return false;
            }
            $this->onPlayerTick++;
        }else{
            $this->onPlayerTick = 0;
        }
        if ($this->age % 20 == 0)$this->spawnBorderParticle();
        $this->age ++;

        if ($this->age % 5 == 0){
            /** @var Player $player */
            foreach ($this->getPresencePlayer() as $player){
//                if($player->isCreative()){
//                    $player->setGamemode(0);
//                }
                if (!$player->canSelected() or !$player->isSurvival())continue;
                if ($this->getBasePos()->y - $player->y > 1){
                    $player->setLastDamageCause(new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_DIDI, PHP_INT_MAX));
                    $player->setHealth(0);
                    $player->sendMessage('§c你的高度低于祭坛坐标！');
                    continue;
                }
                $buff = $player->getBuff();
                if ($buff->getEnable()){
                    $player->setLastDamageCause(new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_DIDI, PHP_INT_MAX));
                    $player->setHealth(0);
                    $player->sendMessage('§c开Buff攻击盖亚！这是弟弟行为。');
                    $player->sendMessage('输入/tw buff [关闭/打开]来控制');
                }
                foreach ($player->getInventory()->getContents() as $index => $item){//检查背包物品
                    if ($item instanceof Weapon or $item instanceof Armor or $item instanceof BaseOrnaments){
                        $player->getInventory()->setItem($index, Item::get(0));
                        $player->getLevel()->dropItem($player, $item);
                    }
                }
                foreach ($player->getOrnamentsInventory()->getContents() as $index => $item){//检查饰品栏
                    if ($item instanceof BaseOrnaments){
                        $player->getOrnamentsInventory()->setItem($index, Item::get(0));
                        $player->getLevel()->dropItem($player, $item);
                    }
                }
            }
        }
        if (Server::getInstance()->getTick() - $this->lastMove > 80){
            $this->lastMove = Server::getInstance()->getTick();
            $randX = mt_rand(-8, 8);
            $randZ = mt_rand(-8, 8);
            $this->getLevel()->addSound(new EndermanTeleportSound($this));
            $this->teleport($this->getBasePos()->add($randX, 0, $randZ));
            foreach($this->getLevel()->getCollisionBlocks($this->getBoundingBox()) as $block){
                if ($block->equals($this->getBasePos()))continue;
                $this->getLevel()->useBreakOn($block);//破坏碰撞的方块..
            }
            $this->spawnLandmine();
        }
        if ($this->age % $this->nextLaunchInterval == 0){
            $this->launchBomb();
            $this->nextLaunchInterval = mt_rand(60, 140);
        }
        $this->updateTarget();
        parent::onUpdate($tick);
        return true;
    }

    /**
     * TODO: 在关服的时候保存盖亚到地图
     */
    public function saveNBT()
    {

    }
    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4, $force = false)
    {

    }

    /**
     * 发射炸弹
     */
    public function launchBomb(){
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", $this->x),
            new DoubleTag("", $this->y + $this->eyeHeight),
            new DoubleTag("", $this->z)
        ]);
        $nbt->Rotation = new ListTag('Rotation', [
            new FloatTag('', 0),
            new FloatTag('', -90)
        ]);
        /** @var Player $player */
        foreach ($this->getPresencePlayer() as $player){
            $nbt->Motion = new ListTag('Motion', [
                new DoubleTag('', ($player->x - $this->x) / 20),
                new DoubleTag('', 0.4),
                new DoubleTag('', ($player->z - $this->z) / 20)
            ]);
            $bomb = new Bomb($this->getLevel(), $nbt, $this);
            $bomb->spawnToAll();
        }
    }
    /**
     * 产出地雷
     */
    public function spawnLandmine(){//landmine
        $count = mt_rand(1, 2);
        $count += $this->getPlayerCount();
        $nbt = new CompoundTag;
        $nbt->Rotation = new ListTag('Rotation', [
            new FloatTag('', 0),
            new FloatTag('', )
        ]);
        while($count-->0){
            $randX = mt_rand(-10, 10);
            $randZ = mt_rand(-10, 10);
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $this->basePos->x + $randX),
                new DoubleTag("", $this->basePos->y),
                new DoubleTag("", $this->basePos->z + $randZ)
            ]);
            $landmine = new Landmine($this->getLevel(), $nbt, 0.6, $this);
            $landmine->spawnToAll();
        }
    }

    /**
     * 初始化
     */
    public function initEntity()
    {
        $this->getAttributeMap()->addAttribute(new Attribute(Attribute::HEALTH, "minecraft:health", 0, 300, 300, true));
        $this->spawnToAll();
        parent::initEntity();
    }
    public function setHealth($amount)
    {
        $oldAmount = $this->getHealth();
        parent::setHealth($amount);
        $health = $this->getHealth();
        if ((($oldAmount > $this->getMaxHealth() * 0.3 and $health < $this->getMaxHealth() * 0.3) or ($oldAmount > $this->getMaxHealth() * 0.7 and $health < $this->getMaxHealth() * 0.7)) and count($this->servants) < 4){//召唤仆从
            /** @var Player $player */
            foreach ($this->getPresencePlayer() as $player){
                $player->sendMessage($this->getName() . '召唤了仆从，优先击杀仆从哦！');
            }
            $this->spawnServant();
        }
        $this->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue($this->getMaxHealth())->setValue($amount, true);
        $this->sendAttributes();
    }

    /**
     * 召唤仆从
     */
    public function spawnServant(){
        $nbt = new CompoundTag;
        $nbt->Rotation = new ListTag('Rotation', [
            new FloatTag('', 0),
            new FloatTag('', )
        ]);
        $v3 = $this->getBasePos();
        $blocks[] = $this->getLevel()->getBlock($v3->add(4, 2, 4))->floor();
        $blocks[] = $this->getLevel()->getBlock($v3->add(4, 2, -4))->floor();
        $blocks[] = $this->getLevel()->getBlock($v3->add(-4, 2, -4))->floor();
        $blocks[] = $this->getLevel()->getBlock($v3->add(-4, 2, 4))->floor();
        foreach ($blocks as $block){
            $index = $block->x . ":" . $block->y . ":" . $block->z;
            if (isset($this->servants[$index]))continue;
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $block->x),
                new DoubleTag("", $block->y),
                new DoubleTag("", $block->z)
            ]);
            $servant = new Servant($this->getLevel(), $nbt, $this);
            $servant->setMaxHealth(50);
            $servant->setHealth($this->getMaxHealth());
            $servant->spawnToAll();
            $this->servants[$index] = $servant;
        }
    }
    /**
     * 检查看的目标
     */
    protected function checkCeeTarget()
    {
        if(Server::getInstance()->getTick() - $this->lastUpdateSee > 100) {
            $this->lastUpdateSee = Server::getInstance()->getTick() + mt_rand(-20, 20);
            if (mt_rand(0, 1)){
                $player = [24, null];
                foreach ($this->getLevel()->getPlayers() as $p){
                    if (!$p->isA())continue;
                    if ($p->distance($this) < $player[0])$player = [$p->distance($this), $p];
                }
                if ($player[1]!=null and $player[1] instanceof Player){
                    $this->baseTarget = $player[1];
                    return;
                }
            }
            $x = mt_rand(2, 5);
            $z = mt_rand(2, 5);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
    }

    /**
     * 边界粒子
     */
    public function spawnBorderParticle(){
        $r = 12;
        $yy = $this->getBasePos()->getY()+5;
        for($i=1;$i<=180;$i++){
            $a=$this->getBasePos()->getX()+$r*cos($i*3.14/90) ;
            $b=$this->getBasePos()->getZ()+$r*sin($i*3.14/90) ;
            $this->getLevel()->addParticle(new DustParticle(new Vector3($a,$yy,$b),200,0,0));
        }
    }

    /**
     * @return int
     */
    public function getPresencePlayerCount(){
        $playerCount = 0;
        foreach ($this->getLevel()->getPlayers() as $p){
            if ($p->isA() and $p->distance($this->getBasePos())<13){
                $playerCount++;
            }
        }
        return $playerCount;
    }

    /**
     * 受伤
     */
    public function attack($damage, EntityDamageEvent $source)
    {
        parent::attack($damage, $source);
        if(!$source->isCancelled()){
            if ($source instanceof EntityDamageByEntityEvent){
                if ($this->getHealth() <= $this->getMaxHealth()*0.2){
                    $this->lastMove -= 80;
                }
            }
        }
    }
    /**
     * @return array
     */
    public function getPresencePlayer(){
        $players = [];
        foreach ($this->getLevel()->getPlayers() as $p){
            if ($p->isA() and $p->distance($this->getBasePos())<13){
                $players[] = $p;
            }
        }
        return $players;
    }

    /**
     * @return bool|void
     */
    public function updateMovement()
    {
        if(!($this->chunk instanceof Chunk)){
            $this->close();
            return false;
        }
        if($this->lastX !== $this->x || $this->lastY !== $this->y || $this->lastZ !== $this->z || $this->lastYaw !== $this->yaw || $this->lastPitch !== $this->pitch) {
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;
            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;
        }
        $yaw = $this->yaw;
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y + 1.62, $this->z, $yaw, $this->pitch, $yaw);
    }

    /**
     * 更新看的目标
     */
    public function updateTarget(){
        $this->checkCeeTarget();
        //$this->baseTarget = Server::getInstance()->getPlayer("A");
        if ($this->baseTarget !== null){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            $diff = abs($x) + abs($z);
            if($x==0 and $z==0) {
                $this->yaw= 0;
                $this->pitch = $y > 0 ? -90 : 90;
            }else{
                $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
                $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
            }
        }
    }

    /**
     * 关闭
     */
    public function close()
    {
        foreach ($this->crystas as $crysta){
            /** @var GaiaCrystal $crysta */
            $crysta->close();
        }
        foreach ($this->servants as $servant){
            /** @var Servant $crysta */
            $servant->close();
        }
        unset(Main::getInstance()->gaia[$this->getId()]);
        parent::close();
    }

    /**
     * 复活水晶
     */
    public function spawnCrysta(){
        $v3 = $this->getBasePos()->asVector3();
        $blocks[] = $this->getLevel()->getBlock($v3->add(4, 0, 4));
        $blocks[] = $this->getLevel()->getBlock($v3->add(4, 0, -4));
        $blocks[] = $this->getLevel()->getBlock($v3->add(-4, 0, -4));
        $blocks[] = $this->getLevel()->getBlock($v3->add(-4, 0, 4));
        foreach ($blocks as $block){
            $nbt = new CompoundTag;
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $block->x + 0.5),
                new DoubleTag("", $block->y),
                new DoubleTag("", $block->z + 0.5)
            ]);
            $nbt->Rotation = new ListTag('Rotation', [
                new FloatTag('', 0),
                new FloatTag('', 0)
            ]);
            $entity = new GaiaCrystal($this->getLevel(), $nbt, $this);
            $entity->spawnToAll();
            $this->getLevel()->spawnLightning($entity, 0, $this);
            $this->crystas[] = $entity;
        }
    }

    /**
     * @return UUID
     */
    public function getUniqueId(){
        if ($this->uid === null){
            $this->uid=UUID::fromData($this->getId(), $this->skinData, ('盖亚守护者 III'));
        }
        return $this->uid;
    }

    /**
     * @param Player $player
     * @param bool $send
     */
    public function despawnFrom(Player $player, bool $send = true){
        if(isset($this->hasSpawned[$player->getLoaderId()])){
            if($send) {
                $pk = new PlayerListPacket();
                $pk->type = PlayerListPacket::TYPE_REMOVE;
                $pk->entries[] = [$this->getUniqueId()];
                $player->dataPacket($pk);
            }
            $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, false);
            parent::despawnFrom($player, $send);
        }
    }
    /**
     * @param Player $player
     */
    public function spawnTo(Player $player){
        if(!isset($this->hasSpawned[$player->getLoaderId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
            if ($this->age < 20*20){
                //$player->sendTitle("§c盖亚守护者抑制了你的行动！！");
                //$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, true);
            }
            $pk = new AddPlayerPacket();
            $pk->uuid = $this->getUniqueId();
            $pk->username = $this->getName();
            $pk->eid = $this->getId();
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = 0;
            $pk->speedY = 0;
            $pk->speedZ = 0;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->item=Item::get(0);
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);
            $bpk = new BossEventPacket();
            $bpk->eid = $this->getId();
            $bpk->eventType = 1;
            $player->dataPacket($bpk);
            $this->sendAttributes();
            parent::spawnTo($player);
        }
    }
    /**
     * @param bool $sendAll
     */
    public function sendAttributes(bool $sendAll = false){
        $entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
        if(count($entries) > 0){
            $pk = new UpdateAttributesPacket();
            $pk->entityId = $this->id;
            $pk->entries = $entries;
            foreach ($this->hasSpawned as $player) {
                $player->dataPacket($pk);
            }
            foreach($entries as $entry){
                $entry->markSynchronized();
            }
        }
    }
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "盖亚守护者III";
    }
}
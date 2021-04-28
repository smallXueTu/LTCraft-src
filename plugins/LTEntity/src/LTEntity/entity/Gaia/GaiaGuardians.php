<?php


namespace LTEntity\entity\Gaia;


use LTEntity\entity\Gaia\SkillEntity\DieArea;
use LTEntity\Main;
use LTEntity\Main as LTEntity;
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
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\UUID;

class GaiaGuardians extends Creature
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
    private $playerCount = 0;
    /** @var UUID */
    private $uid = null;
    /** @var Position  */
    private $basePos = null;
    /** @var string  */
    private $skinData = '';
    /** @var array  */
    private $crystas = [];
    /** @var array  */
    private $lastMove = 0;
    /** @var int  */
    private int $lastUpdateSee = 0;
    /** @var Position  */
    private $baseTarget = null;
    /** @var array  */
    private $dieAreas = [];
    /** @var int  */
    public $onSky = 0;
    /** @var int  */
    public $onPlayerTick = 0;
    /** @var int  */
    public $type = 0;
    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
    }

    /**
     * @param Position $position
     * @param Player $player
     */
    public static function spawn(Position $position, Player $player){
        $playerCount = 0;
        foreach ($position->getLevel()->getPlayers() as $p){
            if ($p->isA() and $p->distance($position)<=13){
                $playerCount++;
            }
        }
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag('Pos', [
            new DoubleTag('', $position->x + 0.5),
            new DoubleTag('', $position->y + 5),
            new DoubleTag('', $position->z + 0.5)
        ]);
        /** @var GaiaGuardians $entity */
         $entity = new GaiaGuardians($position->getLevel(), $nbt);
        $entity->setPlayerCount($playerCount);
        $entity->setBasePos($position);
        $entity->setMaxHealth(500 + $playerCount * 300);
        $entity->setHealth(1);
        $entity->setNameTagVisible(true);
        $entity->setNameTagAlwaysVisible(true);
        if ($player->getItemInHand()->getLTName()=='泰拉钢锭'){
            $entity->setArmorV(150);
            $entity->type = 0;
        }elseif($player->getItemInHand()->getLTName()=='盖亚魂锭'){
            $entity->setArmorV(230);
            $entity->type = 1;
        }
        $entity->setNameTag(('盖亚守护者 '.($entity->type==0?'I':'II')));
        $entity->spawnCrysta();
        Main::getInstance()->gaia[$entity->getId()] = $entity;
    }


    /**
     * @param Entity $attacker
     * @param $damage
     * @param $x
     * @param $z
     * @param float $base
     * @param bool $force
     */
    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4, $force=false){

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

    /**
     * 初始化
     */
    public function initEntity()
    {
        $this->getAttributeMap()->addAttribute(new Attribute(Attribute::HEALTH, "minecraft:health", 0, $this->getMaxHealth(), $this->getMaxHealth(), true));
        $this->spawnToAll();
        parent::initEntity();
    }

    /**
     * @param $tick
     * @return bool
     */
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

        if ($this->age % 5 == 0){
            /** @var Player $player */
            foreach ($this->getPresencePlayer() as $player){
                if($player->isCreative()){
                    $player->setGamemode(0);
                }
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
        if ($this->age <= 20*20){
            $this->age ++;
            $this->setHealth($this->getHealth() + ceil($this->getMaxHealth() / (20*20)));
            //if (Server::getInstance()->getTick() % 2 == 0)$this->shakeScreen();//震动玩家屏幕
            $this->yaw+=25;
            $this->pitch=0;
            $this->forceUpdateMovement();
            //$this->move(0,  0 - (10 / (20*20)),0);
            /*
            if ($this->age == 20*20){//初始化完成 解除玩家移动效果
                foreach ($this->hasSpawned as $player) $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, false);
            }
            */
        }elseif($this->onSky!=0 and $this->onSky < 20*30){
            $this->age ++;
            $this->onSky++;
            $this->yaw+=25;
            $this->pitch=0;
            if ($this->getY() - $this->getBasePos()->getY() <= 4){
                $this->move(0, $this->gravity ,0);
            }
            $this->forceUpdateMovement();
        }else{
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
                $this->dieAreas = [];
                $this->updateDieAreas();
            }
            $this->updateTarget();
            foreach ($this->dieAreas as $dieArea){
                /** @var DieArea $dieArea */
                $dieArea->onUpdate();
            }
           parent::onUpdate($tick);
        }
        return true;
    }
    /**
     * 更新凋零区域
     */
    public function updateDieAreas(){
        $count = mt_rand(5, 10);
		$count += $this->getPlayerCount();
        while($count-->0){
            $randX = mt_rand(-10, 10);
            $randZ = mt_rand(-10, 10);
            $this->dieAreas[] = new DieArea($this->getBasePos()->add($randX, 0, $randZ), mt_rand(1, 2));
        }
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
     *
     */
    public function close()
    {
        foreach ($this->crystas as $crysta){
            /** @var GaiaCrystal $crysta */
            $crysta->close();
        }
       unset(Main::getInstance()->gaia[$this->getId()]);
        parent::close();
    }

    /**
     * TODO: 在关服的时候保存盖亚到地图
     */
    public function saveNBT()
    {

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
     * 受伤
     */
    public function attack($damage, EntityDamageEvent $source)
    {
        if ($this->age <= 20*20 or ($this->onSky!=0 and $this->onSky < 20*30))return;
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
     * 震动屏幕
     */
    private function shakeScreen(){
        foreach ($this->hasSpawned as $player) {
            $x=$player->x - $this->x;
            $y=$player->y - $this->y;
            $z=$player->z - $this->z;
            if($x==0 and $z==0){
                $yaw = 0;
                $pitch = $y>0?-90:90;
                if($y==0)$pitch=0;
            }else{
                $yaw=asin($x/sqrt($x*$x+$z*$z))/3.14*180;
                $pitch=round(asin($y/sqrt($x*$x+$z*$z+$y*$y))/3.14*180);
            }
            if($z>0)
            {
                $yaw=-$yaw+180;
            }
            $location = $player->asLocation();
            $x = (mt_rand(1, 100) / 100 - 0.5) * 2;
            $z = (mt_rand(1, 100)/ 100 - 0.5) * 2;
            $location = $location->add($x / 5, 0, $z / 5);

            $location->yaw = $yaw - $x * 2;
            $location->pitch = $pitch - $z * 2;
            $player->sendPosition($location, $location->yaw, $location->pitch);
            $player->newPosition = $location;
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
     * @param $amount
     */
    public function setHealth($amount)
    {
        parent::setHealth($amount);
        $this->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue($this->getMaxHealth())->setValue($amount, true);
        $this->sendAttributes();
        if ($this->age >= 20*20 and $this->getHealth() <= $this->getMaxHealth()*0.2 and $this->onSky==0){
            if ($this->getHealth()<=0){
                return;
            }
            $this->onSky = 1;
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return ('盖亚守护者 '.($this->type == 0?'I':'II'));
    }

    /**
     * @return UUID
     */
    public function getUniqueId(){
        if ($this->uid === null){
            $this->uid=UUID::fromData($this->getId(), $this->skinData, ('盖亚守护者 '.($this->type == 0?'I':'II')));
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
            $entity = new GaiaCrystal($this->getLevel(), $nbt, $this);
            $entity->spawnToAll();
            $this->getLevel()->spawnLightning($entity, 0, $this);
            $this->crystas[] = $entity;
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
     * @param Player $player
     * @return bool
     */
    public static function checkInv(Player $player):bool {
        foreach ($player->getInventory()->getContents() as $item){
            if ($item instanceof Weapon or $item instanceof Armor or $item instanceof BaseOrnaments){
                return false;
            }
        }
        return true;
    }
    public function kill()
    {
        foreach ($this->getPresencePlayer() as $player){
            $player->newProgress('死亡,寻求！', '在盖亚仪式中召唤并击杀一只盖亚守护者', 'challenge');
        }
        return parent::kill();
    }

    public function getDrops()
    {
        $drop = [];
        if ($this->type == 0){
            $item = \LTItem\Main::getInstance()->createMaterial("盖亚之魂");
            $item->setCount(mt_rand(1, 4));
            $drop[] = $item;
        }elseif ($this->type == 1){
            foreach ($this->getPresencePlayer() as $player){
                /** @var Player $player */
                if (!$player->isA())continue;
                $item = \LTItem\Main::getInstance()->createMana("命运之骰", $player);
                $drop[] = $item;
            }
        }
        return $drop;
    }
}
<?php

namespace LTEntity\entity;

use pocketmine\Player;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\ProjectileSource;
use pocketmine\entity\Effect;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\level\format\Chunk;
use pocketmine\math\Math;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\entity\Attribute;
use LTItem\Main as LTItem;
use LTItem\EventListener as LTWEventListener;
use LTEntity\entity\monster\flying\ABlaze;
use LTEntity\entity\monster\flying\AEnderDragon;
use LTEntity\entity\monster\walking\AShulker;
use LTEntity\entity\monster\walking\EMods\ANPC;
use pocketmine\event\entity\EntityRegainHealthEvent;
use LTEntity\entity\monster\Monster;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\scheduler\CallbackTask;
use LTEntity\Main as LTEntity;
use LTEntity\DataList;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\Explosion;
use LTEntity\particle\FloatingText;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;

abstract class BaseEntity extends Creature
{

    public $clear = true;
    public $lastTime = 0;
	public $restTime;
	public $seeTime;
    public $Mode = false;
    public $enConfig = [];
    public $participants = [];
    public $Equipments = [];
    public $onPlayer = false;
    public $isAnger = false;
    public $lastAttackEntity = null;
    public $attackMove = 0;
    protected $stayTime = 0;
    protected $spawnPos;
    protected $moveTime = 0;

    /** @var Vector3|Entity */
    protected $baseTarget = null;

    private $movement = true;
    private $friendly = false;
    protected $hasSkill = false;
    private $damage = 0;
    protected $lastAttackTime = PHP_INT_MAX;
    private $wallcheck = true;
    public abstract function updateMove();
    public static function skillArrow($entity)
    {
        $nbt = new CompoundTag('', [
            'Pos' => new ListTag('Pos', [
                new DoubleTag('', $entity->x),
                new DoubleTag('', $entity->y + $entity->getEyeHeight()),
                new DoubleTag('', $entity->z)
            ]),
            'Fire' => new ShortTag('Fire', 0),
            'Potion' => new ShortTag('Potion', 0)
        ]);
        for($i = 0; $i < 30; $i++) {
            $nbt->Motion = new ListTag('Motion', [
                new DoubleTag('', -sin($i * 12 / 180 * M_PI)),
                new DoubleTag('', 0),
                new DoubleTag('', cos($i * 12 / 180 * M_PI))
            ]);
            $arror = Entity::createEntity('AFalseArrow', $entity->getLevel(), $nbt, $entity, true);
            $arror->setMotion($arror->getMotion()->multiply(2));
            $arror->spawnToAll();
            $entity->level->addSound(new BlazeShootSound($entity), $entity->getViewers());
        }
    }
	public static function addEnchant(&$item){
		$item->setNamedTag(new CompoundTag('',[
			'ench'=>new ListTag('ench', [])
		]));
		return $item;
	}
	public function checkBack(){
		return $this->onPlayer and $this->distance($this->spawnPos) > 1 and $this->enConfig['刷怪点']!=='傀儡';
	}
    public function getSaveId()
    {
        $class = new \ReflectionClass(get_class($this));
        return $class->getShortName();
    }
    public function setOnPlayer($onPlayer)
    {
        $this->onPlayer = $onPlayer;
    }
	public function getTarget(){
		return $this->baseTarget;
	}
	public function setTarget($target){
		$this->baseTarget = $target;
	}
    public function isMovement() : bool{
        return $this->movement;
    }

    public function isFriendly() : bool{
        return $this->friendly;
    }
    public function getDamage()
    {
        return $this->damage;
    }
    public function setDamage($damage)
    {
        $this->damage = $damage;
    }
    public function isKnockback() : bool{
        if($this->getLastDamageCause() instanceof EntityDamageByEntityEvent and $this->getLastDamageCause()->getCause() == EntityDamageEvent::CAUSE_THORNS)
            return false;
        return $this->attackTime > 3;
    }
	public function initThis(){
     $this->setNormalName($this->enConfig['名字']);
		$this->setMaxHealth($this->enConfig['血量']);
		if($this->enConfig['名字']=='冰之神'){
			$this->InitializeIng=210;
			for($i=0;$i<=10;$i++){
				$this->level->spawnLightning($this);
			}
		}else{
			$this->setHealth($this->enConfig['血量']);
		}
		if($this->enConfig['刷怪点']!=='傀儡')$this->spawnPos=new Vector3($this->enConfig['x'], $this->enConfig['y'], $this->enConfig['z']);
		$this->setDamage($this->enConfig['攻击']);
		if(isset($this->enConfig['护甲']))$this->setArmorV($this->enConfig['护甲']);
		if(!($this instanceof AEnderDragon)){
			$this->setNameTagVisible(true);
			$this->setNameTagAlwaysVisible(true);
			$this->setNameTag('§a'.$this->enConfig['名字'].' §d[§e'.$this->getHealth().'/§4'.$this->getMaxHealth().'§d]');
		}
		if($this instanceof ProjectileSource and substr($this->enConfig['手持ID'], 0, 3)==261){
			$this->attackMove=1;
		}
		if(in_array($this->enConfig['名字'], ['守护者-卡拉森','生化统治者','觉醒法老','异界统治者','冰之神','怪魔制造者','碎骨者','灭世者','命末神','死神审判者','骑士']))$this->hasSkill=true;
		if(isset($this->enConfig['类型']) and in_array($this->enConfig['类型'], DataList::$EMods)){
			if($this->enConfig['手持ID']!==false){
				$pk = new MobEquipmentPacket();
				$pk->eid = $this->getId();
				$ids=explode(':', $this->enConfig['手持ID']);
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]==true)self::addEnchant($item);
				$pk->item = $item;
				$pk->slot = 10;
				$pk->selectedSlot = 10;
				$this->Equipments[]=$pk;
			}
			if($this->enConfig['头盔ID']===false and $this->enConfig['胸甲ID']===false and $this->enConfig['护膝ID']===false and $this->enConfig['鞋子ID']===false)return;
			$pk = new MobArmorEquipmentPacket();
			$pk->eid = $this->getId();
			if($this->enConfig['头盔ID']!==false){
				$ids=explode(':', $this->enConfig['头盔ID']);
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true")self::addEnchant($item);
				$pk->slots[]=$item;
			}else $pk->slots[] = Item::get(0);
			if($this->enConfig['胸甲ID']!==false){
				$ids=explode(':', $this->enConfig['胸甲ID']);
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true")self::addEnchant($item);
				$pk->slots[]=$item;
			}else $pk->slots[] = Item::get(0);
			if($this->enConfig['护膝ID']!==false){
				$ids=explode(':', $this->enConfig['护膝ID']);
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true")self::addEnchant($item);
				$pk->slots[]=$item;
			}else $pk->slots[] = Item::get(0);
			if($this->enConfig['鞋子ID']!==false){
				$ids=explode(':', $this->enConfig['鞋子ID']);
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true")self::addEnchant($item);
				$pk->slots[]=$item;
			}else $pk->slots[] = Item::get(0);
			$this->Equipments[]=$pk;
		}
	}
    public function setMovement(bool $value)
    {
        $this->movement = $value;
    }

    public function setFriendly(bool $bool)
    {
        $this->friendly = $bool;
    }

    public function getSpeed() : float{
        return isset($this->namedtag['Speed']) ? $this->namedtag['Speed'] : 1;
    }

    public function initEntity()
    {
        parent::initEntity();
        $this->dataProperties[self::DATA_FLAG_IMMOBILE] = [self::DATA_TYPE_BYTE, 1];
    }

//不许保存ARPG的怪物
    public function saveNBT() {}

    public function spawnTo(Player $player)
    {
        if(
            !isset($this->hasSpawned[$player->getLoaderId()])
            && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])
        ) {
            $pk = new AddEntityPacket();
            $pk->eid = $this->getID();
            $pk->type = static::NETWORK_ID;
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = 0;
            $pk->speedY = 0;
            $pk->speedZ = 0;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);
            $this->hasSpawned[$player->getLoaderId()] = $player;
        }
    }
    public function transferTo(Vector3 $v3)
    {

        $this->motionX = 0;
        $this->motionY = 0;
        $this->motionZ = 0;
        $this->x = $v3->x;
        $this->y = $v3->y;
        $this->z = $v3->z;
        $this->checkTarget();
        $this->updateMovement();
    }

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
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, ($this instanceof ANPC) ? ($this->y + 1.62) : $this->y, $this->z, $yaw, $this->pitch, $yaw);
    }

    public function isInsideOfSolid()
    {
        $block = $this->level->getBlock($this->temporalVector->setComponents(Math::floorFloat($this->x), Math::floorFloat($this->y + $this->height - 0.18), Math::floorFloat($this->z)));
        $bb = $block->getBoundingBox();
        return $bb !== null and $block->isSolid() and !$block->isTransparent() and $bb->intersectsWith($this->getBoundingBox());
    }
    public function Skill($source)
    {
        $h = $this->getHealth() - $source->getFinalDamage();
        switch($this->enConfig['名字']) {
        case '骑士':
            if($this->Mode === 3) {
                if($source->getDamager() instanceof Player)$source->getDamager()->sendCenterTip('§c'.$this->enConfig['名字'].'蓄力中，免疫所有伤害');
                return false;
            }
            if($h < 3000 and $h >= 1500 and $this->Mode === false) {
                $this->Mode = 1;
                foreach($this->getViewers() as $p)$p->addTitle('§c骑士进入了暴走模式', '§d攻击速度翻倍！',0, 60, 0);
                return true;
            }
            elseif($h < 1500 and $h >= 500 and $this->Mode === 1) {
                $this->Mode = 2;
                $this->addArmorV(80);
                foreach($this->getViewers() as $p)$p->addTitle('§c骑士进入了愤怒模式', '§d护甲提升到80！',0, 60, 0);
                return true;
            }
            elseif($h < 500 and $this->Mode === 2 and !isset($this->hasBoom)) {
                $this->Mode = 3;
				$this->notMove=false;
                foreach($this->getViewers() as $p)$p->addTitle('§c血量过低进入爆发模式！', '§d身体产生爆炸！远离它！！',0, 60, 0);
				$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
					if($this->closed)return;
					$this->Mode = 2;
					$ex = new Explosion($this, 3, $this);
					$ex->booom(300, $this, true);
					$this->hasBoom=true;
					$this->setHealth($this->getHealth() + 1000);
					unset($this->notMove);
				}, []), 60);
			}
            break;
        case '觉醒法老':
            if($h > 2000) {
                if(isset($this->skill1))return true;
                foreach($this->getViewers() as $p)$p->addTitle('§l§a法老将在3秒后对攻击目标造成30真实伤害', '§d注意,潜行即可躲避！！',0, 60, 0);
				$this->skill1 = true;
                $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function($entity) {
                    if($this->baseTarget instanceof Player) {
						self::skillArrow($entity);
                        if(!$this->baseTarget->isSneaking())
							$this->baseTarget->attack(60, new EntityDamageEvent($this->baseTarget, EntityDamageEvent::CAUSE_MAGIC, 60));
                    }
                }, [$this]), 60);
            }
            elseif($h < 1000) {
                if(isset($this->skill2))return true;
                foreach($this->getViewers() as $p)$p->addTitle('§l§a法老将在2秒后对对攻击目标造成60真实伤害', '§d注意,潜行即可躲避！！',0, 40, 0);
				$this->skill2 = true;
                $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function($entity) {
                    if($this->baseTarget instanceof Player) {
                        self::skillArrow($entity);
                        if(!$this->baseTarget->isSneaking())
							$this->baseTarget->attack(60, new EntityDamageEvent($this->baseTarget, EntityDamageEvent::CAUSE_MAGIC, 60));
                    }
                }, [$this]), 40);
            }
            break;
        case '生化泰迪':
            if($h < 500 and $this->getArmorV() === 0) {
                foreach($this->getViewers() as $p)$p->addTitle('§l§a触发生化泰迪被动', '§e生化泰迪恢复2000生命值和200护甲！！',0, 60, 0);
                $this->setArmorV(200);
                $this->setHealth($this->getHealth() - (int)$h + 2000);
            }
        break;
        }
        return true;
    }
	public function setAnger($target=null){
		if($this->isAnger!==true){
			$this->isAnger = true;
			$this->seeTime = 0;
			$this->restTime = 0;
			if($target!==null)$this->baseTarget=$target;
		}elseif($target===null){
			$this->isAnger = false;
			$this->baseTarget = null;
			$this->checkTarget();
		}
	}
    public function attack($damage, EntityDamageEvent $source)
    {
        if($source instanceof EntityDamageByEntityEvent and ($damager=$source->getDamager()) instanceof Player) {
            if($damager->getGamemode() !== 0) {
                $damager->sendCenterTip('§c创造模式不允许打怪！');
                $source->setCancelled();
                return;
            }
			if(LTEntity::getCount($damager->getName())>=10){
				if(isset($damager->opening)){
					return;
				}
				$damager->sendTitle('§e将为你验证在线状态', '§a请点击绿宝石来验证！');
				$damager->opening = true;
				
                $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function($damager) {
					if(!$damager->closed)\LTMenu\Main::getInstance()->openMenu($damager, 'Validation');
					unset($damager->opening);
                }, [$damager]), 60);
				return false;
			}
			$damager->setlastAttackMob(time());
			if(!$source->zs){
				if($this->onPlayer and $source->getCause()!==EntityDamageEvent::CAUSE_PROJECTILE){
					$damager->sendCenterTip('§c攻击怪物请进去刷怪点感应范围');
					return false;
				}
				elseif($damager->isFlying() and $this instanceof WalkingEntity){
					$damager->sendCenterTip('§c请取消飞行状态!');
					return false;
				}
				elseif(abs($damager->getY()-$this->getY())>1.2  and $this instanceof WalkingEntity and $source->getCause()!==EntityDamageEvent::CAUSE_PROJECTILE){
					$damager->sendCenterTip('§c你跟怪物高度相差太多！');
					return false;
				}
				elseif($this->enConfig['名字'] === '玄之凋零' and $source->getCause() !== EntityDamageEvent::CAUSE_PROJECTILE){
					$damager->sendCenterTip('§c这个怪物免疫近战攻击!');
					return false;
				}
				if($this->lastAttackEntity===null){
					$this->lastAttackEntity=[$damager, $this->server->getTick()];
					$this->baseTarget=$damager;
				}
				if(isset($this->crystal) and $this->enConfig['名字'] === '冰之神'){
					 $damager->sendCenterTip('§c请清除冰冻水晶!');
					return false;
				}
			}
			if($this->baseTarget!==$damager and !$this->enConfig['团队']){
				if($this->server->getTick()-$this->lastAttackEntity[1]>300){
					$this->lastAttackEntity=[$damager, $this->server->getTick()];
					$this->baseTarget=$damager;
				}else{
					$damager->sendCenterTip('§c你不是这个怪物的目标');
					return false;
				}
			}
			$this->lastAttackEntity[1]=$this->server->getTick();
        }
        parent::attack($damage, $source);
        if($source->isCancelled() || !($source instanceof EntityDamageByEntityEvent)) {
            return false;
        }
        $damager = $source->getDamager();
		if($source instanceof EntityDamageByEntityEvent)$this->setAnger($damager);

        if($damager instanceof Player) {
			if($this->enConfig['团队']){
				if($this->getHealth()<($this->getMaxHealth()/4) and !isset($this->participants[$damager->getName()])){
					$damager->sendMessage("§c当前怪物生命值低于总生命值25%,你现在参与击杀不会获这个怪物的战利品！");
				}elseif(!isset($this->participants[$damager->getName()])){
					$this->participants[$damager->getName()] = [$damager, 1, time()];
				}elseif(isset($this->participants[$damager->getName()])){
					$this->participants[$damager->getName()][1]++;
					$this->participants[$damager->getName()][2] = time();
				}
				if($source->getCause()!==EntityDamageEvent::CAUSE_MAGIC){
					$this->attackTime = (int)(20/count($this->participants));
					if($this->attackTime>10)$this->attackTime=10;
					if($this->attackTime<4)$this->attackTime=4;
					foreach($this->participants as $index=>$p){
						if($p[0]->closed){
							unset($this->participants[$index]);
						}
						if(time()-$p[2]>30){
							$damager->sendMessage("§c你脱离战斗时间超过30s,无法获得怪物战利品！");
							unset($this->participants[$index]);
						}
					}
				}
			}
        }
        $this->stayTime = 0;
        $this->moveTime = 0;
        if($this->enConfig['怪物模式'] == 0 or $source->getCause() == EntityDamageEvent::CAUSE_THORNS)return true;
        if($this instanceof FlyingEntity)return true;
        $motion = (new Vector3($this->x - $damager->x, $this->y - $damager->y, $this->z - $damager->z))->normalize();
        $this->motionX = $motion->x * 0.09;
        $this->motionZ = $motion->z * 0.09;
        $this->updateMovement();
		return true;
    }
/*
    public function heal($amount, EntityRegainHealthEvent $source)
    {
        parent::heal($amount, $source);

        $this->setNameTag('§a'.$this->enConfig['名字'].' §d[§e'.$this->getHealth().'/§4'.$this->getMaxHealth().'§d]');
    }*/
    public function setHealth($h){
       parent::setHealth($h);
        if(!$this->justCreated and !($this instanceof AEnderDragon))$this->setNameTag('§a'.$this->enConfig['名字'].' §d[§e'.$this->getHealth().'/§4'.$this->getMaxHealth().'§d]');
    }
    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4, $force=false)
    {

    }

    public function entityBaseTick($tickDiff = 1, $EnchantL = 0)
    {
		// $this->blocksAround = null;
		$this->justCreated = false;
		if($this->getHealth() < $this->getMaxHealth() and $this->server->getTick() - $this->lastAttackTime > 600 and $this->age % 20 === 0 and !isset($this->skilling)) {
            $this->heal($this->getMaxHealth() * 0.05, new EntityRegainHealthEvent($this, $this->getMaxHealth() * 0.05, EntityRegainHealthEvent::CAUSE_MAGIC));
			if($this->getHealth()>=$this->getMaxHealth()){
				$this->participants = [];
				$this->setAnger();
			}
		}
		if($this->enConfig['刷怪点']==='傀儡' and $this->age>1800){
			$this->close();
			return false;
		}
        if($this->freezeTime > 0)$this->freezeTime -= $tickDiff;
        if($this->moveTime > 0)$this->moveTime -= $tickDiff;
        if($this->restTime > 0){
			$this->restTime -= $tickDiff;
			if($this->restTime<=0){
				$this->seeTime = 0;
				$this->baseTarget=null;
			}
		}
        if($this->seeTime > 0)$this->seeTime -= $tickDiff;
		if($this->BlindnessTime>0)
			--$this->BlindnessTime;
		if($this->SunderArmorTime>0)
			--$this->SunderArmorTime;
        if($this->attackTime > 0)$this->attackTime -= $tickDiff;
        if(!$this->isAlive()) {
			$this->removeAllEffects();
            if(++$this->deadTicks >= 20) {
				$this->despawnFromAll();
                $this->close();
                return false;
            }
            return true;
        }
		if($this->fireTicks > 0){
			$this->fireTicks -= $tickDiff;
			if($this->fireTicks===0)$this->extinguish();
		}
		if($this->noDamageTicks > 0){
			$this->noDamageTicks -= $tickDiff;
			if($this->noDamageTicks < 0){
				$this->noDamageTicks = 0;
			}
		}
		$this->age += $tickDiff;
		$this->ticksLived += $tickDiff;
        return true;
    }
    public function move($dx, $dy, $dz) : bool{
		if($this->freezeTime>0)return false;
		if($this->checkBack() or $this instanceof AEnderDragon){
			$this->boundingBox->offset($dx, $dy, $dz);
			$this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
			$this->checkChunks();
			return true;
		}
        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz), true); //碰撞立方体。
        foreach($list as $bb)
        {
            $dx = $bb->calculateXOffset($this->boundingBox, $dx);
        }
        $this->boundingBox->offset($dx, 0, 0);

        foreach($list as $bb)
        {
            $dz = $bb->calculateZOffset($this->boundingBox, $dz);
        }
        $this->boundingBox->offset(0, 0, $dz);
        foreach($list as $bb)
        {
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset(0, $dy, 0);

        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->checkChunks();
        return true;
    }
    public function targetOption(Creature $creature, float $distance) : bool{
        return $this instanceof Monster && (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->closed && $distance <= 81;
    }

    public function close()
    {
		if($this->closed)return;
        $plugin = $this->server->getPluginManager()->getPlugin('LTEntity');

        if(isset($plugin->spawnTmp[$this->enConfig['刷怪点']]['数量']) and $this->clear) {

            $this->clear = false;
            $plugin->spawnTmp[$this->enConfig['刷怪点']]['数量'] --;
        }
        parent::close();
        $this->participants=[];
        $this->baseTarget=null;
		if(isset($this->crystal))
		foreach($this->crystal as $c)$c->close();
		foreach($this as $index=>$key){
			if($key instanceof \pocketmine\scheduler\CallbackTask){
				$this->getServer()->getScheduler()->cancelTask($key->getTaskId());
			}
		}
    }
    public function kill()
    {
        if($this->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
            $player = $this->getLastDamageCause()->getDamager();
            if($player instanceof Player) {
                if($this->enConfig['死亡信息'] != false){
					$mess=str_replace('{name}', $player->getName(), $this->enConfig['死亡信息']);
					foreach($this->server->getOnlinePlayers() as $p){
						if(!in_array($p->getLevel()->getName(), ['zy', 'mt', 'land', 'dp', 'jm', 'zc', 'create', 'login', 'pvp'])){
							$p->sendMessage($mess);
						}
					}
				}
				if($this->enConfig['团队']){
					foreach($this->server->getOnlinePlayers() as $p){
						if(!in_array($p->getLevel()->getName(), ['zy', 'mt', 'land', 'dp', 'jm', 'zc', 'create', 'login', 'pvp']))
							$p->sendTitle('§e--'.$this->enConfig['名字'].'§e已被击杀--', '§a--击杀者:§d'.$player->getName().'§a--', 50, 80);
					}
				}
            }
        }
        if(isset($this->crystal))
            foreach($this->crystal as $c)$c->close();
        parent::kill();
    }
}
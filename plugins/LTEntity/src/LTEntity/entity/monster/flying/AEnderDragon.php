<?php

namespace LTEntity\entity\monster\flying;

use LTEntity\entity\BaseEntity;
use LTEntity\Main;
use LTEntity\entity\monster\FlyingMonster;
use LTEntity\entity\projectile\ADragonFireBall;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\Creature;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\ProjectileSource;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\scheduler\CallbackTask;
use pocketmine\level\Level;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\nbt\tag\{ByteTag,CompoundTag,DoubleTag,FloatTag,ListTag,StringTag,IntTag,ShortTag};
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\sound\ExplodeSound;

class AEnderDragon extends FlyingMonster implements ProjectileSource{
	const NETWORK_ID = 53; 
	public $width = 2.8;
	public $height = 3.8;
	public $gravity = 0.04;
	public $pspeed = 0;
	private $attribute = null;
	private $radius = 18;
	private $skillIng = false;
	private $lastSkillTime = 0;
	private $lastChangeModeTime = 0;
	private $skillStatus=null;
	private $tmp;
	public function initThis(){
		if($this->enConfig['名字']!=='暗黑影龙')return parent::initThis();
		$this->tmp = &Main::getInstance()->spawnTmp['boss'];
		$this->attribute = new Attribute(Attribute::HEALTH, "minecraft:health", 0, $this->enConfig['血量'], $this->enConfig['血量'], true);
		parent::initThis();
		$this->setNameTag(PHP_EOL . PHP_EOL . '§l§e暗黑影龙§a');
		foreach($this->level->getPlayers() as $p)$p->sendMessage('§l§a[§e暗黑影龙§a]§d接受死亡的命运吧！');
		$this->level->setTime(18000);
		$allPos=[new Vector3(67.5, 109, 6.5), new Vector3(42.5, 109, 31.5), new Vector3(67.5, 109, 56.5), new Vector3(92.5, 109, 31.5)];
		$nbt = new CompoundTag("", [
		   "Rotation" => new ListTag("Rotation", [
			   new FloatTag("", 0),
			   new FloatTag("", 0)
		   ]),
	   ]);
		$nbt->Motion = new ListTag("Motion", [
		   new DoubleTag("", 0),
		   new DoubleTag("", 0),
		   new DoubleTag("", 0)
		]);
		foreach($allPos as $index=>$pos){
			$this->level->spawnLightning($pos);
			$tmpNbt=clone $nbt;
			$tmpNbt->Pos = new ListTag("Pos", [
				new DoubleTag("", $pos->x),
				new DoubleTag("", $pos->y),
				new DoubleTag("", $pos->z)
			]);
			$Crystal = Entity::createEntity('AEnderCrystal', $this->level, $tmpNbt, $this);
			$Crystal->noCanMove = $index;
			$Crystal->setMaxHealth(50);
			$Crystal->setHealth(50);
			$Crystal->spawnToAll();
			$Crystal->setNameTag('§a异界水晶 §b§l[§e'.$Crystal->getHealth().'§f/§4'.$Crystal->getMaxHealth().'§b§l]§f');
			$Crystal->setNameTagVisible(true);
			$Crystal->setNameTagAlwaysVisible(true);
			$this->crystal[$index] = $Crystal;
		}
		$this->lastSkillTime = microtime(true)+10;
		$this->lastChangeModeTime = microtime(true)+20;
	}
	public function getStatus(){
		return $this->skillStatus;
	}
	public function setStatus($status){
		$this->skillStatus = $status;
	}
	public function getNameTag(){
		if($this->enConfig['名字']!=='暗黑影龙')return parent::getNameTag();
		return '§d暗黑影龙';
	}
	public function sendMsg(String $msg, $seconds=10){
		$this->tmp['tip']=[$msg, microtime(true)+$seconds];
	}
    public function onUpdate($currentTick){
		if($this->enConfig['名字']!=='暗黑影龙')return parent::onUpdate($currentTick);
        if(!$this->isAlive()) {
			$this->removeAllEffects();
            if(++$this->deadTicks >= 20) {
				$this->despawnFromAll();
                $this->close();
                return false;
            }
            return true;
        }
		$tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
		if($this->attackTime > 0) {
            $this->attackTime -= $tickDiff;
        }
		$this->justCreated = false;
		if($this->noDamageTicks > 0){
			$this->noDamageTicks -= $tickDiff;
			if($this->noDamageTicks < 0){
				$this->noDamageTicks = 0;
			}
		}
		$this->fireTicks -= $tickDiff;
		$this->age += $tickDiff;
		$this->ticksLived += $tickDiff;
		$this->updateMove();
		if(isset($this->crystal) and count($this->crystal)>0){
			if($this->server->getTick() % 20 === 0)$this->heal(3000*count($this->crystal), new EntityRegainHealthEvent($this, 3000*count($this->crystal), EntityRegainHealthEvent::CAUSE_MAGIC));
		}
		if((microtime(true)>($this->lastChangeModeTime+10)) and (microtime(true)>($this->lastSkillTime+20)) and $this->skillStatus===null){
			if($this->spawnPos->y===111){
				switch(mt_rand(1,2)){
					case 1:
						if(!isset($this->crystal) or ($this->crystal)<4){
							$allPos=[new Vector3(67.5, 109, 6.5), new Vector3(42.5, 109, 31.5), new Vector3(67.5, 109, 56.5), new Vector3(92.5, 109, 31.5)];
							$nbt = new CompoundTag("", [
							   "Rotation" => new ListTag("Rotation", [
								   new FloatTag("", 0),
								   new FloatTag("", 0)
							   ]),
							]);
							$nbt->Motion = new ListTag("Motion", [
							   new DoubleTag("", 0),
							   new DoubleTag("", 0),
							   new DoubleTag("", 0)
							]);
							foreach($allPos as $index=>$pos){
								if(isset($this->crystal[$index]))continue;
								$this->level->spawnLightning($pos);
								$tmpNbt=clone $nbt;
								$tmpNbt->Pos = new ListTag("Pos", [
									new DoubleTag("", $pos->x),
									new DoubleTag("", $pos->y),
									new DoubleTag("", $pos->z)
								]);
								$Crystal = Entity::createEntity('AEnderCrystal', $this->level, $tmpNbt, $this);
								$Crystal->noCanMove = $index;
								$Crystal->setMaxHealth(50);
								$Crystal->setHealth(50);
								$Crystal->spawnToAll();
								$Crystal->setNameTag('§a异界水晶 §b§l[§e'.$Crystal->getHealth().'§f/§4'.$Crystal->getMaxHealth().'§b§l]§f');
								$Crystal->setNameTagVisible(true);
								$Crystal->setNameTagAlwaysVisible(true);
								$this->crystal[$index] = $Crystal;
							}
							$this->sendMsg('§d暗黑影龙§e重生了它的水晶,再次摧毁它！');
						}
					break;
					case 2:
						$nbt = new CompoundTag;
						$nbt->Rotation = new ListTag("Rotation", [
							 new FloatTag("", 0),
							 new FloatTag("", 0)
						 ]);
						$nbt->Speed = new DoubleTag("Speed", 1.8);
						$data = Main::getInstance()->enConfig['影龙傀儡'];
						foreach($this->level->getPlayers() as $player){
							$nbt->Pos = new ListTag("Pos", [
								new DoubleTag("", $player->x),
								new DoubleTag("", $player->y + 0.5),
								new DoubleTag("", $player->z)
							]);
							$pk = Entity::createEntity('AEnderman', $this->level, $nbt);
							$pk->enConfig = $data;
							$pk->initThis();
							$pk->spawnToAll();
							$pk->setTarget($player);
						}
						$this->sendMsg('§d暗黑影龙§e向玩家召唤了傀儡 击杀傀儡！');
					break;
				}
			}else{
				switch(mt_rand(1,3)){
					case 1:
						$this->sendMsg('§d暗黑影龙§e抛出了TNT,注意躲避！');
						$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
							$nbt = new CompoundTag("", [
						   "Pos" => new ListTag("Pos", [
							   new DoubleTag("", $this->x),
							   new DoubleTag("", $this->y + 1.72),
							   new DoubleTag("", $this->z)
						   ]),
							"Rotation" => new ListTag("Rotation", [
								  new FloatTag("", $this->yaw),
								  new FloatTag("", $this->pitch)
							  ]),
						   ]);
							$x = $this->spawnPos->x - $this->x;
							$y = $this->spawnPos->y - $this->y;
							$z = $this->spawnPos->z - $this->z;
							$diff = abs($x) + abs($z);
							@$yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
							@$pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
							for($i = 0; $i < 10; $i++) {
								$nbt->Motion = new ListTag("Motion", [
									new DoubleTag("", -sin($yaw / 180 * M_PI)  * cos($pitch / 180 * M_PI)+mt_rand(-80, 80)/80),
									new DoubleTag("", 0.4),
									new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)+mt_rand(-80, 80)/80)
								]);
								$pk = Entity::createEntity('PrimedTNT', $this->level, $nbt, false, $this);
								$pk->spawnToAll();
								$pk->setDamage(500);
								$this->level->addSound(new TNTPrimeSound($this));
							}
						}, []), 40);
					break;
					case 2:
						$this->sendMsg('§d暗黑影龙§c开始寻找抓取目标,潜行躲避它的视野！ 两秒后抓取目标', 2);
						$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
							$players=$this->level->getPlayers();
							shuffle($players);
							foreach($players as $player){
								 if(!$player->isSneaking()){
									$this->skillStatus = 1;
									$this->baseTarget = $player;
									$player->sendTitle('§l§a你成了暗黑影龙的抓捕目标','§l§d做好准备！', 0, 40, 0);
									break;
								 }
							}
						}, []), 40);
					break;
					case 3:
						$this->sendMsg('§d暗黑影龙§e抛出了滞留剧毒药水,注意躲避！');
						$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
							$nbt = new CompoundTag("", [
						   "Pos" => new ListTag("Pos", [
							   new DoubleTag("", $this->x),
							   new DoubleTag("", $this->y),
							   new DoubleTag("", $this->z)
						   ]),
							"Rotation" => new ListTag("Rotation", [
								  new FloatTag("", $this->yaw),
								  new FloatTag("", $this->pitch)
							  ]),
						   ]);
							$x = $this->spawnPos->x - $this->x;
							$y = $this->spawnPos->y - $this->y;
							$z = $this->spawnPos->z - $this->z;
							$diff = abs($x) + abs($z);
							@$yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
							@$pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
							for($i = 0; $i < 10; $i++) {
								$nbt->Motion = new ListTag("Motion", [
									new DoubleTag("", -sin($yaw / 180 * M_PI)  * cos($pitch / 180 * M_PI)+mt_rand(-80, 80)/80),
									new DoubleTag("", 0.4),
									new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)+mt_rand(-80, 80)/80)
								]);
								$nbt->PotionId = new ShortTag("PotionId", 26);
								$pk = Entity::createEntity('ThrownPotion', $this->level, $nbt, $this, true);
								$pk->spawnToAll();
								$this->level->addSound(new LaunchSound($this), $this->getViewers());
							}
						}, []), 40);
					break;
				}
			}
			$this->lastSkillTime = microtime(true);
		}
		if((microtime(true)>($this->lastSkillTime+10)) and (microtime(true)>($this->lastChangeModeTime+50)) and $this->skillStatus===null){
			switch(mt_rand(1,5)){
				case 1:
				case 2:
				case 3:
					if($this->spawnPos->y==94)break;
					$this->radius=8;
					$this->spawnPos=new Vector3(67, 94, 31);
					$this->sendMsg('§d暗黑影龙§e降低了坐标来释放技能 攻击它！！');
				break;
				case 4:
				case 5:
					if($this->spawnPos->y==111)break;
					$this->radius=18;
					$this->spawnPos=new Vector3(67, 111, 31);
					$this->sendMsg('§d暗黑影龙§e又飞向了天空，继续接受死亡吧！！');
				break;
			}
			$this->lastChangeModeTime = microtime(true);
		}
		if(count($this->level->getPlayers())<=0){
			$this->level->setTime(18000);
			$this->close();
			return false;
		}
    return true;
	}
	public function getName(){
		return $this->enConfig['名字'];
	}
	
	public function updateMove(){
		if($this->enConfig['名字']!=='暗黑影龙')return parent::updateMove();
		if($this->spawnPos->y!=94 or $this->server->getTick()%2===0 and $this->skillStatus===null){
			$this->pspeed += 1;
			if($this->pspeed > 360)$this->pspeed = 0;
			$cosv = cos(($this->pspeed) * M_PI / 180) * $this->radius;
			$sinv = sin(($this->pspeed) * M_PI / 180) * $this->radius;
			$this->motionX = $cosv - $sinv;
			$this->motionY = 0;
			$this->motionZ = $sinv + $cosv;
			//$moveVector = new Vector3($this->spawnPos->x + $this->motionX, $this->spawnPos->y + $this->motionY, $this->spawnPos->z + $this->motionZ);
			$this->moveTo($this->motionX, $this->motionY, $this->motionZ);
			$this->yaw=(-atan2($this->motionX, $this->motionZ) * 180 / M_PI)+90; 
			$this->updateMovement();
		}elseif($this->skillStatus!==null){
			switch($this->skillStatus){
				case 1:
					if(!($this->baseTarget instanceof Player) or $this->baseTarget->isSneaking() or $this->baseTarget->isSneaking() or $this->baseTarget->getGamemode()!==0 or $this->baseTarget->level!==$this->level or !$this->baseTarget->isA() or $this->baseTarget->closed){
						$this->skillStatus=null;
						$this->baseTarget=null;
						return;
					}
					$x = $this->baseTarget->x - $this->x;
					$y = $this->baseTarget->y - $this->y;
					$z = $this->baseTarget->z - $this->z;
					$diff = abs($x) + abs($z);
					if($x ** 2 + $z ** 2 < 0.7) {
						$this->motionX = 0;
						$this->motionZ = 0;
						$this->motionY = 0;
					} else {
						$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
						$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
						$this->motionY = $this->getSpeed() * 0.15 * ($y / $diff);
					}
					if($x==0 and $z==0){
						$yaw = 0;
						$pitch = $y>0?-90:90;
						if($y==0)$pitch=0;
					}else{
						$this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
						$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
					}
					$dx = $this->motionX ;
					$dz = $this->motionZ ;
					$dy = $this->motionY ;
					$this->move($this->motionX === 0 ? 0 : $dx, $dy, $this->motionZ === 0 ? 0 : $dz);
					$this->updateMovement();
					if($this->distanceSquared($this->baseTarget)<1){
						$pk=new SetEntityLinkPacket();
						$pk->from=$this->getId();
						$pk->to=$this->baseTarget->getId();
						$pk->type=1;
						foreach($this->level->getPlayers() as $p){
							$p->dataPacket(clone $pk);
						}
						$pk->to=0;
						$this->baseTarget->dataPacket($pk);
						$this->baseTarget->setLinkedEntity($this);
						$this->skillStatus=2;
					}
				break;
				case 2:
					$this->pspeed += 1;
					if($this->pspeed > 360)$this->pspeed = 0;
					$cosv = cos(($this->pspeed) * M_PI / 180) * 30;
					$sinv = sin(($this->pspeed) * M_PI / 180) * 30;
					$this->motionX = $cosv - $sinv;
					$this->motionZ = $sinv + $cosv;
					$this->yaw=(-atan2($this->motionX, $this->motionZ) * 180 / M_PI)+90; 
					$this->pitch = 45;
					$this->moveTo($this->motionX, $this->getSpeed()*0.3, $this->motionZ, true);
					$this->updateMovement();
					if($this->y>150)
						$this->skillStatus=3;
				break;
				case 3:
					if($this->y<95){
						$this->skillStatus=null;
						$this->baseTarget->attack(PHP_INT_MAX, new EntityDamageByEntityEvent($this, $this->baseTarget, EntityDamageEvent::CAUSE_ENTITY_ATTACK, PHP_INT_MAX, 0, true));
                        $this->baseTarget->newProgress('死亡前的恐惧', '被末影龙抓着翱翔一圈');
						$this->level->addParticle(new HugeExplodeSeedParticle($this));
						$this->level->addSound(new ExplodeSound($this));
						$pk=new SetEntityLinkPacket();
						$pk->from=$this->getId();
						$pk->to=$this->baseTarget->getId();
						$pk->type=0;
						$this->level->getServer()->broadcastPacket($this->level->getPlayers() ,$pk);
						$this->baseTarget->setLinkedEntity(null);
						$this->baseTarget=null;
						return;
					}
					$x = $this->spawnPos->x - $this->x;
					$y = $this->spawnPos->y - $this->y;
					$z = $this->spawnPos->z - $this->z;
					$diff = abs($x) + abs($z);
					if($x ** 2 + $z ** 2 < 0.7) {
						$this->motionX = 0;
						$this->motionZ = 0;
						$this->motionY = 0;
					} else {
						$this->motionX = $this->getSpeed() * 0.6 * ($x / $diff);
						$this->motionZ = $this->getSpeed() * 0.6 * ($z / $diff);
						$this->motionY = $this->getSpeed() * 0.6 * ($y / $diff);
					}
					if($x==0 and $z==0){
						$yaw = 0;
						$pitch = $y>0?-90:90;
						if($y==0)$pitch=0;
					}else{
						$this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
						$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
					}
					$dx = $this->motionX;
					$dz = $this->motionZ;
					$dy = $this->motionY;
					$this->move($this->motionX === 0 ? 0 : $dx, $dy, $this->motionZ === 0 ? 0 : $dz);
					$this->updateMovement();
				break;
			}
		}
		if($this->server->getTick()%20===0 and !mt_rand(0, 9) and $this->skillStatus===null)
			foreach($this->level->getPlayers() as $player){
				if($player->isA() and !$player->closed and $player->getGamemode()==0)
					$this->attackEntity($player);
			}
	}
	public function moveTo($dx, $dy, $dz, $skill=false){
		$radius = $this->width / 2;
        $this->setComponents($this->spawnPos->x + $dx, $skill?$this->y+$dy:$this->spawnPos->y, $this->spawnPos->z + $dz);
        $this->boundingBox->setBounds($this->x - $radius, $this->y, $this->z - $radius, $this->x + $radius, $this->y + $this->height, $this->z + $radius);
        $this->checkChunks();
	}
	public function attackEntity(Entity $player){
	  if(($this->attackDelay > 20 && mt_rand(1, 32) < 4 && $this->distance($player) <= $this->enConfig['边界范围半径']) or $this->enConfig['名字']=='暗黑影龙'){
			$this->attackDelay = 0;
			$x = $player->x - $this->x;
			$y = $player->y+1 - $this->y;
			$z = $player->z - $this->z;
			$diff = abs($x) + abs($z);
			$yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
			$pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
			$nbt = new CompoundTag("", [
				"Pos" => new ListTag("Pos",[
					new DoubleTag("", $this->x),
					new DoubleTag("", $this->y + 1.2),
					new DoubleTag("", $this->z)
				]),
				"Motion" => new ListTag("Motion",[
					new DoubleTag("", -sin($yaw / 180 * M_PI)  * cos($pitch / 180 * M_PI)),
					new DoubleTag("", -sin($pitch / 180 * M_PI)),
					new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI))
				]),
				"Rotation" => new ListTag("Rotation",[
					new FloatTag("", $this->yaw),
					new FloatTag("", $this->pitch)
				]),
			]);
			$pk = new ADragonFireBall($this->level, $nbt, $this);
			$pk->setExplode(true);
			$pk->setTranslation(true);
			$pk->setDamage($this->enConfig["攻击"]);
			$pk->setMotion($pk->getMotion()->multiply(2));
			$pk->spawnToAll();
			$this->level->addSound(new LaunchSound($this), $this->getViewers());
	  }
	}
 
	public function kill(){
		if($this->enConfig['名字']!=='暗黑影龙')return parent::kill();
		foreach($this->level->getPlayers() as $player){
			$player->getTask()->action('击败末影龙', null);
			switch(mt_rand(0, 2)){
			case 0:
				$item=\LTItem\Main::getInstance()->createMaterial('SS级宠物碎片');
				$player->sendMessage('§a你获得了"SS级宠物碎片"');
			break;
			case 1:
				$item=\LTItem\Main::getInstance()->createMaterial('勇者武器礼包');
				$player->sendMessage('§a你获得了"勇者武器礼包"');
			break;
			case 2:
				$item=\LTItem\Main::getInstance()->createMaterial('春晖碎片');
				$player->sendMessage('§a你获得了"春晖碎片"');
			break;
			}
			$player->getInventory()->addItem($item);
			$s=0;
			if(mt_rand(1,100)==1){
				$player->getInventory()->addItem(Item::get(444));
				$s++;
			}
			if(mt_rand(1,100)==1){
				$player->getInventory()->addItem(Item::get(397, 5));
				$s++;
			}
			if($s>0)$player->sendMessage('§a你获得了'.$s.'个稀有物品');
			$player->sendTitle('§d10秒后返回主城~');
		}
		$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
			foreach($this->level->getPlayers() as $player){
				$player->teleport($this->server->getDefaultLevel()->getSafeSpawn());
			}
		}, []), 200);
		unset($this->attribute);
		$this->level->setTime(6000);
		parent::kill();
		$this->close();
	}    
	public function updateMovement()
    {
        if($this->lastX !== $this->x || $this->lastY !== $this->y || $this->lastZ !== $this->z || $this->lastYaw !== $this->yaw || $this->lastPitch !== $this->pitch) {
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;
            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;
        }
        $yaw = $this->yaw>0?$this->yaw-180:$this->yaw+180;;
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $yaw, $this->pitch, $yaw);
    }
	public function spawnTo(Player $player){
		if($this->enConfig['名字']!=='暗黑影龙')return parent::spawnTo($player);
		if(!isset($this->hasSpawned[$player->getLoaderId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
			parent::spawnTo($player);
			$bpk = new BossEventPacket();
			$bpk->eid = $this->getId();
			$bpk->eventType = 1;
			$player->dataPacket($bpk);
			$pk = new UpdateAttributesPacket();
			$pk->entries[] = $this->attribute;
			$pk->entityId = $this->getId();
			$player->dataPacket($pk);
		}
	}
	public function setHealth($amount){
		parent::setHealth($amount);
		if(!$this->justCreated and isset($this->attribute) and $this->enConfig['名字']=='暗黑影龙'){
			$this->attribute->setValue($amount);
			$pk = new UpdateAttributesPacket();
			$pk->entries[] = $this->attribute;
			$pk->entityId = $this->getId();
			foreach($this->hasSpawned as $player)$player->dataPacket($pk);
		}
	}
	public function getDrops(){
		return [];
	}
}
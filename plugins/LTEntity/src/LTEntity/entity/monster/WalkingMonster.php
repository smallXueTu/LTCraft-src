<?php
namespace LTEntity\entity\monster;

use LTEntity\entity\monster\walking\Enderman;
use LTEntity\entity\WalkingEntity;
use LTEntity\entity\BaseEntity;
use pocketmine\block\Water;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Timings;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\Server;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\level\Position;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\scheduler\CallbackTask;
use pocketmine\nbt\tag\ {ByteTag, ShortTag, CompoundTag, DoubleTag, FloatTag, ListTag, StringTag, IntTag};

abstract class WalkingMonster extends WalkingEntity
{
    public $attackDelay = 0;
    protected $ids = 0;

    public abstract function attackEntity(Entity $player);

    /**
     * 好乱啊  我想重写 但意义不大..
     * TODO: rewrite ti
     * @param $currentTick
     * @return bool
     */
    public function onUpdate($currentTick)
    {
        
        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
		if(!$this->isAlive())
			if(!$this->entityBaseTick($tickDiff))
				return false;
			else{
				return true;
			}
		if($this->vertigoTime>0){
			--$this->vertigoTime;
			if(count($this->level->getPlayers())>0){
				$this->yaw+=30;
				$this->pitch=0;
				$this->updateMovement();
			}
			return true;
		}
        if(count($this->level->getPlayers())<=0) {
            return true;
		}
		if($this->hasSkill)
        switch($this->enConfig['名字']) {
			case '觉醒法老':
				if(!($this->baseTarget instanceof Player) or $this->restTime>0)break;
				if(!isset($this->lastReleaseSkill))$this->lastReleaseSkill = time();
				if(!isset($this->nextSkillTime) or (time() - $this->lastReleaseSkill > $this->nextSkillTime)) {
					$this->setFreeze(3);
					foreach($this->getViewers() as $p)$p->addTitle('§l§a法老将在3秒后冰冻附近玩家', '§d注意远离它！！',0, 60, 0);
					$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
						if($this->closed)return;
						foreach($this->level->getPlayers() as $player) {
							if($player->distanceSquared($this) > 15)continue;
							$player->setFreeze(3);
							$player->sendTitle('§c你被觉醒法老冰冻了！');
						}
					}, []), 60);
					$this->lastReleaseSkill = time();
					$this->nextSkillTime = mt_rand(20, 30);
				}
			break;
            case '守护者-卡拉森':
                if ($this->age % 5 == 0){//0.5s
                    foreach ($this->level->getPlayers() as $player){
                        if ($player->distance($this) < 8 and !$player->getAStatusIsDone("史诗")){
                            $deltaX = $this->x - $player->x;
                            $deltaZ = $this->z - $player->z;
                            $player->knockBack($this, 0, $deltaX, $deltaZ, 5);
                            $player->sendMessage("§c很显然，你不够资格。");
                        }
                    }
                }
            break;
			case '生化统治者':
			if(!($this->baseTarget instanceof Player) or $this->restTime>0)break;
            if(!isset($this->lastReleaseSkill))$this->lastReleaseSkill = time();
            if((!isset($this->nextSkillTime) or time() - $this->lastReleaseSkill > $this->nextSkillTime) and ($this->restTime <= 0 and $this->baseTarget instanceof Player)) {
                switch(mt_rand(1, 6)) {
                case 1:
                case 4:
                case 5:
                    for($i = 0; $i < 10; $i++) {
                        $this->level->spawnLightning($this->add(mt_rand(-10, 10), mt_rand(-10, 10), mt_rand(-10, 10)), 50, $this);
                    }
                    foreach($this->getViewers() as $p)$p->addTitle('§c生化统治者召唤闪电了！', '§d注意由天听令吧！',0, 60, 0);
                    break;
                case 2://TODO 苦力怕爆炸动画
					$count=0;
					foreach($this->level->getEntities() as $entity)
						if($entity instanceof BaseEntity and $entity->enConfig['刷怪点']=='傀儡' and ++$count>=3)break 2;
                    $nbt = new CompoundTag;
                    $nbt->Pos = new ListTag("Pos", [
						new DoubleTag("", $this->x),
						new DoubleTag("", $this->y + 0.5),
						new DoubleTag("", $this->z)
					]);
                    $nbt->Rotation = new ListTag("Rotation", [
						 new FloatTag("", 0),
						 new FloatTag("", 0)
					 ]);
                    $nbt->Speed = new DoubleTag("Speed", 1.8);
                    $plugin = $this->server->getPluginManager()->getPlugin("LTEntity");
                    $data = $plugin->EnConfig['生化统治者傀儡'];
                    for($i = 0; $i < 3-$count; $i++) {
                        $pk = Entity::createEntity('AZombie', $this->level, $nbt);
                        $pk->enConfig = $data;
                        $pk->spawnToAll();
						$pk->setTarget($this->baseTarget);
                        $pk->initThis();
                    }
                    foreach($this->getViewers() as $p)$p->addTitle('§c生化统治者召唤了', ('§d'. (3-$count).'只傀儡,注意闪避！'),0, 60, 0);
                    break;
                case 3:
				case 6:
                    foreach($this->getViewers() as $p)$p->addTitle('§c生化统治者2秒后', '§d抛出TNT,注意躲避！',0, 40, 0);
                    $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
						if($this->closed)return;
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
                        for($i = 0; $i < 8; $i++) {
                            $nbt->Motion = new ListTag("Motion", [
							   new DoubleTag("", mt_rand(-20, 20) / 29),
							   new DoubleTag("", 0.4),
							   new DoubleTag("", mt_rand(-20, 20) / 29)
						   ]);
                            $pk = Entity::createEntity('PrimedTNT', $this->level, $nbt, false, $this);
                            $pk->setDamage(25);
                            $pk->spawnToAll();
                            $this->level->addSound(new TNTPrimeSound($this));
                        }
                    }, []), 40);
                    break;
                }
                $this->lastReleaseSkill = time();
                $this->nextSkillTime = mt_rand(5, 15);
            }
		break;
       case '异界统治者':
			foreach($this->getViewers() as $player){
				if($player instanceof Player and $player->isFlying() and $player->isSurvival()){
					$player->attack(PHP_INT_MAX, new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_SECONDS_KILL, PHP_INT_MAX, 0, true));
					$player->sendMessage('§c你不能在异界统治者面前飞行！');
				}
			}
			if(!($this->baseTarget instanceof Player))break;
            if(!isset($this->lastReleaseSkill))$this->lastReleaseSkill = 0;
            if(!isset($this->nextSkillTime) or time() - $this->lastReleaseSkill > $this->nextSkillTime) {
                switch(mt_rand(1, 3)) {
                case 1:
                    if(!isset($this->crystal))$this->crystal = [];
                    $create = false;
                    $nbt = new CompoundTag("", [
					   "Rotation" => new ListTag("Rotation", [
						   new FloatTag("", 0),
						   new FloatTag("", 0)
					   ]),
				   ]);
                    for($i = 0; $i < 3; $i++) {
                        if(isset($this->crystal[$i]))continue;
                        $create = true;
                        $nbt->Pos = new ListTag("Pos", [
							new DoubleTag("", $this->x + 3 * cos(($i * 120) * 3.14 / 36)),
							new DoubleTag("", $this->y + 1.2),
							new DoubleTag("", $this->z + 3 * sin(($i * 120) * 3.14 / 36))
						]);
                        $nbt->Motion = new ListTag("Motion", [
						   new DoubleTag("", 0),
						   new DoubleTag("", 0),
						   new DoubleTag("", 0)
					   ]);
                        $Crystal = Entity::createEntity('AEnderCrystal', $this->level, $nbt, $this);
                        $Crystal->angle = $i * 120;
                        $Crystal->setDamage(30);
                        $Crystal->setMaxHealth(200);
                        $Crystal->setHealth(200);
                        $Crystal->spawnToAll();
						$Crystal->setName('§a异界水晶');
                        $Crystal->setNameTag('§a异界水晶 §b§l[§e'.$Crystal->getHealth().'§f/§4'.$Crystal->getMaxHealth().'§b§l]§f');
                        $Crystal->setNameTagVisible(true);
                        $Crystal->setNameTagAlwaysVisible(true);
                        $this->crystal[$i] = $Crystal;
                    }
                    if($create){
                        foreach($this->getViewers() as $p)$p->addTitle('§c异界统治者身边将环绕水晶球', '§d注意闪避，击碎它！',0, 60, 0);
					}
                    break;
                case 2:
					$colors=[0 => '白色', 1 => '橙色', 4 => '黄色', 5 => '绿色', 6 => '粉色', 10 => '紫色', 11 => '蓝色', 14 => '红色', 15 => '黑色'];
                    $colorKey = array_rand($colors);
                    $colorName = $colors[$colorKey];
                    $this->targets = [];
                    foreach($this->getViewers() as $p) {
						if(!$p->canSelected() or $p->closed)continue;
                        $this->targets[] = $p;
                        $p->addTitle('§c异界统治者将在5秒后释放秒杀技能', '§d注意站到§a'.$colorName.'§d羊毛上即可躲避！',0, 100, 0);
                    }
                    $this->notMove = true;
                    $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function($colorV) {
						if($this->closed)return;
						$this->DiePlayers = [];
                        foreach($this->targets as $player) {
                            $block = $this->level->getBlock($player->add(0, -1, 0));
                            if($block->getID() != 35 or $block->getDamage() != $colorV){
                                $this->DiePlayers[] = $player;
							}
                        }
						if(count($this->DiePlayers) <= 0) {
							unset($this->notMove, $this->targets, $this->DiePlayers);
							return;
						}
						$this->lastPosition=$this->asPosition();
						$this->KillTask = new CallbackTask(function(){
							if($this->closed)return;
							$player = array_shift($this->DiePlayers);
							if($player instanceof Player and $player->isSurvival() and $this->isViewers($player)){
								$this->teleport($player);
								$player->attack(PHP_INT_MAX, new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_SECONDS_KILL, PHP_INT_MAX, 0,true));
								$this->level->addSound(new \pocketmine\level\sound\EndermanTeleportSound($this), $this->getViewers());
							}
							if(count($this->DiePlayers) <= 0) {
							if($this->closed)return;
								$this->server->getScheduler()->cancelTask($this->KillTask->getTaskId());
								$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
									$this->teleport($this->lastPosition);
									$this->level->addSound(new \pocketmine\level\sound\EndermanTeleportSound($this), $this->getViewers());
									unset($this->KillTask, $this->lastPosition, $this->notMove, $this->targets, $this->DiePlayers);
								 }, []), 5);
							}
						}, []);
						$this->server->getScheduler()->scheduleRepeatingTask($this->KillTask, 0, 5);
                    }, [$colorKey]), 100);
                    break;
                case 3:
                    foreach($this->getViewers() as $p)
                        $p->addTitle('§c异界统治者将在3秒后在脚下周围释放箭', '§d注意跳跃躲避！',0, 60, 0);
                    $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
						if($this->closed)return;
                        $nbt = new CompoundTag("", [
						   "Pos" => new ListTag("Pos", [
							   new DoubleTag("", $this->x),
							   new DoubleTag("", $this->y+0.2),
							   new DoubleTag("", $this->z)
							]),
							"Fire" => new ShortTag("Fire", 0),
							"Potion" => new ShortTag("Potion", 0)
					   ]);
                        for($i = 0; $i < 30; $i++) {
                            $nbt->Motion = new ListTag("Motion", [
							   new DoubleTag("", -sin($i * 12 / 180 * M_PI)),
							   new DoubleTag("", 0),
							   new DoubleTag("", cos($i * 12 / 180 * M_PI))
						   ]);
                            $nbt->Rotation = new ListTag("Rotation", [
								 new FloatTag("", $i * 12),
								 new FloatTag("", 0)
							 ]);
                            $arror = Entity::createEntity("AFalseArrow", $this->getLevel(), $nbt, $this, true);
                            $arror->setMotion($arror->getMotion()->multiply(2));
                            $arror->spawnToAll();
                            $arror->setDamage(50);
                            $this->level->addSound(new BlazeShootSound($this), $this->getViewers());
                        }
                    }, []), 60);
                    break;
                }
                $this->lastReleaseSkill = time();
                $this->nextSkillTime = mt_rand(10, 30);
            }
		break;
		case '冰之神':
			if(!($this->baseTarget instanceof Player))break;
			if(isset($this->InitializeIng) and --$this->InitializeIng>0){
				$players=[];
				foreach($this->level->getPlayers() as $player)
					if($player->distance($this)<5 and $player instanceof Player and $player->isOnline())
						$players[]=$player;
					if(count($players)>0){
						foreach($players as $player){
							$deltaX = $player->x - $this->x;
							$deltaZ = $player->z - $this->z;
							$player->knockBack($player, $this, $deltaX, $deltaZ, 1, true);
						}
						$this->level->addParticle(new \pocketmine\level\particle\HugeExplodeSeedParticle($this));
						$this->level->addSound(new \pocketmine\level\sound\ExplodeSound($this));
					}
				$this->pitch=0;
				$this->yaw+=30;
				$this->updateMovement();
				if($this->server->getTick() % 5 === 0)$this->heal($this->getMaxHealth() * 0.025, new EntityRegainHealthEvent($this, $this->getMaxHealth() * 0.025, EntityRegainHealthEvent::CAUSE_MAGIC));
				return true;
			}
			if(isset($this->crystal)){
				if($this->server->getTick() % 10 === 0)$this->heal(100*count($this->crystal), new EntityRegainHealthEvent($this, 100*count($this->crystal), EntityRegainHealthEvent::CAUSE_MAGIC));
				 break;
			}
			foreach($this->getViewers() as $player){
				if($player instanceof Player and $player->isFlying() and $player->isSurvival()){
					$player->attack(PHP_INT_MAX, new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_SECONDS_KILL, PHP_INT_MAX, 0, true));
					$player->sendMessage('§c你不能在冰之神面前飞行！');
				}
			}
            if(!isset($this->lastReleaseSkill))$this->lastReleaseSkill = 0;
            if(!isset($this->nextSkillTime))$this->nextSkillTime =time() + 10;
            if(time() - $this->lastReleaseSkill > $this->nextSkillTime) {
				switch(mt_rand(1, 3)){
				case 1:
					if(!($this->baseTarget instanceof Player))break;
					 foreach($this->getViewers() as $p)
                        $p->addTitle('§c冰之神3秒抛出冰冻抛射物', '§d你可以用武器反弹！',0, 60, 0);
					$this->setFreeze(3);
                    $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
						if($this->closed)return;
						$nbt = new CompoundTag("", [
							"Pos" => new ListTag("Pos",[
								new DoubleTag("", $this->x),
								new DoubleTag("", $this->y + 1.2),
								new DoubleTag("", $this->z)
							]),
							"Motion" => new ListTag("Motion",[
								new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
								new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
							]),
							"Rotation" => new ListTag("Rotation",[
								new FloatTag("", $this->yaw),
								new FloatTag("", $this->pitch)
							]),
						]);
						$pk = new \LTEntity\entity\projectile\ABlueWitherSkull($this->level, $nbt, $this);
						$pk->setRebound(true);
						$pk->setMotion($pk->getMotion()->multiply(2));
						$pk->setDamage(0);
						$pk->spawnToAll();
						$pk->skill=['Freeze', 3];
						$this->level->addSound(new \pocketmine\level\sound\LaunchSound($this), $this->getViewers());
                    }, []), 60);
				break;
				case 2:
					 foreach($this->getViewers() as $p)
                        $p->addTitle('§c冰之神3秒后旋转发射致命箭', '§d请注意跳跃或走位躲避！',0, 60, 0);
					$this->teleport(new Position($this->enConfig['x'], $this->enConfig['y'], $this->enConfig['z'], $this->level));
					$this->notMove=false;
					$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
					if($this->closed){
						if(isset($this->launchFun) and $this->launchFun instanceof CallbackTask)$this->server->getScheduler()->cancelTask($this->launchFun->getTaskId());
						unset($this->launchFun, $this->angle, $this->notMove);
					}
						$this->angle=1;
						$this->pitch=0;
						$this->yaw=0;
						$this->launchFun=new CallbackTask(function(){
							$this->angle++;
							$this->yaw=$this->angle*12;
							$this->updateMovement();
							$nbt = new CompoundTag("", [
							   "Pos" => new ListTag("Pos", [
								   new DoubleTag("", $this->x),
								   new DoubleTag("", $this->y+0.2),
								   new DoubleTag("", $this->z)
								]),
								"Fire" => new ShortTag("Fire", 0),
								"Potion" => new ShortTag("Potion", 0)
						   ]);
							$nbt->Motion = new ListTag("Motion", [
							   new DoubleTag("", -sin($this->angle * 12 / 180 * M_PI)),
							   new DoubleTag("", 0),
							   new DoubleTag("", cos($this->angle * 12 / 180 * M_PI))
						   ]);
							$nbt->Rotation = new ListTag("Rotation", [
								 new FloatTag("", $this->angle * 12),
								 new FloatTag("", 0)
							]);
							$arror = Entity::createEntity("AFalseArrow", $this->getLevel(), $nbt, $this, true);
							$arror->setMotion($arror->getMotion()->multiply(2));
							$arror->setDamage(100);
							$arror->spawnToAll();
							$this->level->addSound(new BlazeShootSound($this), $this->getViewers());
							if($this->angle==30){
								$this->server->getScheduler()->cancelTask($this->launchFun->getTaskId());
								unset($this->launchFun, $this->angle, $this->notMove);
								$this->lastAttackTime=$this->server->getTick();
							}
						}, []);
						$this->server->getScheduler()->scheduleRepeatingTask($this->launchFun, 4, 0);
					}, []), 60);
				break;
				case 3:
					 foreach($this->getViewers() as $p)
                        $p->addTitle('§c冰之神3秒后释放三个水晶', '§d碰到将被冰冻,请闪避,或击碎它！'.PHP_EOL .'§e注意:水晶存在时将会恢复生命值！',0, 60, 0);
						$this->teleport(new Position($this->enConfig['x'], $this->enConfig['y'], $this->enConfig['z'], $this->level));
						$this->notMove=true;
						$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
							if($this->closed)return;
						$nbt = new CompoundTag("", [
						   "Rotation" => new ListTag("Rotation", [
							   new FloatTag("", 0),
							   new FloatTag("", 0)
						   ]),
					   ]);
					   $this->crystal=[];
						$this->skilling=true;
						for($i = 1; $i <= 3; $i++) {
							$nbt->Pos = new ListTag("Pos", [
								new DoubleTag("", $this->x + $i*1.5 * cos(($i * 120) * 3.14 / 36)),
								new DoubleTag("", $this->y + 1.5),
								new DoubleTag("", $this->z + $i*1.5 * sin(($i * 120) * 3.14 / 36))
							]);
							$nbt->Motion = new ListTag("Motion", [
							   new DoubleTag("", 0),
							   new DoubleTag("", 0),
							   new DoubleTag("", 0)
						   ]);
							$Crystal = Entity::createEntity('AEnderCrystal', $this->level, $nbt, $this);
							$Crystal->angle = $i * 90;
							$Crystal->setDamage(50);
							$Crystal->skill = ['Freeze', 3];
							$Crystal->radius = $i*2;
							$Crystal->m = 90;//旋转速度
							$Crystal->speed = 6-$i;
							$Crystal->setMaxHealth(300);
							$Crystal->setHealth(300);
							$Crystal->spawnToAll();
							$Crystal->setName('§a冰冻水晶');
							$Crystal->setNameTag('§a冰冻水晶 §b§l[§e'.$Crystal->getHealth().'§f/§4'.$Crystal->getMaxHealth().'§b§l]§f');
							$Crystal->setNameTagVisible(true);
							$Crystal->setNameTagAlwaysVisible(true);
							$this->crystal[$i]=$Crystal;
						}
					}, []), 60);
				break;
				}
                $this->lastReleaseSkill = time();
                $this->nextSkillTime = mt_rand(10, 30);
			}
		break;
		case '怪魔制造者':
			if(!($this->baseTarget instanceof Player))break;
			if($this->baseTarget instanceof Player and $this->baseTarget->isFlying() and $this->baseTarget->isSurvival()){
				$this->baseTarget->attack(PHP_INT_MAX, new EntityDamageByEntityEvent($this, $this->baseTarget, EntityDamageEvent::CAUSE_SECONDS_KILL, PHP_INT_MAX, 0, true));
				$this->baseTarget->sendMessage('§c你不能在怪魔制造者面前飞行！');
			}
            if(!isset($this->lastReleaseSkill))$this->lastReleaseSkill = 0;
            if(!isset($this->nextSkillTime) or time() - $this->lastReleaseSkill > $this->nextSkillTime) {
				$additionalTime=0;
				switch(mt_rand(1,  3)){
					case 1://TODO Improve it
						if($this->baseTarget instanceof Player){
							$this->SkillCount = 0;
							$this->Blocks = [];
							$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_EVOKER_SPELL, true);
							$this->baseTarget->diePos=$this->baseTarget->asPosition();
							foreach($this->getViewers() as $p)
								$p->addTitle('§c怪魔制造者将挖空脚下方块', '§d请不断走动来躲避！',0, 60, 0);
							$this->notMove=false;
							$additionalTime+=7;
							$this->KillTask = new CallbackTask(function(){
								if(!($this->baseTarget instanceof Player) or $this->baseTarget->level->getName()!=='f6' or $this->closed or !$this->baseTarget->isA()){
									$this->server->getScheduler()->cancelTask($this->KillTask->getTaskId());
									$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_EVOKER_SPELL, false);
									unset($this->notMove, $this->Blocks, $this->baseTarget->diePos);
									return;
								}
								$y=$this->baseTarget->y+1;
								do{
									$block = $this->level->getBlock(new Vector3($this->baseTarget->x ,--$y , $this->baseTarget->z));
									if($block->canPassThrough())continue;
									$pk = new UpdateBlockPacket();
									$pk->x = (int)$block->x;
									$pk->z = (int)$block->z;
									$pk->y = (int)$block->y;
									$pk->blockId = 0;
									$pk->blockData = 0;
									$ypk=clone $pk;
									$ypk->blockId = $block->getID();
									$ypk->blockData = $block->getDamage();
									$this->Blocks[]=$ypk;
									$this->baseTarget->dataPacket($pk);
								}while($y>0);
								if(count($this->Blocks)>=30){
									foreach($this->Blocks as $pk){
										$this->baseTarget->dataPacket($pk);
									}
									$this->server->getScheduler()->cancelTask($this->KillTask->getTaskId());
									$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_EVOKER_SPELL, false);
									unset($this->notMove, $this->Blocks, $this->baseTarget->diePos);
								}
							}, []);
							$this->server->getScheduler()->scheduleRepeatingTask($this->KillTask, 5, 5);
						}
					break;
					case 2:
						if($this->baseTarget instanceof Player){
							foreach($this->getViewers() as $p)
								$p->addTitle('§c怪魔制造者3秒抛出眩晕抛射物', '§d请用武器反弹！',0, 60, 0);
							$this->setFreeze(3);
							$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_EVOKER_SPELL, true);
							$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
								if($this->closed)return;
								$nbt = new CompoundTag("", [
									"Pos" => new ListTag("Pos",[
										new DoubleTag("", $this->x),
										new DoubleTag("", $this->y + 1.2),
										new DoubleTag("", $this->z)
									]),
									"Motion" => new ListTag("Motion",[
										new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
										new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
										new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
									]),
									"Rotation" => new ListTag("Rotation",[
										new FloatTag("", $this->yaw),
										new FloatTag("", $this->pitch)
									]),
								]);
								$pk = new \LTEntity\entity\projectile\ABlueWitherSkull($this->level, $nbt, $this);
								$pk->setRebound(true);
								$pk->setMotion($pk->getMotion()->multiply(2));
								$pk->setDamage(0);
								$pk->spawnToAll();
								$pk->skill=['Vertigo', 3];
								$this->level->addSound(new \pocketmine\level\sound\LaunchSound($this), $this->getViewers());
								$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_EVOKER_SPELL, false);
							}, []), 60);
						}
					break;
					case 3:
						if($this->baseTarget instanceof Player){
							foreach($this->getViewers() as $p)$p->addTitle('§c怪魔制造者召唤了', '§d3只傀儡,注意闪避！',0, 60, 0);
							$plugin = $this->server->getPluginManager()->getPlugin("LTEntity");
							$data = $plugin->EnConfig['傀儡虫'];
							$nbt = new CompoundTag;
							$nbt->Pos = new ListTag("Pos", [
								new DoubleTag("", $this->baseTarget->x+mt_rand(-5, 5)),
								new DoubleTag("", $this->baseTarget->y + 0.5),
								new DoubleTag("", $this->baseTarget->z+mt_rand(-5, 5))
							]);
							$nbt->Rotation = new ListTag("Rotation", [
								 new FloatTag("", 0),
								 new FloatTag("", 0)
							 ]);
							$nbt->Speed = new DoubleTag("Speed", 1.8);
							for($i = 0; $i < 3; $i++) {
								$pk = Entity::createEntity('ASilverfish', $this->level, $nbt);
								$pk->enConfig = $data;
								$pk->setTarget($this->baseTarget);
								$pk->spawnToAll();
								$pk->initThis();
							}
						}
					break;
				}
                $this->lastReleaseSkill = time();
                $this->nextSkillTime = mt_rand(10, 25)+$additionalTime;
			}
		break;
		case '碎骨者':
		case '灭世者':
		case '命末神':
		case '死神审判者':
			if(!($this->baseTarget instanceof Player))break;
			if(isset($this->notMove) and isset($this->pk) and $this->pk->hadCollision)
				unset($this->notMove, $this->pk);
			if(!isset($this->lastReleaseSkill))$this->lastReleaseSkill = 0;
            if(!isset($this->nextSkillTime))$this->nextSkillTime =  time() + 10;
			if(time() - $this->lastReleaseSkill > $this->nextSkillTime and $this->baseTarget instanceof Player) {
				$this->setFreeze(3);
				$pk = new MobEquipmentPacket();
				$pk->eid = $this->getId();
				$item=Item::get(261);
				$this->addEnchant($item);
				$pk->item = $item;
				$pk->slot = 10;
				$pk->selectedSlot = 10;
				 foreach($this->getViewers() as $p)
					 $p->dataPacket($pk);
				 $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, true);
				switch(mt_rand(1,5)){
					case 1://冰冻
						$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
							if($this->closed)return;
							$nbt = new CompoundTag("", [
								"Pos" => new ListTag("Pos",[
									new DoubleTag("", $this->x),
									new DoubleTag("", $this->y + 1.2),
									new DoubleTag("", $this->z)
								]),
								"Motion" => new ListTag("Motion",[
									new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
									new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
									new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
								]),
								"Rotation" => new ListTag("Rotation",[
									new FloatTag("", $this->yaw),
									new FloatTag("", $this->pitch)
								]),
							]);
							$pk = new \pocketmine\entity\falseArrow($this->level, $nbt, $this);
							$pk->setMotion($pk->getMotion()->multiply(2));
							$pk->setDamage(30);
							$pk->spawnToAll();
							$pk->skill=['Freeze', 3];
							$this->level->addSound(new \pocketmine\level\sound\LaunchSound($this), $this->getViewers());
							$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
							$pk = new MobEquipmentPacket();
							$pk->eid = $this->getId();
							if($this->enConfig['手持ID']!==false){
								$ids=explode(':', $this->enConfig['手持ID']);
								$item=Item::get($ids[0], $ids[1]);
								if($ids[2]==true)$this->addEnchant($item);
								$pk->item = $item;
							}else $pk->item=Item::get(0);
							$pk->slot = 10;
							$pk->selectedSlot = 10;
							foreach($this->getViewers() as $p)
								$p->dataPacket($pk);
						}, []), 60);
					break;
					case 2://眩晕	
					$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
						if($this->closed)return;
						$nbt = new CompoundTag("", [
							"Pos" => new ListTag("Pos",[
								new DoubleTag("", $this->x),
								new DoubleTag("", $this->y + 1.2),
								new DoubleTag("", $this->z)
							]),
							"Motion" => new ListTag("Motion",[
								new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
								new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
							]),
							"Rotation" => new ListTag("Rotation",[
								new FloatTag("", $this->yaw),
								new FloatTag("", $this->pitch)
							]),
						]);
						$pk = new \pocketmine\entity\falseArrow($this->level, $nbt, $this);
						$pk->setMotion($pk->getMotion()->multiply(2));
						$pk->setDamage(30);
						$pk->spawnToAll();
						$pk->skill=['Vertigo', 1.5];
						$this->level->addSound(new \pocketmine\level\sound\LaunchSound($this), $this->getViewers());
						$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
						$pk = new MobEquipmentPacket();
						$pk->eid = $this->getId();
						if($this->enConfig['手持ID']!==false){
							$ids=explode(':', $this->enConfig['手持ID']);
							$item=Item::get($ids[0], $ids[1]);
							if($ids[2]==true)$this->addEnchant($item);
							$pk->item = $item;
						}else $pk->item=Item::get(0);
						$pk->slot = 10;
						$pk->selectedSlot = 10;
						foreach($this->getViewers() as $p)
							$p->dataPacket($pk);
						}, []), 60);
					break;
					case 3://虚弱
						$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
							if($this->closed)return;
						$nbt = new CompoundTag("", [
							"Pos" => new ListTag("Pos",[
								new DoubleTag("", $this->x),
								new DoubleTag("", $this->y + 1.2),
								new DoubleTag("", $this->z)
							]),
							"Motion" => new ListTag("Motion",[
								new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
								new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
							]),
							"Rotation" => new ListTag("Rotation",[
								new FloatTag("", $this->yaw),
								new FloatTag("", $this->pitch)
							]),
							"Potion" => new ShortTag("Potion", 36)
						]);
						$pk = new \pocketmine\entity\falseArrow($this->level, $nbt, $this);
						$pk->setMotion($pk->getMotion()->multiply(2));
						$pk->setDamage(30);
						$pk->skill=['weak', 60];
						$pk->spawnToAll();
						$this->level->addSound(new \pocketmine\level\sound\LaunchSound($this), $this->getViewers());
						$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
						$pk = new MobEquipmentPacket();
						$pk->eid = $this->getId();
						if($this->enConfig['手持ID']!==false){
							$ids=explode(':', $this->enConfig['手持ID']);
							$item=Item::get($ids[0], $ids[1]);
							if($ids[2]==true)$this->addEnchant($item);
							$pk->item = $item;
						}else $pk->item=Item::get(0);
						$pk->slot = 10;
						$pk->selectedSlot = 10;
						foreach($this->getViewers() as $p)
							$p->dataPacket($pk);
						}, []), 60);
					break;
					case 4://力量
					$this->pitch=-90;
					$this->notMove=false;
					$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
						if($this->closed)return;
						$nbt = new CompoundTag("", [
							"Pos" => new ListTag("Pos",[
								new DoubleTag("", $this->x),
								new DoubleTag("", $this->y + 1.2),
								new DoubleTag("", $this->z)
							]),
							"Motion" => new ListTag("Motion",[
								new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
								new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
							]),
							"Rotation" => new ListTag("Rotation",[
								new FloatTag("", $this->yaw),
								new FloatTag("", $this->pitch)
							]),
						"Potion" => new ShortTag("Potion", 34)
						]);
						$pk = new \pocketmine\entity\falseArrow($this->level, $nbt, $this);
						$pk->setMotion($pk->getMotion()->multiply(2));
						$pk->skill=['Power', 60];
						$pk->spawnToAll();
						$this->pk=$pk;
						$this->level->addSound(new \pocketmine\level\sound\LaunchSound($this), $this->getViewers());
						$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
						$pk = new MobEquipmentPacket();
						$pk->eid = $this->getId();
						if($this->enConfig['手持ID']!==false){
							$ids=explode(':', $this->enConfig['手持ID']);
							$item=Item::get($ids[0], $ids[1]);
							if($ids[2]==true)$this->addEnchant($item);
							$pk->item = $item;
						}else $pk->item=Item::get(0);
						$pk->slot = 10;
						$pk->selectedSlot = 10;
						foreach($this->getViewers() as $p)
							$p->dataPacket($pk);
						}, []), 60);
					break;
					case 5://恢复
					$this->pitch=-90;
					$this->notMove=false;
					$this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
						if($this->closed)return;
						$nbt = new CompoundTag("", [
							"Pos" => new ListTag("Pos",[
								new DoubleTag("", $this->x),
								new DoubleTag("", $this->y + 1.2),
								new DoubleTag("", $this->z)
							]),
							"Motion" => new ListTag("Motion",[
								new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
								new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
							]),
							"Rotation" => new ListTag("Rotation",[
								new FloatTag("", $this->yaw),
								new FloatTag("", $this->pitch)
							]),
						"Potion" => new ShortTag("Potion", 32)
						]);
						$pk = new \pocketmine\entity\falseArrow($this->level, $nbt, $this);
						$pk->setMotion($pk->getMotion()->multiply(2));
						$pk->spawnToAll();
						$pk->skill=['Recover', 0];
						$this->pk=$pk;
						$this->level->addSound(new \pocketmine\level\sound\LaunchSound($this), $this->getViewers());
						$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
						$pk = new MobEquipmentPacket();
						$pk->eid = $this->getId();
						if($this->enConfig['手持ID']!==false){
							$ids=explode(':', $this->enConfig['手持ID']);
							$item=Item::get($ids[0], $ids[1]);
							if($ids[2]==true)$this->addEnchant($item);
							$pk->item = $item;
						}else $pk->item=Item::get(0);
						$pk->slot = 10;
						$pk->selectedSlot = 10;
						foreach($this->getViewers() as $p)
							$p->dataPacket($pk);
						}, []), 60);
					break;
				}
				$this->updateMovement();
			$this->lastReleaseSkill = time();
			$this->nextSkillTime = mt_rand(5, 15);
			}
			break;
		}
     if(!$this->entityBaseTick($tickDiff)){
		 return false;
	 }
		$this->attackDelay += $tickDiff;
		if(isset($this->notMove)){
			if($this->notMove===true){
				$this->pitch=0;
				$this->yaw+=10;
				$this->updateMovement();
			}
			return true;
		}
        if(($target = $this->updateMove()) instanceof Player and $target->getGamemode()==0) {
            $this->attackEntity($target);
        }elseif($target instanceof Vector3 && (($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1) {
            $this->moveTime = 0;
        }
		
        return true;
    }
}
<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\entity;


use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\level\MovingObjectPosition;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;

abstract class Projectile extends Entity {

	const DATA_SHOOTER_ID = 17;

	/** @var Entity */
	public $shootingEntity = null;
	protected $damage = 0;

	public $hadCollision = false;
	public $skill = [null, null];
	public $calculate = true;
	public $zs = false;

	/**
	 * Projectile constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 * @param Entity|null $shootingEntity
	 */
	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		$this->shootingEntity = $shootingEntity;
		if($shootingEntity !== null){
			$this->setDataProperty(self::DATA_SHOOTER_ID, self::DATA_TYPE_LONG, $shootingEntity->getId());
		}
		parent::__construct($level, $nbt);
	}
	
	public function setDamage($damage){
		$this->damage=$damage;
	}
	
	public function setCalculate($v){
		$this->calculate=$v;
	}
	/**
	 * @param float             $damage
	 * @param EntityDamageEvent $source
	 *
	 * @return bool|void
	 */
	public function attack($damage, EntityDamageEvent $source){
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($damage, $source);
		}
	}

	protected function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(1);
		$this->setHealth(1);
		if(isset($this->namedtag->Age)){
			$this->age = $this->namedtag["Age"];
		}

	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canCollideWith(Entity $entity){
		return ($entity instanceof Living and !$this->onGround) and ($entity!==$this->shootingEntity or $this->ticksLived > 5);
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->Age = new ShortTag("Age", $this->age);
	}

	/**
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}
		
		if($this->getServer()->getTicksPerSecond()<10 or ($this->getServer()->getTicksPerSecondAverage()<18 and $this->age>10)){
			$this->kill();
			$this->hadCollision = true;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		if($tickDiff <= 0 and !$this->justCreated){
			return true;
		}
		$this->lastUpdate = $currentTick;

		$hasUpdate = $this->entityBaseTick($tickDiff);

		if($this->isAlive()){

			$movingObjectPosition = null;

			if(!$this->isCollided){
				$this->motionY -= $this->gravity;
			}

			$moveVector = new Vector3($this->x + $this->motionX, $this->y + $this->motionY, $this->z + $this->motionZ);

			$list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);

			$nearDistance = PHP_INT_MAX;
			$nearEntity = null;

			foreach($list as $entity){
				if(/*!$entity->canCollideWith($this) or */
				($entity === $this->shootingEntity and $this->ticksLived < 5)
				){
					continue;
				}

				$axisalignedbb = $entity->boundingBox->grow(0.3, 0.3, 0.3);
				$ob = $axisalignedbb->calculateIntercept($this, $moveVector);

				if($ob === null){
					continue;
				}

				$distance = $this->distanceSquared($ob->hitVector);

				if($distance < $nearDistance){
					$nearDistance = $distance;
					$nearEntity = $entity;
				}
			}

			if($nearEntity !== null){
				$movingObjectPosition = MovingObjectPosition::fromEntity($nearEntity);
			}

			if($movingObjectPosition !== null){
				if($movingObjectPosition->entityHit !== null){

					$this->server->getPluginManager()->callEvent(new ProjectileHitEvent($this));

					$motion = sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2);
					if($this->calculate)
						$damage = ceil($motion * $this->damage);
					else 
						$damage=$this->damage;

					if($this instanceof Arrow and $this->isCritical() and $this->calculate) {
						$damage += mt_rand(0, (int) ($damage / 2) + 1);
					}

					if($this->shootingEntity === null){
						$ev = new EntityDamageByEntityEvent($this, $movingObjectPosition->entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage, $this->zs);
					}else{
						$ev = new EntityDamageByChildEntityEvent($this->shootingEntity, $this, $movingObjectPosition->entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage, $this->zs);
					}

					if($movingObjectPosition->entityHit->attack($ev->getFinalDamage(), $ev) === true){
						if($this instanceof Arrow and $this->getPotionId() != 0){
							foreach(Potion::getEffectsById($this->getPotionId() - 1) as $effect){
								$movingObjectPosition->entityHit->addEffect($effect->setDuration($effect->getDuration() / 8));
							}
						}

						if($movingObjectPosition->entityHit instanceof Creature){
							switch($this->skill[0]){
							case 'Freeze':
								$movingObjectPosition->entityHit->setFreeze((int)$this->skill[1]);
								if($movingObjectPosition->entityHit instanceof \pocketmine\Player)$movingObjectPosition->entityHit->sendTitle(('§c你被§e'.($this->shootingEntity->getName()).'§c冰冻了'));
								if($this->shootingEntity instanceof \pocketmine\Player)$this->shootingEntity->sendTitle(('§a成功冰冻§e'.($movingObjectPosition->entityHit instanceof \LTEntity\entity\BaseEntity?$movingObjectPosition->entityHit->enConfig['名字']:$movingObjectPosition->entityHit->getName())));
							break;
							case 'Vertigo':
								$movingObjectPosition->entityHit->setVertigo((int)$this->skill[1]);
								if($movingObjectPosition->entityHit instanceof \pocketmine\Player)$movingObjectPosition->entityHit->sendTitle(('§c你被§e'.($this->shootingEntity->getName()).'§c眩晕了'));
								if($this->shootingEntity instanceof \pocketmine\Player)$this->shootingEntity->sendTitle(('§a成功眩晕§e'.($movingObjectPosition->entityHit instanceof \LTEntity\entity\BaseEntity?$movingObjectPosition->entityHit->enConfig['名字']:$movingObjectPosition->entityHit->getName())));
							break;
							case 'Injured':
								$movingObjectPosition->entityHit->setInjured((int)$this->skill[1]);
								if($movingObjectPosition->entityHit instanceof \pocketmine\Player)$movingObjectPosition->entityHit->sendTitle(('§c你被§e'.($this->shootingEntity->getName()).'§c附加了重伤效果'));
								if($this->shootingEntity instanceof \pocketmine\Player)$this->shootingEntity->sendTitle(('§a成功为§e'.($movingObjectPosition->entityHit instanceof \LTEntity\entity\BaseEntity?$movingObjectPosition->entityHit->enConfig['名字']:$movingObjectPosition->entityHit->getName()).'§c附加了重伤效果'));
							break;
							case 'weak':
								$movingObjectPosition->entityHit->addEffect(\pocketmine\entity\Effect::getEffect(18)->setDuration($this->skill[1]*20)->setAmplifier(1));
								if($movingObjectPosition->entityHit instanceof \pocketmine\Player)$movingObjectPosition->entityHit->sendTitle(('§c你被§e'.($this->shootingEntity->getName()).'§c附加了虚弱效果'));
								if($this->shootingEntity instanceof \pocketmine\Player)$this->shootingEntity->sendTitle(('§a成功为§e'.($movingObjectPosition->entityHit instanceof \LTEntity\entity\BaseEntity?$movingObjectPosition->entityHit->enConfig['名字']:$movingObjectPosition->entityHit->getName()).'§c附加了虚弱效果'));
							break;
							case 'Power':
								$movingObjectPosition->entityHit->addEffect(\pocketmine\entity\Effect::getEffect(5)->setDuration($this->skill[1]*20)->setAmplifier(2));
							break;
							case 'Recover':
								$movingObjectPosition->entityHit->addEffect(\pocketmine\entity\Effect::getEffect(6)->setDuration(1)->setAmplifier(255));
							break;
							}
						}
						$ev->useArmors();
					}

					$this->hadCollision = true;// 发生了碰撞。

					if($this->fireTicks > 0){
						$ev = new EntityCombustByEntityEvent($this, $movingObjectPosition->entityHit, 5);
						$this->server->getPluginManager()->callEvent($ev);
						if(!$ev->isCancelled()){
							$movingObjectPosition->entityHit->setOnFire($ev->getDuration());
						}
					}

					$this->kill();
					return true;
				}
			}

			$this->move($this->motionX, $this->motionY, $this->motionZ);

			if($this->isCollided and !$this->hadCollision){
				$this->hadCollision = true;

				$this->motionX = 0;
				$this->motionY = 0;
				$this->motionZ = 0;

				$this->server->getPluginManager()->callEvent(new ProjectileHitEvent($this));
			}elseif(!$this->isCollided and $this->hadCollision){
				$this->hadCollision = false;
			}

			if(!$this->onGround or abs($this->motionX) > 0.00001 or abs($this->motionY) > 0.00001 or abs($this->motionZ) > 0.00001){
				$f = sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
				$this->yaw = (atan2($this->motionX, $this->motionZ) * 180 / M_PI);
				$this->pitch = (atan2($this->motionY, $f) * 180 / M_PI);
				$hasUpdate = true;
			}

			$this->updateMovement();

		}

		return $hasUpdate;
	}

}
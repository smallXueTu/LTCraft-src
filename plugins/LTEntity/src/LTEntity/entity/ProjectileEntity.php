<?php

namespace LTEntity\entity;

use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\entity\Creature;
use pocketmine\math\Vector3;
use pocketmine\math\Math;
use pocketmine\Player;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\level\MovingObjectPosition;
use LTEntity\entity\projectile\ADragonFireBall;

abstract class ProjectileEntity extends Projectile
{

    public $movingObjectPosition = null;
    public $translation = false;
	public $skill=[null, null];
    public function saveNBT() {}
    public function attack($damage, EntityDamageEvent $ev)
    {
        if(!$this->canRebound or !isset($this->DisappearTime))return;
        if($ev instanceof EntityDamageByEntityEvent and ($player = $ev->getDamager()) instanceof Player) {
            $this->setMotion(new Vector3(-sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI), -sin($player->pitch / 180 * M_PI), cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)));
            $this->shootingEntity = $player;
            $this->damage*=10;
            unset($this->DisappearTime);
        }
    }
	public function setTranslation($v){
		$this->translation=$v;
	}
    public function onUpdate($currentTick)
    {
        if($this->closed) {
            return false;
        }
		if($this->getServer()->getTicksPerSecondAverage()<18 and $this->age>40)
			$this->kill();

        $tickDiff = $currentTick - $this->lastUpdate;
        if($tickDiff <= 0 and !$this->justCreated) {
            return true;
        }
        $this->lastUpdate = $currentTick;

        $hasUpdate = $this->entityBaseTick($tickDiff);
        if(isset($this->DisappearTime)) {
            if(--$this->DisappearTime === 0) {
                $this->kill();
                $this->attackOccurred($this->attackTimeOut[0],  $this->attackTimeOut[1]);
            }
            return true;
        }
        if($this->isAlive()) {

            $movingObjectPosition = null;

            if(!$this->isCollided and !($this->translation)) { //是相撞的。
                $this->motionY -= $this->gravity;
            }

            $moveVector = new Vector3($this->x + $this->motionX, $this->y + $this->motionY, $this->z + $this->motionZ);

            $list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);

            $nearDistance = PHP_INT_MAX;
            $nearEntity = null;

            foreach($list as $entity) {
				
                if($entity === $this->shootingEntity) { 
                    continue;
                }

                $axisalignedbb = $entity->boundingBox->grow(0.3, 0.3, 0.3);
                $ob = $axisalignedbb->calculateIntercept($this, $moveVector);

                if($ob === null) {
                    continue;
                }

                $distance = $this->distanceSquared($ob->hitVector);

                if($distance < $nearDistance) {
                    $nearDistance = $distance;
                    $nearEntity = $entity;
                }
            }

            if($nearEntity !== null) { //近
                $movingObjectPosition = MovingObjectPosition::fromEntity($nearEntity);
            }

            if($movingObjectPosition !== null) {
                if($movingObjectPosition->entityHit !== null) {
                    $motion = sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2);
                    $damage = ceil($motion * $this->damage);

                    if($this instanceof Arrow and $this->isCritical()) {
                        $damage += mt_rand(0, (int) ($damage / 2) + 1);
                    }

                    if($this->canRebound and $this->shootingEntity instanceof BaseEntity) {
                        $this->attackTimeOut = [$movingObjectPosition->entityHit, $damage];
                        $results = false;
                    } else
                        $results = $this->attackOccurred($movingObjectPosition->entityHit, $damage);
					
                    /* if($results === true) {
                       if($this instanceof Arrow and $this->getPotionId() != 0) {
                            foreach(Potion::getEffectsById($this->getPotionId() - 1) as $effect) {
                                $movingObjectPosition->entityHit->addEffect($effect->setDuration($effect->getDuration() / 8));
                            }
                        }
                    }*/

                    $this->hadCollision = true;

                    if($this->shootingEntity instanceof Player or !$this->canRebound) {
                        $this->kill();
                    } else $this->DisappearTime = 8;
                    return true;
                }
            }

            $this->move($this->motionX, $this->motionY, $this->motionZ);

            if($this->isCollided and !$this->hadCollision) {
                $this->hadCollision = true;

                $this->motionX = 0;
                $this->motionY = 0;
                $this->motionZ = 0;
            }elseif(!$this->isCollided and $this->hadCollision) {
                $this->hadCollision = false;
            }

            if(!$this->onGround or abs($this->motionX) > 0.00001 or abs($this->motionY) > 0.00001 or abs($this->motionZ) > 0.00001) {
                $f = sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
                $this->yaw = (atan2($this->motionX, $this->motionZ) * 180 / M_PI);
                $this->pitch = (atan2($this->motionY, $f) * 180 / M_PI);
                $hasUpdate = true;
            }

            $this->updateMovement();

        }

        return $hasUpdate;
    }

    public function attackOccurred($target, $damage)
    {

        if($target === null) return false;
        if($this->shootingEntity === null) {
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        } else {
            $ev = new EntityDamageByChildEntityEvent($this->shootingEntity, $this, $target, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        }
		if($target instanceof Creature){
			switch($this->skill[0]){
			case 'Freeze':
				$target->setFreeze((int)$this->skill[1]);
				if($target instanceof \pocketmine\Player)$target->sendTitle(('§c你被§e'.($this->shootingEntity instanceof BaseEntity?$this->shootingEntity->enConfig['名字']:$this->shootingEntity->getName()).'§c冰冻了'));
				if($this->shootingEntity instanceof \pocketmine\Player)$this->shootingEntity->sendTitle(('§a成功冰冻§e'.($target instanceof BaseEntity?$target->enConfig['名字']:$target->getName())));
			break;
			case 'Vertigo':
				$target->setVertigo((int)$this->skill[1]);
				if($target instanceof \pocketmine\Player)$target->sendTitle(('§c你被§e'.($this->shootingEntity instanceof BaseEntity?$this->shootingEntity->enConfig['名字']:$this->shootingEntity->getName()).'§c眩晕了'));
				if($this->shootingEntity instanceof \pocketmine\Player)$this->shootingEntity->sendTitle(('§a成功眩晕§e'.($target instanceof BaseEntity?$target->enConfig['名字']:$target->getName())));
			break;
			}
		}
        if($target->attack($ev->getFinalDamage(), $ev) === true) {
            $ev->useArmors();
            return true;
        }

        return false;
    }

}
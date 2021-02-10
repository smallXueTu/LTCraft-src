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

namespace LTEntity\entity\projectile;

use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use LTEntity\entity\ProjectileEntity;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\MovingObjectPosition;
use LTEntity\entity\BaseEntity;

class AFalseArrow extends ProjectileEntity
{
    const NETWORK_ID = 80;

    public $width = 0.5;
    public $length = 0.5;
    public $height = 0.5;

    protected $gravity = 0.05;
    protected $drag = 0.01;

    public $damage = 2;

    protected $isCritical;
    protected $potionId;
    protected $canExplode = false;
    protected $canRebound = false;

    /**
     * Arrow constructor.
     *
     * @param Level       $level
     * @param CompoundTag $nbt
     * @param Entity|null $shootingEntity
     * @param bool        $critical
     */
    public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, $critical = false)
    {
        $this->isCritical = (bool) $critical;
        if(!isset($nbt->Potion)) {
            $nbt->Potion = new ShortTag("Potion", 0);
        }
        parent::__construct($level, $nbt, $shootingEntity);
        $this->potionId = $this->namedtag["Potion"];
    }

    /**
     * @return bool
     */
    public function isCritical() : bool{
        return $this->isCritical;
    }

    /**
     * @return int
     */
    public function getPotionId() : int{
        return $this->potionId;
    }

    /**
     * @param $currentTick
     *
     * @return bool
     */
    public function onUpdate($currentTick)
    {
        if($this->closed) {
            return false;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;

        $hasUpdate = $this->entityBaseTick($tickDiff);
        if($this->isAlive()) {

            $movingObjectPosition = null;

            $moveVector = new Vector3($this->x + $this->motionX, $this->y + $this->motionY, $this->z + $this->motionZ);

            $list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);

            $nearDistance = PHP_INT_MAX;
            $nearEntity = null;

            foreach($list as $entity) {
                if($entity === $this->shootingEntity) { //是发射的实体就进入下次循环
                    continue;
                }

                $axisalignedbb = $entity->boundingBox->grow(0.3, 0.3, 0.3);//碰撞盒
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
                $this->movingObjectPosition = $movingObjectPosition;
            }

            if($movingObjectPosition !== null) {
                if($movingObjectPosition->entityHit !== null) {
                    $motion = sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2);
                    $damage = ceil($motion * $this->damage);

                    if($this instanceof AFalseArrow and $this->isCritical()) {
                        $damage += mt_rand(0, (int)($damage / 2) + 1);
                    }
                      $results = $this->attackOccurred($movingObjectPosition->entityHit, $damage);

                    /* if($results === true) {
                       if($this instanceof Arrow and $this->getPotionId() != 0) {
                            foreach(Potion::getEffectsById($this->getPotionId() - 1) as $effect) {
                                $movingObjectPosition->entityHit->addEffect($effect->setDuration($effect->getDuration() / 8));
                            }
                        }
                    }*/

                    $this->hadCollision = true;
					/*
                    if($this->fireTicks > 0) {
                        $movingObjectPosition->entityHit->setOnFire($ev->getDuration());
                    }
					*/
                    if($this->shootingEntity instanceof Player or !$this->canRebound) {
                        $this->kill();
                    } else $this->DisappearTime = 8;
                    $hasUpdate=true;
                }
            }

            $this->move($this->motionX, $this->motionY, $this->motionZ);

            if($this->isCollided and !$this->hadCollision) {
                $this->hadCollision = true;

                $this->motionX = 0;
                $this->motionY = 0;
                $this->motionZ = 0;
            }
            elseif(!$this->isCollided and $this->hadCollision) {
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
        if($this->onGround) {
            $this->isCritical = false;//是挑剔
        }
        if($this->age > 40 or $this->isCollided) { //是相撞的。
            $this->kill();
            $hasUpdate = true;
        }
        $this->timings->stopTiming();
        return $hasUpdate;
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player)
    {
        $pk = new AddEntityPacket();
        $pk->type = self::NETWORK_ID;
        $pk->eid = $this->getId();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        /* $pk=new MoveEntityPacket();
        	$pk->eid = $this->getId();
          $pk->x=$this->x+$this->motionX*15;
          $pk->y=$this->y;
          $pk->z=$this->z+$this->motionZ*15;
        	$player->dataPacket($pk);*/
        parent::spawnTo($player);
    }
}
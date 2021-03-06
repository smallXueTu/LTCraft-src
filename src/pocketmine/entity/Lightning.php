<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\entity;

use LTEntity\entity\Guide\Trident;
use pocketmine\block\Liquid;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\Player;
use pocketmine\entity\Entity;

class Lightning extends Animal {
	const NETWORK_ID = 93;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 1.8;
	public $damage = 0;
	public $owner = null;
	public $target = null;

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Lightning";
	}
	
	public function setDamage($damage){
		$this->damage=$damage;
	}

    /**
     * @param \pocketmine\entity\Entity $owner
     */
	public function setOwner(Entity $owner){
		$this->owner=$owner;
	}
	public function getOwner(){
		return $this->owner;
	}
	public function setTarget(Entity $target){
		$this->target=$target;
	}
	public function initEntity(){
		parent::initEntity();
		$this->setMaxHealth(2);
		$this->setHealth(2);
	}

	/**
	 * @param $tick
	 *
	 * @return bool
	 */
	public function onUpdate($tick){
		parent::onUpdate($tick);
		if($this->age > 20){
			$this->kill();
			$this->close();
		}
		return true;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		$pk = new ExplodePacket();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->radius = 10;
		$pk->records = [];
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

	public function spawnToAll(){
		parent::spawnToAll();
        if($this->target!==null){
            $damage = $this->damage!==0?$this->damage : mt_rand(8, 20);
            if ($this->owner instanceof Trident){
                $ev = new EntityDamageByEntityEvent($this, $this->target, EntityDamageByEntityEvent::CAUSE_LIGHTNING, $damage, 0, true);
            }else{
                $ev = new EntityDamageByEntityEvent($this, $this->target, EntityDamageByEntityEvent::CAUSE_LIGHTNING, $damage);
            }
            if($this->target->attack($ev->getFinalDamage(), $ev) === true){
                $ev->useArmors();
            }
            return;
        }else{
            foreach($this->level->getNearbyEntities($this->boundingBox->grow(4, 3, 4), $this) as $entity){
                if($entity instanceof Player){
                    $damage = $this->damage==0?mt_rand(8, 20):$this->damage;
                    $ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_LIGHTNING, $damage, 0);
                    if($entity->attack($ev->getFinalDamage(), $ev) === true){
                        $ev->useArmors();
                    }
                    $entity->setOnFire(mt_rand(3, 8));
                }

                if($entity instanceof Creeper){
                    $entity->setPowered(true, $this);
                }
            }
        }
	}
}
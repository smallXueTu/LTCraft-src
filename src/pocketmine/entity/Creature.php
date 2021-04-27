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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\scheduler\CallbackTask;
use LTGrade\FloatingText;
use LTGrade\EventListener;

abstract class Creature extends Living {
	public $attackingTick = 0;
	public $freezeTime = 0;
	public $vertigoTime = 0;
	public $injuredTime = 0;
	public $SunderArmorTime = 0;
	public $BlindnessTime = 0;
	public $armorV = 0;
	/*
	设置冰冻
	需要 int 时间 秒
	*/
	public function setFreeze(int $time){
		if($this instanceof \pocketmine\Player)
			$this->freezeTime=(int)($time*20)*(100-$this->getBuff()->getControlReduce())/100;
		else
			$this->freezeTime=$time*20;
		if($this instanceof \pocketmine\Player)$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_IMMOBILE, true);
	}
	/*
	设置眩晕
	需要 int 时间 秒
	*/
	public function setVertigo(int $time){
		if($this instanceof \pocketmine\Player)
			$this->vertigoTime=(int)($time*20)*(100-$this->getBuff()->getControlReduce())/100;
		else
			$this->vertigoTime=$time*20;
		if($this instanceof \pocketmine\Player)$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_IMMOBILE, true);
	}
	/*
	设置重伤
	需要 int 时间 秒
	*/
	public function setInjured(int $time){
		if($this instanceof \pocketmine\Player)
			$this->injuredTime=(int)($time*20)*(100-$this->getBuff()->getControlReduce())/100;
		else
			$this->injuredTime=$time*20;
	}
	/*
	设置致盲
	需要 int 时间 秒
	*/
	public function setBlindnessArmor(int $time){
		if($this instanceof \pocketmine\Player)
			$this->BlindnessTime=(int)($time*20)*(100-$this->getBuff()->getControlReduce())/100;
		else
			$this->BlindnessTime=$time*20;
	}
	/*
	设置破甲
	需要 int 时间 秒
	*/
	public function setSunderArmor(int $time){
		if($this instanceof \pocketmine\Player)
			$this->SunderArmorTime=(int)($time*20)*(100-$this->getBuff()->getControlReduce())/100;
		else
			$this->SunderArmorTime=$time*20;
	}
	public function getBlindness(){
		return $this->BlindnessTime>0;
	}
	public function getArmorV(){
		if($this->SunderArmorTime>0){
			return (int)$this->armorV/2;
		}
		return $this->armorV;
	}
	public function setArmorV(int $v){
		$this->armorV=$v;
		if($this instanceof \pocketmine\Player and $this->getAPI()!==null)$this->getAPI()->update(1);
	}
	public function addArmorV(int $v){
		$this->armorV+=$v;
		if($this instanceof \pocketmine\Player and $this->getAPI()!==null)$this->getAPI()->update(1);
	}
	public function delArmorV(int $v){
		$this->armorV-=$v;
		if($this instanceof \pocketmine\Player and $this->getAPI()!==null)$this->getAPI()->update(1);
	}
	
	public function setHealth($amount){
		$HaraHealth=$this->getHealth();
		parent::setHealth($amount);
		if($this->getHealth()>$HaraHealth){
			$h=(int)($this->getHealth()-$HaraHealth);
			$particle=new FloatingText($this,'§a+'.$h,1.1);
		}
	}
	public function onUpdate($tick){
		if(!($this instanceof Human)){
			if($this->attackingTick > 0){
				$this->attackingTick--;
			}
			if(!$this->isAlive() and $this->hasSpawned){
				++$this->deadTicks;
				if($this->deadTicks >= 20){
					$this->despawnFromAll();
				}
				return true;
			}
			if($this->isAlive()){

				$this->motionY -= $this->gravity;

				$this->move($this->motionX, $this->motionY, $this->motionZ);

				$friction = 1 - $this->drag;

				if($this->onGround and (abs($this->motionX) > 0.00001 or abs($this->motionZ) > 0.00001)){
					$friction = $this->getLevel()->getBlock($this->temporalVector->setComponents((int) floor($this->x), (int) floor($this->y - 1), (int) floor($this->z) - 1))->getFrictionFactor() * $friction;
				}

				$this->motionX *= $friction;
				$this->motionY *= 1 - $this->drag;
				$this->motionZ *= $friction;

				if($this->onGround){
					$this->motionY *= -0.5;
				}

//				$this->updateMovement();
			}
		}
		if($this->freezeTime>0)
			--$this->freezeTime;
		if($this->injuredTime>0)
			--$this->injuredTime;
		if($this->BlindnessTime>0)
			--$this->BlindnessTime;
		if($this->SunderArmorTime>0)
			--$this->SunderArmorTime;
		if($this->vertigoTime>0){
			--$this->vertigoTime;
			$this->yaw+=30;
			$this->pitch=0;
			$this->forceUpdateMovement();
		}
		//parent::entityBaseTick();
		return parent::onUpdate($tick);
	}
	 public function move($dx, $dy, $dz) : bool{
		 if($this->freezeTime>0 or $this->vertigoTime>0)return false;
		 return parent::move($dx, $dy, $dz);
	 }
	/**
	 * @param int $distance
	 *
	 * @return bool
	 */
	public function willMove($distance = 36){
		foreach($this->getViewers() as $viewer){
			if($this->distance($viewer->getLocation()) <= $distance) return true;
		}
		return false;
	}

	/**
	 * @param float             $damage
	 * @param EntityDamageEvent $source
	 *
	 * @return bool|void
	 */
	public function attack($damage, EntityDamageEvent $source){
		parent::attack($damage, $source);
		if(!$source->isCancelled() and $source->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK){
			$this->attackingTick = 20;
		}
	}
}
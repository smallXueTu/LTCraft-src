<?php

/**
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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\entity;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Creature;
use pocketmine\event\Cancellable;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\Player;
use LTGrade\FloatingText;


class EntityDamageEvent extends EntityEvent implements Cancellable {
	public static $handlerList = null;

	const MODIFIER_BASE = 0;//基础伤害
	const MODIFIER_GAIN = 1;//增益
	const MODIFIER_OFFSET = 2;//抵消
	const MODIFIER_ARMOUR = 3;//穿甲 Armour
	const MODIFIER_REAL_DAMAGE = 4;//真伤 RealDamage

	const CAUSE_CONTACT = 0;//?
	const CAUSE_ENTITY_ATTACK = 1;//实体攻击
	const CAUSE_PROJECTILE = 2;//抛射物
	const CAUSE_SUFFOCATION = 3;//窒息
	const CAUSE_FALL = 4;//摔落
	const CAUSE_FIRE = 5;//火
	const CAUSE_FIRE_TICK = 6;//火
	const CAUSE_LAVA = 7;//岩浆
	const CAUSE_DROWNING = 8;//溺水
	const CAUSE_BLOCK_EXPLOSION = 9;
	const CAUSE_ENTITY_EXPLOSION = 10;
	const CAUSE_VOID = 11;
	const CAUSE_SUICIDE = 12;
	const CAUSE_MAGIC = 13;
	const CAUSE_CUSTOM = 14;
	const CAUSE_STARVATION = 15;

	const CAUSE_LIGHTNING = 16;
	const CAUSE_THORNS = 17;
	const CAUSE_SECONDS_KILL = 18;
	const CAUSE_EAT = 19;
	const CAUSE_PUNISHMENT = 20;
	const CAUSE_DIDI = 21;
	const CAUSE_HT = 22;
	const CAUSE_SAKURA = 23;

	private $cause;
	private $EPF = 0;
	private $fireProtectL = 0;
	/** @var array */
	private $modifiers;
	private $rateModifiers = [];
	private $originals;
	private $usedArmors = [];
	private $thornsLevel = [];
	private $thornsArmor;
	private $thornsDamage = 0;
	public $zs=false;
	public $notC=false;

	/**
	 * @param Entity    $entity
	 * @param int       $cause
	 * @param int|int[] $damage
     * @param bool $zs
	 */
	public function __construct(Entity $entity, $cause, $damage, $zs=false){
		$this->entity = $entity;
		$this->cause = $cause;
		$this->zs = $zs;
		if(is_array($damage)){
			$this->modifiers = $damage;
			$this->modifiers[self::MODIFIER_REAL_DAMAGE] = 0;
		}else{
			$this->modifiers = [
				self::MODIFIER_BASE => $damage,
				self::MODIFIER_REAL_DAMAGE => 0
			];
		}
		$this->originals = $this->modifiers;
		$this->rateModifiers = [1=>0, 2=>0, 3=>0];
		if($this->zs===true)return;
		
		if(!isset($this->modifiers[self::MODIFIER_BASE])){
			throw new \InvalidArgumentException("BASE Damage modifier missing");
		}
		//For DAMAGE_RESISTANCE
		if($cause !== self::CAUSE_VOID and $cause !== self::CAUSE_SUICIDE and $cause !== self::CAUSE_MAGIC){
			if($entity->hasEffect(Effect::DAMAGE_RESISTANCE)){
				$RES_level = 0.20 * $entity->getEffect(Effect::DAMAGE_RESISTANCE)->getAmplifier();
				if($RES_level > 1){
					$RES_level = 1;
				}
				$this->setRateDamage($RES_level, self::MODIFIER_OFFSET);//应用
			}
		}

		//TODO: add zombie
		/*
		if($entity instanceof Player and $entity->getInventory() instanceof PlayerInventory){
			switch($cause){
				case self::CAUSE_CONTACT:
				case self::CAUSE_ENTITY_ATTACK:
				case self::CAUSE_PROJECTILE:
				case self::CAUSE_FIRE:
				case self::CAUSE_LAVA:
				case self::CAUSE_BLOCK_EXPLOSION:
				case self::CAUSE_ENTITY_EXPLOSION:
				case self::CAUSE_LIGHTNING:
					$points = 0;
					foreach($entity->getInventory()->getArmorContents() as $index => $i){
						if($i->isArmor()){
							$points += $i->getArmorValue();
							$this->usedArmors[$index] = 1;
						}
					}
					if($points !== 0){
						$this->setRateDamage(1 - 0.04 * $points, self::MODIFIER_ARMOR);
					}
					//For Protection
					$spe_Prote = null;
					switch($cause){
						case self::CAUSE_ENTITY_EXPLOSION:
						case self::CAUSE_BLOCK_EXPLOSION:
							$spe_Prote = Enchantment::TYPE_ARMOR_EXPLOSION_PROTECTION;
							break;
						case self::CAUSE_FIRE:
						case self::CAUSE_LAVA:
							$spe_Prote = Enchantment::TYPE_ARMOR_FIRE_PROTECTION;
							break;
						case self::CAUSE_PROJECTILE:
							$spe_Prote = Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION;
							break;
						default;
							break;
					}
					foreach($this->usedArmors as $index => $cost){
						$i = $entity->getInventory()->getArmorItem($index);
						if($i->isArmor()){
							$this->EPF += $i->getEnchantmentLevel(Enchantment::TYPE_ARMOR_PROTECTION);
							$this->fireProtectL = max($this->fireProtectL, $i->getEnchantmentLevel(Enchantment::TYPE_ARMOR_FIRE_PROTECTION));
							if($i->getEnchantmentLevel(Enchantment::TYPE_ARMOR_THORNS) > 0){
								$this->thornsLevel[$index] = $i->getEnchantmentLevel(Enchantment::TYPE_ARMOR_THORNS);
							}
							if($spe_Prote !== null){
								$this->EPF += 2 * $i->getEnchantmentLevel($spe_Prote);
							}
						}
					}
					break;
				case self::CAUSE_FALL:
					//Feather Falling
					$i = $entity->getInventory()->getBoots();
					if($i->isArmor()){
						$this->EPF += $i->getEnchantmentLevel(Enchantment::TYPE_ARMOR_PROTECTION);
						$this->EPF += 3 * $i->getEnchantmentLevel(Enchantment::TYPE_ARMOR_FALL_PROTECTION);
					}
					break;
				case self::CAUSE_FIRE_TICK:
				case self::CAUSE_SUFFOCATION:
				case self::CAUSE_DROWNING:
				case self::CAUSE_VOID:
				case self::CAUSE_SUICIDE:
				case self::CAUSE_MAGIC:
				case self::CAUSE_CUSTOM:
				case self::CAUSE_STARVATION:
					break;
				default:
					break;
			}
			if($this->EPF !== 0){
				$this->EPF = min(20, ceil($this->EPF * mt_rand(50, 100) / 100));
				$this->setRateDamage(0.04 * $this->EPF, self::MODIFIER_OFFSET);
			}
		}
		*/
	}
    public function setCancelled($value = true)
    {
        if($this->notC)return;
        parent::setCancelled($value);
    }

    /**
	 * @return int
	 */
	public function getCause(){
		return $this->cause;
	}

	/**
	 * @param int $type
	 *
	 * @return int
	 */
	public function getOriginalDamage($type = self::MODIFIER_BASE){
		if(isset($this->originals[$type])){
			return $this->originals[$type];
		}
		return 0;
	}

	/**
	 * @param int $type
	 *
	 * @return int
	 */
	public function getDamage($type = self::MODIFIER_BASE){
		if(isset($this->modifiers[$type])){
			return $this->modifiers[$type];
		}

		return 0;
	}

	/**
	 * @param float $damage
	 * @param int   $type
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setDamage($damage, $type = self::MODIFIER_BASE){
		$this->modifiers[$type] = $damage;
	}

	/**
	 * @param int $type
	 *
	 * @return float 1 - the percentage
	 */
	public function getRateDamage($type = self::MODIFIER_BASE){
		if(isset($this->rateModifiers[$type])){
			return $this->rateModifiers[$type];
		}
		return 1;
	}

	/**
	 * @param float $damage
	 * @param int   $type
	 *
	 * Notice:If you want to add/reduce the damage without reducing by Armor or effect. set a new Damage using setDamage
	 * Notice:If you want to add/reduce the damage within reducing by Armor of effect. Plz change the MODIFIER_BASE
	 * Notice:If you want to add/reduce the damage by multiplying. Plz use this function.
	 */
	public function setRateDamage($damage, $type = self::MODIFIER_BASE){
		$this->rateModifiers[$type] += $damage;
	}

	/**
	 * @param int $type
	 *
	 * @return bool
	 */
	public function isApplicable($type){
		return isset($this->modifiers[$type]);
	}

	/**
	 * @return int
	 */
	public function getFinalDamage(){
		if($this->zs or $this->cause === self::CAUSE_MAGIC)return $this->modifiers[self::MODIFIER_BASE];
		$damage = $this->modifiers[self::MODIFIER_BASE];
		if(($v=$this->rateModifiers[self::MODIFIER_OFFSET])!==0){
			if($v>1)$v=1;
			if($v<0)$v=0;
			if($v>0.95 and ($this->entity->getEffect(Effect::DAMAGE_RESISTANCE)===null or $this->entity->getEffect(Effect::DAMAGE_RESISTANCE)->getAmplifier()<5))
				$v=0.95;
			if($this->rateModifiers[self::MODIFIER_ARMOUR]>0){
				if($this->rateModifiers[self::MODIFIER_ARMOUR]>100)$this->rateModifiers[self::MODIFIER_ARMOUR] = 100;
				$v*=1-$this->rateModifiers[self::MODIFIER_ARMOUR]/100;
			}
			$damage *= 1-$v;
		}
		if(($v=$this->rateModifiers[self::MODIFIER_GAIN])!==0){
			$damage *= $v+1;
		}
		foreach($this->modifiers as $type => $d){
			if($type !== self::MODIFIER_BASE){
				$damage += $d;
			}
		}
		return $damage;
	}

	/**
	 * @return Item $usedArmors
	 * notice: $usedArmors $index->$cost
	 * $index: the $index of ArmorInventory
	 * $cost:  the num of durability cost
	 */
	public function getUsedArmors(){
		return $this->usedArmors;
	}

	/**
	 * @return Int $fireProtectL
	 */
	public function getFireProtectL(){
		return $this->fireProtectL;
	}

	/**
	 * @return bool
	 */
	public function useArmors(){
		if($this->entity instanceof Player){
			if($this->entity->isSurvival() and $this->entity->isAlive()){
				foreach($this->usedArmors as $index => $cost){
					$i = $this->entity->getInventory()->getArmorItem($index);
					if($i->isArmor()){
						$this->entity->getInventory()->damageArmor($index, $cost);
					}
				}
			}
			return true;
		}
		return false;
	}

	public function createThornsDamage(){
		if($this->thornsLevel !== []){
			$this->thornsArmor = array_rand($this->thornsLevel);
			$thornsL = $this->thornsLevel[$this->thornsArmor];
			if(mt_rand(1, 100) < $thornsL * 15){
				//$this->thornsDamage = mt_rand(1, 4); 
				$this->thornsDamage = 0; //Delete When #321 Is Fixed And Add In The Normal Damage
			}
		}
	}

	/**
	 * @return int
	 */
	public function getThornsDamage(){
		return $this->thornsDamage;
	}
	public function sendFloatingDamage(){
		if(!($this->getEntity() instanceof Creature))return;
		if($this instanceof EntityDamageByEntityEvent){
			if($this->zs){
				if((int)$this->getFinalDamage()>0) {
					return new FloatingText($this->getEntity(),'-'.round($this->getFinalDamage()),0.3);
				}
			}else{
				$damage=$this->getFinalDamage()-$this->modifiers[self::MODIFIER_REAL_DAMAGE];
				if((int)$damage>0) {
					return new FloatingText($this->getEntity(),'§c-'.round($damage),0.6);
				}
				if((int)$this->modifiers[self::MODIFIER_REAL_DAMAGE]>0) {
					return new FloatingText($this->getEntity(),'-'.round($this->modifiers[self::MODIFIER_REAL_DAMAGE]),0.3);
				}
			}
		}
		switch($this->getCause()) {
			case EntityDamageEvent::CAUSE_PROJECTILE://射蛋
			case EntityDamageEvent::CAUSE_SUFFOCATION://卡墙
			case EntityDamageEvent::CAUSE_FALL://坠落
			case EntityDamageEvent::CAUSE_FIRE://火燃
			case EntityDamageEvent::CAUSE_FIRE_TICK://火
			case EntityDamageEvent::CAUSE_LAVA://岩浆
			case EntityDamageEvent::CAUSE_STARVATION://岩浆
			case EntityDamageEvent::CAUSE_DROWNING://溺水
			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION://方块爆炸
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION://实体爆炸
			case EntityDamageEvent::CAUSE_LIGHTNING://闪电
			case EntityDamageEvent::CAUSE_THORNS://反伤
				if((int)$this->getFinalDamage()>0) {
					return new FloatingText($this->getEntity(),'§e-'.round($this->getFinalDamage()),0.1);
				}
			break;
			case EntityDamageEvent::CAUSE_MAGIC:
			case EntityDamageEvent::CAUSE_SECONDS_KILL:
			case EntityDamageEvent::CAUSE_EAT://吃
				if((int)$this->getFinalDamage()>0) {
					$particle=new FloatingText($this->getEntity(),'-'.round($this->getFinalDamage()),0.3);
				}
			break;
		}
	}
	/**
	 * @return bool should be used after getThornsDamage()
	 */
	public function setThornsArmorUse(){
		if($this->thornsArmor === null){
			return false;
		}else{
			$this->usedArmors[$this->thornsArmor] = 3;
			return true;
		}
	}
}

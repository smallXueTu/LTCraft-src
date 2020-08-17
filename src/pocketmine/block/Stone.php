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

namespace pocketmine\block;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Tool;

class Stone extends Solid {
	const NORMAL = 0;
	const GRANITE = 1;
	const POLISHED_GRANITE = 2;
	const DIORITE = 3;
	const POLISHED_DIORITE = 4;
	const ANDESITE = 5;
	const POLISHED_ANDESITE = 6;

	protected $id = self::STONE;

	/**
	 * Stone constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;

	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 1.5;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		static $names = [
			self::NORMAL => '石头',
			self::GRANITE => '花岗岩',
			self::POLISHED_GRANITE => '抛光花岗岩',
			self::DIORITE => '闪长岩',
			self::POLISHED_DIORITE => '抛光闪长岩',
			self::ANDESITE => '安山岩',
			self::POLISHED_ANDESITE => '抛光安山岩',
			7 => '未知石头',
		];
		return $names[$this->meta & 0x07];
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= Tool::TIER_WOODEN){
			if($item->getEnchantmentLevel(Enchantment::TYPE_MINING_SILK_TOUCH) > 0 and $this->getDamage() === 0){
				return [
					[Item::STONE, 0, 1],
				];
			}
			return [
				[$this->getDamage() === 0 ? Item::COBBLESTONE : Item::STONE, $this->getDamage(), 1],
			];
		}else{
			return [];
		}
	}

}
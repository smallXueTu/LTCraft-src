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

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Flower extends Flowable {
	const TYPE_POPPY = 0;
	const TYPE_BLUE_ORCHID = 1;
	const TYPE_ALLIUM = 2;
	const TYPE_AZURE_BLUET = 3;
	const TYPE_RED_TULIP = 4;
	const TYPE_ORANGE_TULIP = 5;
	const TYPE_WHITE_TULIP = 6;
	const TYPE_PINK_TULIP = 7;
	const TYPE_OXEYE_DAISY = 8;

	protected $id = self::RED_FLOWER;

	/**
	 * Flower constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		static $names = [
			self::TYPE_POPPY => '罂粟',
			self::TYPE_BLUE_ORCHID => '蓝色的兰花',
			self::TYPE_ALLIUM => '葱属植物',
			self::TYPE_AZURE_BLUET => '茜草花',
			self::TYPE_RED_TULIP => '红色郁金香',
			self::TYPE_ORANGE_TULIP => '橙色郁金香',
			self::TYPE_WHITE_TULIP => '白郁金香',
			self::TYPE_PINK_TULIP => '粉色郁金香',
			self::TYPE_OXEYE_DAISY => '牛雏菊',
			9 => '未知',
			10 => '未知',
			11 => '未知',
			12 => '未知',
			13 => '未知',
			14 => '未知',
			15 => '未知'
		];
		return $names[$this->meta];
	}


	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param int         $face
	 * @param float       $fx
	 * @param float       $fy
	 * @param float       $fz
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$down = $this->getSide(0);
		if($down->getId() === Block::GRASS or $down->getId() === Block::DIRT or $down->getId() === Block::FARMLAND){
			$this->getLevel()->setBlock($block, $this, true);

			return true;
		}

		return false;
	}

	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
				$this->getLevel()->useBreakOn($this);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}
}
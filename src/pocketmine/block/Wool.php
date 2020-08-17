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


use pocketmine\item\Tool;

class Wool extends Solid {
	const WHITE = 0;
	const ORANGE = 1;
	const MAGENTA = 2;
	const LIGHT_BLUE = 3;
	const YELLOW = 4;
	const LIME = 5;
	const PINK = 6;
	const GRAY = 7;
	const LIGHT_GRAY = 8;
	const CYAN = 9;
	const PURPLE = 10;
	const BLUE = 11;
	const BROWN = 12;
	const GREEN = 13;
	const RED = 14;
	const BLACK = 15;

	protected $id = self::WOOL;

	/**
	 * Wool constructor.
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
		return 0.8;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_SHEARS;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		static $names = [
			0 => '白色羊毛',
			1 => '橙色羊毛',
			2 => '洋红羊毛',
			3 => '浅蓝色羊毛',
			4 => '黄色羊毛',
			5 => '灰色羊毛',
			6 => '粉色羊毛',
			7 => '灰色羊毛',
			8 => '浅灰色羊毛',
			9 => '青色羊毛',
			10 => '紫色羊毛',
			11 => '蓝色羊毛',
			12 => '棕色羊毛',
			13 => '绿色羊毛',
			14 => '红色羊毛',
			15 => '黑色羊毛',
		];
		return $names[$this->meta & 0x0f];
	}

	/**
	 * @return int
	 */
	public function getBurnChance() : int{
		return 30;
	}

	/**
	 * @return int
	 */
	public function getBurnAbility() : int{
		return 60;
	}

}
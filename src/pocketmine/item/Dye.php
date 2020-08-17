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

namespace pocketmine\item;

use pocketmine\block\Block;

class Dye extends Item {
	const BLACK = 0;
	const RED = 1;
	const GREEN = 2;
	const BROWN = 3;
	const COCOA_BEANS = 3;
	const BLUE = 4;
	const LAPIS_LAZULI = 4;
	const PURPLE = 5;
	const CYAN = 6;
	const SILVER = 7;
	const LIGHT_GRAY = 7;
	const GRAY = 8;
	const PINK = 9;
	const LIME = 10;
	const YELLOW = 11;
	const LIGHT_BLUE = 12;
	const MAGENTA = 13;
	const ORANGE = 14;
	const WHITE = 15;
	const BONE_MEAL = 15;

	/**
	 * Dye constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
	public function __construct($meta = 0, $count = 1){
		if($meta === 3){
			$this->block = Block::get(Item::COCOA_BLOCK);
			parent::__construct(self::DYE, 3, $count, "可可豆");
		}else{
			parent::__construct(self::DYE, $meta, $count, $this->getNameByMeta($meta));
		}
	}

	/**
	 * @param int $meta
	 *
	 * @return string
	 */
	public function getNameByMeta(int $meta) : string{
		switch($meta){
			case self::BLACK:
				return "墨水囊";
			case self::RED:
				return "玫瑰红";
			case self::GREEN:
				return "仙人掌綠";
			case self::BROWN:
				return "可可豆";
			case self::BLUE:
				return "青金石";
			case self::PURPLE:
				return "紫色染料";
			case self::CYAN:
				return "青色染料";
			case self::SILVER:
				return "浅灰色染料";
			case self::GRAY:
				return "灰色染料";
			case self::PINK:
				return "粉色染料";
			case self::LIME:
				return "石灰染料";
			case self::YELLOW:
				return "蒲公英黄";
			case self::LIGHT_BLUE:
				return "浅蓝染料";
			case self::MAGENTA:
				return "品红色染料";
			case self::ORANGE:
				return "橙色染料";
			case self::WHITE:
				return "骨粉";
			default:
				return "染料";
		}
	}
}

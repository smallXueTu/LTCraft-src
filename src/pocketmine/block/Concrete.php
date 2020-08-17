<?php

/*
 *
 *  _____            _               _____           
 * / ____|          (_)             |  __ \          
 *| |  __  ___ _ __  _ ___ _   _ ___| |__) | __ ___  
 *| | |_ |/ _ \ '_ \| / __| | | / __|  ___/ '__/ _ \ 
 *| |__| |  __/ | | | \__ \ |_| \__ \ |   | | | (_) |
 * \_____|\___|_| |_|_|___/\__, |___/_|   |_|  \___/ 
 *                         __/ |                    
 *                        |___/                     
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author GenisysPro
 * @link https://github.com/GenisysPro/GenisysPro
 *
 *
*/

namespace pocketmine\block;

use pocketmine\item\Tool;

class Concrete extends Solid {

	protected $id = self::CONCRETE;

	/**
	 * Concrete constructor.
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
		return 1.8;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	/**
	 * @return mixed
	 */
	public function getName(){
		static $names = [
			0 => '白色混凝土',
			1 => '柑桔混凝土',
			2 => '洋红混凝土',
			3 => '浅蓝色混凝土',
			4 => '黄色混凝土',
			5 => '石灰混凝土',
			6 => '粉红混凝土',
			7 => '灰色混凝土',
			8 => '银混凝土',
			9 => '青铜混凝土',
			10 => '紫色混凝土',
			11 => '蓝色混凝土',
			12 => '棕色混凝土',
			13 => '绿色混凝土',
			14 => '红色混凝土',
			15 => '黑色混凝土',
		];
		return $names[$this->meta & 0x0f];
	}

}
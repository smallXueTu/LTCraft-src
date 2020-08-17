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

class ConcretePowder extends Fallable {

	protected $id = self::CONCRETE_POWDER;

	/**
	 * ConcretePowder constructor.
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
		return 0.5;
	}

	/**
	 * @return float
	 */
	public function getResistance(){
		return 2.5;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_SHOVEL;
	}

	/**
	 * @return mixed
	 */
	public function getName(){
		static $names = [
			0 => '白色混凝土粉末',
			1 => '橙色混凝土粉末',
			2 => '洋红水泥粉',
			3 => '浅蓝色混凝土粉',
			4 => '黄色混凝土粉末',
			5 => '石灰混凝土粉',
			6 => '粉红混凝土粉',
			7 => '灰色混凝土粉末',
			8 => '银粉混凝土',
			9 => '青色混凝土粉',
			10 => '紫色混凝土粉末',
			11 => '蓝色混凝土粉末',
			12 => '棕色混凝土粉末',
			13 => '绿色混凝土粉末',
			14 => '红色混凝土粉末',
			15 => '黑色混凝土粉末',
		];
		return $names[$this->meta & 0x0f];
	}

}

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

namespace pocketmine\level\particle;

use pocketmine\math\Vector3;
use pocketmine\network\protocol\DataPacket;

abstract class Particle extends Vector3 {

	const TYPE_BUBBLE = 1;//水泡
	const TYPE_CRITICAL = 2;//灰星星
	const TYPE_BLOCK_FORCE_FIELD = 3;//空
	const TYPE_SMOKE = 4;//闪退
	const TYPE_EXPLODE = 5;// 爆炸？
	const TYPE_EVAPORATION = 6;//水蒸气
	const TYPE_FLAME = 7;//火焰
	const TYPE_LAVA = 8;//火花
	const TYPE_LARGE_SMOKE = 9;//烟
	const TYPE_REDSTONE = 10;//红石的粒子
	const TYPE_RISING_RED_DUST = 11;//红星星
	const TYPE_ITEM_BREAK = 12;//闪退
	const TYPE_SNOWBALL_POOF = 13;//雪球粒子
	const TYPE_HUGE_EXPLODE = 14;//爆炸
	const TYPE_HUGE_EXPLODE_SEED = 15;//大爆炸
	const TYPE_MOB_FLAME = 16;//大火
	const TYPE_HEART = 17;//爱心
	const TYPE_TERRAIN = 18;//闪退
	const TYPE_SUSPENDED_TOWN = 19, TYPE_TOWN_AURA = 19;//小 看不到
	const TYPE_PORTAL = 20;//末影人粒子
	const TYPE_SPLASH = 21, TYPE_WATER_SPLASH = 21;//水粒子
	const TYPE_WATER_WAKE = 22;//水粒子
	const TYPE_DRIP_WATER = 23;//掉落水粒子
	const TYPE_DRIP_LAVA = 24;//掉落岩浆
	const TYPE_FALLING_DUST = 25, TYPE_DUST = 25;//黑粒子
	const TYPE_MOB_SPELL = 26;//看不到
	const TYPE_MOB_SPELL_AMBIENT = 27;//看不到
	const TYPE_MOB_SPELL_INSTANTANEOUS = 28;//看不到
	const TYPE_INK = 29;//黑粒子
	const TYPE_SLIME = 30;//粘液球
	const TYPE_RAIN_SPLASH = 31;//刷怪笼的火
	const TYPE_VILLAGER_ANGRY = 32;//村民生气
	const TYPE_VILLAGER_HAPPY = 33;//村民高兴
	const TYPE_ENCHANTMENT_TABLE = 34;//附魔粒子
	const TYPE_TRACKING_EMITTER = 35;//看不到
	const TYPE_NOTE = 36;//音符
	const TYPE_WITCH_SPELL = 37;//看不到
	const TYPE_CARROT = 38;//食物残渣
	//39 unknown
	const TYPE_END_ROD = 40;//白色 潜影怪的球
	const TYPE_DRAGONS_BREATH = 41;//潜影
    //42 白色
    //43 黄绿
    //44 闪退

	/**
	 * @return DataPacket|DataPacket[]
	 */
	abstract public function encode();

}

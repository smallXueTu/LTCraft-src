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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>

class LevelEventPacket extends DataPacket {

	const NETWORK_ID = Info::LEVEL_EVENT_PACKET;

	const EVENT_SOUND_CLICK = 1000;//点击
	const EVENT_SOUND_CLICK_FAIL = 1001;//点击失败
	const EVENT_SOUND_SHOOT = 1002;//发送声音
	const EVENT_SOUND_DOOR = 1003;//门的声音
	const EVENT_SOUND_FIZZ = 1004;//水和岩浆碰撞
	const EVENT_SOUND_IGNITE = 1005;//点燃

	const EVENT_SOUND_GHAST = 1007;//可怕
	const EVENT_SOUND_GHAST_SHOOT = 1008;//可怕
	const EVENT_SOUND_BLAZE_SHOOT = 1009;//大火
	const EVENT_SOUND_DOOR_BUMP = 1010;//门

	const EVENT_SOUND_DOOR_CRASH = 1012;//门崩溃
	const EVENT_SOUND_ENDERMAN_TELEPORT = 1018;//末影人传送

	const EVENT_SOUND_ANVIL_BREAK = 1020; //铁砧坏了 This sound is played on the anvil's final use, NOT when the block is broken.
	const EVENT_SOUND_ANVIL_USE = 1021;//铁砧使用
	const EVENT_SOUND_ANVIL_FALL = 1022;//铁砧落地

	const EVENT_SOUND_POP = 1030;//POP?

	const EVENT_SOUND_PORTAL = 1032;//传送门
	const EVENT_SOUND_ITEMFRAME_ADD_ITEM = 1040;//物品展示框增加物品
	const EVENT_SOUND_ITEMFRAME_REMOVE = 1041;//移除
	const EVENT_SOUND_ITEMFRAME_PLACE = 1042;//点击
	const EVENT_SOUND_ITEMFRAME_REMOVE_ITEM = 1043;//移除
	const EVENT_SOUND_ITEMFRAME_ROTATE_ITEM = 1044;//旋转
	const EVENT_SOUND_CAMERA = 1050;//相机
	const EVENT_SOUND_ORB = 1051;//球？
	const EVENT_PARTICLE_SHOOT = 2000;//粒子抢？
	const EVENT_PARTICLE_DESTROY = 2001;//破坏粒子
	const EVENT_PARTICLE_SPLASH = 2002; //带有粒子的喷溅药水声This is actually the splash potion sound with particles
	const EVENT_PARTICLE_EYE_DESPAWN = 2003;//粒子眼睛绝望？
	const EVENT_PARTICLE_SPAWN = 2004;//粒子产生
	const EVENT_GUARDIAN_CURSE = 2006;//守护者诅咒
	const EVENT_PARTICLE_BLOCK_FORCE_FIELD = 2008;//粒子块力场

	const EVENT_PARTICLE_PUNCH_BLOCK = 2014;//颗粒冲压块

	const EVENT_START_RAIN = 3001;//开始下雨
	const EVENT_START_THUNDER = 3002;//开始晴天
	const EVENT_STOP_RAIN = 3003;//停止下雨
	const EVENT_STOP_THUNDER = 3004;//晴天

	const EVENT_REDSTONE_TRIGGER = 3500;//红石触发器
	const EVENT_CAULDRON_EXPLODE = 3501;//爆炸
	const EVENT_CAULDRON_DYE_ARMOR = 3502;//大锅-染料-盔甲
	const EVENT_CAULDRON_CLEAN_ARMOR = 3503;//删除颜色
	const EVENT_CAULDRON_FILL_POTION = 3504;//填充药水瓶
	const EVENT_CAULDRON_TAKE_POTION = 3505;//接受药水瓶
	const EVENT_CAULDRON_FILL_WATER = 3506;//填充水
	const EVENT_CAULDRON_TAKE_WATER = 3507;//接受水
	const EVENT_CAULDRON_ADD_DYE = 3508;//添加染料

	const EVENT_SET_DATA = 4000;//设置数据

	const EVENT_PLAYERS_SLEEPING = 9800;//玩家睡觉

	const EVENT_ADD_PARTICLE_MASK = 0x4000;

	public $evid;
	public $x = 0; //Weather effects don't have coordinates
	public $y = 0;
	public $z = 0;
	public $data;

	/**
	 *
	 */
	public function decode(){

	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putVarInt($this->evid);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVarInt($this->data);
	}

}

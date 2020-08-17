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

use pocketmine\inventory\EnchantInventory;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\enchantment\Enchantment;

class EnchantingTable extends Transparent {

	protected $id = self::ENCHANTING_TABLE;
	/**
	 * EnchantingTable constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return int
	 */
	public function getLightLevel(){
		return 12;
	}

	/**
	 * @return AxisAlignedBB
	 */
	public function getBoundingBox(){
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 0.75,
			$this->z + 1
		);
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
		$this->getLevel()->setBlock($block, $this, true, true);
		$nbt = new CompoundTag("", [
			new StringTag("id", Tile::ENCHANT_TABLE),
			new IntTag("x", $this->x),
			new IntTag("y", $this->y),
			new IntTag("z", $this->z)
		]);

		if($item->hasCustomName()){
			$nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
		}

		if($item->hasCustomBlockData()){
			foreach($item->getCustomBlockData() as $key => $v){
				$nbt->{$key} = $v;
			}
		}

		Tile::createTile(Tile::ENCHANT_TABLE, $this->getLevel(), $nbt);

		return true;
	}

	/**
	 * @return bool
	 */
	public function canBeActivated() : bool{
		return true;
	}

	/**
	 * @return int
	 */
	public function getHardness(){
		return 5;
	}

	/**
	 * @return int
	 */
	public function getResistance(){
		return 6000;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return '附魔台';
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		if(!isset($player->lastClicken) or $player->lastClicken!==$this){
			if(!$item->isTool() and !$item->isArmor() and !($item instanceof \pocketmine\item\FishingRod))return true;
			$player->sendMessage('§l§a[LTCraft温馨提示]§e再次点击即可随机附魔手持,价格10000');
			$player->lastClicken=$this;
			return true;
		}
		unset($player->lastClicken);
			if(!$item->isTool() and !$item->isArmor() and !($item instanceof \pocketmine\item\FishingRod))return $player->sendMessage('§l§a[LTCraft温馨提示]§c手持不支持附魔!');
			if(EconomyAPI::getInstance()->myMoney($player)<=10000)return $player->sendMessage('§l§a[LTcraft温馨提示]§c你没有足够的钱来附魔!');
			restart:
			switch(true){
				case $item->isPickaxe()://稿子
					switch(mt_rand(0,10)){
						case 0:
						case 1:
							$id=15;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://精准采集
						case 3:
						case 4:
							if($item->getEnchantment(18)!==null)goto restart;//精准采集和时运冲突
							$id=16;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 5://耐久
						case 6:
						case 7:
						case 8:
							$id=17;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 9:
						case 10://时运
							if($item->getEnchantment(16)!==null)goto restart;//精准采集和时运冲突
							$id=18;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isAxe()://斧子
					switch(mt_rand(0,7)){
						case 0://效率
						case 1:
							$id=15;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2:
						case 4:
						case 3://精准采集
							$id=16;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://耐久
						case 5:
						case 6:
						case 7:
							$id=17;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isSword()://剑
					switch(mt_rand(0, 6)){
						case 0://锋利
							$id=9;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://亡灵杀手
							$id=10;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://截肢杀手
							$id=11;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 3://击退
							$id=12;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 4://火焰附加
							$id=13;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 5://抢夺
							$id=14;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 6://耐久
							$id=17;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item instanceof \pocketmine\item\FishingRod://鱼钩
				 
				break;
				case $item instanceof \pocketmine\item\Shears://剪刀
					switch(mt_rand(0,1)){
						case 0://耐久
							$id=17;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://效率
							$id=15;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item instanceof \pocketmine\item\Bow://弓
					switch(mt_rand(0,6)){
						case 0://力量
						case 5://力量
							$id=19;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://冲击
							$id=20;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://火矢
						case 3://火矢
						case 4://火矢
							$id=21;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 6://无限
							$id=22;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isShovel()://铲子
					switch(mt_rand(0,2)){
						case 0://效率
							$id=15;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://精准采集
							$id=16;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://耐久
							$id=17;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isHoe()://锄头
					switch(0){
						case 0://耐久
							$id=17;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isHelmet()://头
					switch(mt_rand(0,6)){
						case 0://保护
							$id=0;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://火焰保护
							$id=1;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://摔落保护
							$id=2;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 3://爆炸
							$id=3;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 4://射弹物保护
							$id=4;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 5://水下呼吸
							$id=6;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 6://水下速掘
							$id=8;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isChestplate()://胸甲
					switch(mt_rand(0,4)){
						case 0://保护
							$id=0;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://火焰保护
							$id=1;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://摔落保护
							$id=2;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 3://爆炸
							$id=3;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 4://射弹物保护
							$id=4;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isLeggings()://裤子
					switch(mt_rand(0,4)){
						case 0://保护
							$id=0;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://火焰保护
							$id=1;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://摔落保护
							$id=2;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 3://爆炸
							$id=3;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 4://射弹物保护
							$id=4;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
				case $item->isBoots()://鞋
					switch(mt_rand(0,5)){
						case 0://保护
							$id=0;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 1://火焰保护
							$id=1;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 2://摔落保护
							$id=2;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 3://爆炸
							$id=3;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 4://射弹物保护
							$id=4;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
						case 5://深海探索者
							$id=7;
							$level=mt_rand(1,Enchantment::getEnchantMaxLevel($id));
						break;
					}
				break;
			}
		$enchantment=Enchantment::getEnchantment($id);
		$enchantment->setLevel($level);
		$item->addEnchantment($enchantment);
		$player->getInventory()->setItemInHand($item);
		EconomyAPI::getInstance()->reduceMoney($player, 10000, '附魔');
		$player->sendMessage('§l§a附魔完成');
        $player->newProgress('附魔师');
		return true;
		/*
		if(!$this->getLevel()->getServer()->enchantingTableEnabled){
			return true;
		}
		if($player instanceof Player){
			if($player->isCreative() and $player->getServer()->limitedCreative){
				return true;
			}
			$enchantTable = null;
				$this->getLevel()->setBlock($this, $this, true, true);
				$nbt = new CompoundTag("", [
					new StringTag("id", Tile::ENCHANT_TABLE),
					new IntTag("x", $this->x),
					new IntTag("y", $this->y),
					new IntTag("z", $this->z)
				]);

				if($item->hasCustomName()){
					$nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
				}

				if($item->hasCustomBlockData()){
					foreach($item->getCustomBlockData() as $key => $v){
						$nbt->{$key} = $v;
					}
				}

				Tile::createTile(Tile::ENCHANT_TABLE, $this->getLevel(), $nbt);
			}
			$player->addWindow(new EnchantInventory($this));
			$player->craftingType = Player::CRAFTING_ENCHANT;

		return true;
*/
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= 1){
			return [
				[$this->id, 0, 1],
			];
		}else{
			return [];
		}
	}
}
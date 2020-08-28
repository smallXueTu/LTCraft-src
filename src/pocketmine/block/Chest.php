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

use LTItem\Mana\Mana;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\utils\Utils;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\byteTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Chest as TileChest;
use pocketmine\tile\Tile;
use pocketmine\level\Explosion;

use LTItem\SpecialItems\Material;
use LTItem\Main as LTItem;

class Chest extends Transparent {

	protected $id = self::CHEST;

	/**
	 * Chest constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return bool
	 */
	public function canBeActivated() : bool{
		return true;
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 2.5;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return '箱子';
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_AXE;
	}

	/**
	 * @return AxisAlignedBB
	 */
	protected function recalculateBoundingBox(){
		return new AxisAlignedBB(
			$this->x + 0.0625,
			$this->y,
			$this->z + 0.0625,
			$this->x + 0.9375,
			$this->y + 0.9475,
			$this->z + 0.9375
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
		$faces = [
			0 => 4,
			1 => 2,
			2 => 5,
			3 => 3,
		];

		$chest = null;
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];

		for($side = 2; $side <= 5; ++$side){
			if(($this->meta === 4 or $this->meta === 5) and ($side === 4 or $side === 5)){
				continue;
			}elseif(($this->meta === 3 or $this->meta === 2) and ($side === 2 or $side === 3)){
				continue;
			}
			$c = $this->getSide($side);
			if($c instanceof Chest and $c->getDamage() === $this->meta){
				$tile = $this->getLevel()->getTile($c);
				if($tile instanceof TileChest and !$tile->isPaired()){
                    $chest = $tile;
                    break;
				}
			}
		}

		$this->getLevel()->setBlock($block, $this, true, true);
		$nbt = new CompoundTag('', [
			new ListTag('Items', []),
			new StringTag('id', Tile::CHEST),
			new IntTag('x', $this->x),
			new IntTag('y', $this->y),
			new IntTag('z', $this->z)
		]);
		$nbt->Items->setTagType(NBT::TAG_Compound);

		if($item->hasCustomName()){
			$nbt->CustomName = new StringTag('CustomName', $item->getCustomName());
		}

		if($item->hasCustomBlockData()){
			foreach($item->getCustomBlockData() as $key => $v){
				if($key=='NeedTime'){
					$nbt->{'OpenTime'} = new StringTag('OpenTime', time()+$v->getValue());
				}elseif($key=='Name'){
					$nbt->{$key} = new StringTag('Name', strtolower($player->getName()));
				}else{
					$nbt->{$key} = $v;
				}
			}
		}

		$tile = Tile::createTile('Chest', $this->getLevel(), $nbt);

		if($chest instanceof TileChest and $tile instanceof TileChest){
			$chest->pairWith($tile);
			$tile->pairWith($chest);
		}

		return true;
	}
	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function onBreak(Item $item){
		$t = $this->getLevel()->getTile($this);
		if($t instanceof TileChest){
            $t->unpair();
            $t->close();
		}
        if ($item instanceof Mana){
            $this->getLevel()->setBlock($this, new Air(), false, false);
        }else{
            $this->getLevel()->setBlock($this, new Air(), true, true);
        }

		return true;
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		if($player instanceof Player){
			$top = $this->getSide(1);
			if($top->isTransparent() !== true){
				return true;
			}

			$t = $this->getLevel()->getTile($this);
			$chest = null;
			if($t instanceof TileChest){
				$chest = $t;
			}else{
				$nbt = new CompoundTag('', [
					new ListTag('Items', []),
					new StringTag('id', Tile::CHEST),
					new IntTag('x', $this->x),
					new IntTag('y', $this->y),
					new IntTag('z', $this->z)
				]);
				$nbt->Items->setTagType(NBT::TAG_Compound);
				$chest = Tile::createTile('Chest', $this->getLevel(), $nbt);
			}
			if($chest->isRewardBox()){
				if(count($chest->getInventory()->getViewers())>=1 and !$player->isOp()){
					$player->sendMessage('§l§a[提示]§c这个奖励箱已经有人在使用了！');
					return true;
				}
				if($chest->getRewardBoxName()!==strtolower($player->getName()) and !$player->isOp()){
					$player->sendMessage('§l§a[提示]§c这个不是你的奖励箱！');
					return true;
				}
				if($chest->openIng() and !$player->isOp()){
					$player->sendMessage('§l§a[提示]§c这个箱子正在打开中!当前还剩余:'.Utils::Sec2Time($chest->getOpenTime()-time()));
					return true;
				}
				// var_dump($chest->namedtag);
				if($chest->getRewardBoxType()=='空奖励箱' or $chest->getRewardBoxType()=='empty'){
					if($chest->getOpenTime()!==0){//时间够了~
						if($player->getItemInHand() instanceof Material and $player->getItemInHand()->getLTName()=='宝箱之钥'){
							//这个箱子要打开了~
							$OpenItem=$chest->getInventory()->getItem(0);
							$hand=$player->getItemInHand();
							$hand->setCount($hand->getCount()-1);
							$player->getInventory()->setItemInHand($hand);
							if($OpenItem instanceof Material){
								$lucky=$chest->getLucky();//宝箱的幸运值
								unset($chest->namedtag->OpenTime);
								unset($chest->namedtag->Lucky);
								$isluckyItem=$chest->getInventory()->getItem(1) instanceof Material and $chest->getInventory()->getItem(1)->getLTName()=='祝福水晶';
								$chest->getInventory()->setItem(0, Item::get(0));
								$chest->getInventory()->setItem(1, Item::get(0));
								$player->getTask()->action('开启神秘奖励', $OpenItem->getLTName());
								switch($OpenItem->getLTName()){
									case '神秘盔甲材料奖励':
										$rand=mt_rand(0, 500);
										if($isluckyItem)$rand+=100;
										switch(true){
											case $rand>=450:
												$item=LTItem::getInstance()->createMaterial('初级盔甲经验水晶');
												$item->setCount(20);
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=400:
												$item=LTItem::getInstance()->createMaterial('减控水晶');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=350:
												$item=LTItem::getInstance()->createMaterial('血之晶');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=300:
												$item=LTItem::getInstance()->createMaterial('黑色金刚石');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=100:
												$item=LTItem::getInstance()->createMaterial('盔甲精髓');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=0://这个箱子失败了 要爆炸了..
												$explosion = new Explosion($this, 4, $this, true, null);
												$explosion->explodeAA();
												$explosion->explodeB();
												$player->sendMessage('§l§a[提示]§c很不幸~你在开启箱子的时候出现了意外导致箱子爆炸了！');
												return true;
										break;
									}
									case '神秘武器材料奖励':
										$rand=mt_rand(0, 500);
										if($isluckyItem)$rand+=100;
										$Aitem=LTItem::getInstance()->createMaterial('初级武器经验水晶');
										$Aitem->setCount(20);
										switch(true){
											case $rand>=400:
												$item=LTItem::getInstance()->createMaterial('史诗武器图纸碎片');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=350:
												$item=LTItem::getInstance()->createMaterial('中毒魔切');
												$item->setCount(5);
												if($item instanceof Item)$chest->getInventory()->addItem($item);
												if($Aitem instanceof Item)$chest->getInventory()->addItem($Aitem);
											break;
											case $rand>=300:
												$item=LTItem::getInstance()->createMaterial('最后的轻语');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
												if($Aitem instanceof Item)$chest->getInventory()->addItem($Aitem);
											break;
											case $rand>=250:
												$item=LTItem::getInstance()->createMaterial('黑色金刚石');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
												if($Aitem instanceof Item)$chest->getInventory()->addItem($Aitem);
											break;
											case $rand>=200:
												$item=LTItem::getInstance()->createMaterial('PVP锋利之书');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
												if($Aitem instanceof Item)$chest->getInventory()->addItem($Aitem);
											break;
											case $rand>=150:
												$item=LTItem::getInstance()->createMaterial('医疗水晶');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=100:
												$item=LTItem::getInstance()->createMaterial('黑色尖刃');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=0://这个箱子失败了 要爆炸了..
												$explosion = new Explosion($this, 4, $this, true, null);
												$explosion->explodeAA();
												$explosion->explodeB();
												$player->sendMessage('§l§a[提示]§c很不幸~你在开启箱子的时候出现了意外导致箱子爆炸了！');
												return true;
											break;
										}
									break;
									case '史诗盔甲图纸奖励':
										$rand=mt_rand(0, 500);
										if($isluckyItem)$rand+=100;
										switch(true){
											case $rand>=420:
												$item=LTItem::getInstance()->createMaterial('高级盔甲经验水晶');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=340:
												$item=LTItem::getInstance()->createMaterial('史诗头盔图纸');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=260:
												$item=LTItem::getInstance()->createMaterial('史诗胸甲图纸');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=180:
												$item=LTItem::getInstance()->createMaterial('史诗护膝图纸');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=100:
												$item=LTItem::getInstance()->createMaterial('史诗战靴图纸');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=0://这个箱子失败了 要爆炸了..
												$explosion = new Explosion($this, 4, $this, true, null);
												$explosion->explodeAA();
												$explosion->explodeB();
												$player->sendMessage('§l§a[提示]§c很不幸~你在开启箱子的时候出现了意外导致箱子爆炸了！');
												return true;
											break;
										}
									break;
									case '神秘饰品奖励':
										$rand=mt_rand(0, 500);
										if($isluckyItem)$rand+=$lucky+100;
										switch(true){
											case $rand>=490:
												$item=LTItem::getInstance()->createOrnaments('幸运女神的祝福');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=480:
												$item=LTItem::getInstance()->createOrnaments('四项之力');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=440:
												$item=LTItem::getInstance()->createOrnaments('幸运女神的头饰');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=400:
												$item=LTItem::getInstance()->createOrnaments('幕刃');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=350:
												$item=LTItem::getInstance()->createOrnaments('饮血剑');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=300:
												$item=LTItem::getInstance()->createOrnaments('破旧的布甲');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=250:
												$item=LTItem::getInstance()->createOrnaments('穿甲之力');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=200:
												$item=LTItem::getInstance()->createOrnaments('迅捷之石');
												if($item instanceof Item)$chest->getInventory()->addItem($item);
											break;
											case $rand>=150:
												$explosion = new Explosion($this, 4, $this, true, null);
												$explosion->explodeB();
												$player->sendMessage('§l§a[提示]§c很不幸~你在开启箱子的时候出现了意外导致箱子爆炸了！');
												return true;
											break;
											case $rand<=100://这个箱子失败了 要爆炸了..
												$explosion = new Explosion($this, 4, $this, true, null);
												$explosion->explodeAA();
												$explosion->explodeB();
												$player->sendMessage('§l§a[提示]§c很不幸~你在开启箱子的时候出现了意外导致箱子爆炸了！');
												return true;
											break;
										}
									break;
								}
							}else{
								$player->sendMessage('§l§a[提示]§c箱子出现了意外！');
							}
						}else{
							$player->sendMessage('§l§a[提示]§c你必须手持宝箱之钥来打开它！');
							return true;
						}
					}
				}elseif($chest->getRewardBoxType()=='奖励箱-基因'){
				if($player->getItemInHand() instanceof Material and $player->getItemInHand()->getLTName()=='宝箱之钥'){
						unset($chest->namedtag->OpenTime);
						unset($chest->namedtag->Name);
						unset($chest->namedtag->Type);
						switch(mt_rand(1, 3)){
							case 1:
								$item=LTItem::getInstance()->createMaterial('基因精髓-法师');
								if($item instanceof Item)$chest->getInventory()->addItem($item);
							break;
							case 2:
								$item=LTItem::getInstance()->createMaterial('基因精髓-牧师');
								if($item instanceof Item)$chest->getInventory()->addItem($item);
							break;
							case 3:
								$item=LTItem::getInstance()->createMaterial('基因精髓-刺客');
								if($item instanceof Item)$chest->getInventory()->addItem($item);
							break;
						}
					}else{
						$player->sendMessage('§l§a[提示]§c你必须手持宝箱之钥来打开它！');
						return true;
					}
				}
			}
			$player->addWindow($chest->getInventory());
		}

		return true;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		return [
			[$this->id, 0, 1],
		];
	}
}
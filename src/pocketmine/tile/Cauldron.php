<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\tile;

use LTItem\LTItem;
use LTItem\Main;
use pocketmine\block\Cobblestone;
use pocketmine\block\Slab;
use pocketmine\entity\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\AngryVillagerParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\sound\SplashSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\Color;

class Cauldron extends Spawnable {
    public $age = 0;
    private $completeManyBlockStructure = false;
	/**
	 * Cauldron constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		if(!isset($nbt->PotionId) or !($nbt->PotionId instanceof ShortTag)){
			$nbt->PotionId = new ShortTag("PotionId", 0xffff);
		}
		if(!isset($nbt->SplashPotion) or !($nbt->SplashPotion instanceof ByteTag)){
			$nbt->SplashPotion = new ByteTag("SplashPotion", 0);
		}
		if(!isset($nbt->Items) or !($nbt->Items instanceof ListTag)){
			$nbt->Items = new ListTag("Items", []);
		}
		parent::__construct($level, $nbt);
	}

    /**
     * @return bool
     */
    public function getCompleteManyBlockStructure(){
        if ($this->isCustomColor())return false;
        return $this->checkManyBlockStructure();
	    //return $this->completeManyBlockStructure;
    }
	/**
	 * @return mixed|null
	 */
	public function getPotionId(){
		return $this->namedtag["PotionId"];
	}

    /**
     * @return array
     */
    public function getEntities(){
	    $box = $this->getBlock()->getBoundingBox();
        $entities = [];
	    foreach ($this->getLevel()->getEntities() as $entity){
	        if ($entity instanceof Item and $box->isVectorInside($entity->add(0, -0.1, 0))){
                $entities[] = $entity;
            }
        }
	    return $entities;
    }

    /**
     * @return bool|void
     */
    public function onUpdate()
    {
        $drops = $this->getEntities();
        if (count($drops)<=0)return false;
        $dropStr = [];
        foreach ($drops as $drop){
            /** @var Item $drop */
            if ($drop->getItem() instanceof LTItem){
                $dropStr[] = $drop->getItem()->getTypeName().':'.$drop->getItem()->getLTName().':'.$drop->getItem()->getCount();
            }else{
                 $dropStr[] = $drop->getItem()->getId().':'.$drop->getItem()->getDamage().':'.$drop->getItem()->getCount();
            }
        }
        $item = $this->CalculationResults($dropStr);
        if ($item!==null){
            foreach ($drops as $drop){
                $drop->close();
            }
            $this->getLevel()->dropItem($this->getBlock()->add(0.5, 1, 0.5), $item, new Vector3(0,0,0));
            $block = $this->getBlock();
            $block->setDamage(0);
            $this->getLevel()->setBlock($block, $block, true);
            $this->getLevel()->addSound(new SplashSound($this->add(0.5, 1, 0.5)));
        }
        return false;
    }

    /**
     * @param array $items
     *
     * @return \pocketmine\item\Item
     */
    public function CalculationResults(array $items){
        $table = [
            '材料:白雏菊' =>[
                '38:3:4',
            ],
            '材料:火红莲' =>[
                '351:7:2',
                '37:0:1',
                '38:0:1',
            ],
            '材料:咀叶花' =>[
                '38:0:1',
                '31:1:3',
            ],
            '材料:石中姬' =>[
                '351:8:2',
                '351:0:1',
                '材料:盖亚之魂:1',
            ],
            '材料:彼方兰' =>[
                '351:7:2',
                '37:0:2',
                '38:0:1',
            ],
        ];
        $keys = array_flip($items);
        foreach ($table as $itemStr=>$need){
            $itemsTmp = $items;
            $need[] = '295:0:1';
            foreach ($need as $i => $needS){
                if (in_array($needS, $itemsTmp)){
                    unset($need[$i]);
                    unset($itemsTmp[$keys[$needS]]);
                }else continue 2;
            }
            if (count($need)<=0 and count($itemsTmp)<=0){
                $type = explode(':', $itemStr);
                switch ($type[0]){
                    case '材料':
                        return Main::getInstance()->createMaterial($type[1]);
                    break;
                    default:
                        return \pocketmine\item\Item::get($type[0], $type[1]);
                    break;
                }
            }
        }
        return null;
    }
    /**
     * 检查多方块结构
     * @return bool
     */
    public function checkManyBlockStructure() : bool {
	    $block = $this->getLevel()->getBlock($this->add(1,0,0));
	    if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在上方
        $block = $this->getLevel()->getBlock($this->add(-1,0,0));
	    if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在上方
        $block = $this->getLevel()->getBlock($this->add(0,0,-1));
	    if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在上方
        $block = $this->getLevel()->getBlock($this->add(0,0,1));
	    if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在上方
        $block = $this->getLevel()->getBlock($this->add(0,-1,0));
        if (!($block instanceof Cobblestone))return false;//不是原石
        $block = $this->getLevel()->getBlock($this->add(0,-2,0));
	    if (!($block instanceof Cobblestone))return false;//不是原石
        $block = $this->getLevel()->getBlock($this->add(1,-2,0));
        if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在下方
        $block = $this->getLevel()->getBlock($this->add(-1,-2,0));
        if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在下方
        $block = $this->getLevel()->getBlock($this->add(0,-2,-1));
        if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在下方
        $block = $this->getLevel()->getBlock($this->add(0,-2,1));
        if (!($block instanceof Slab) or $block->getDamage() != 3)return false;//不是半砖或者不在下方
        return true;
    }
    /**
	 * @param $potionId
	 */
	public function setPotionId($potionId){
		$this->namedtag->PotionId = new ShortTag("PotionId", $potionId);
		$this->onChanged();
	}

	/**
	 * @return bool
	 */
	public function hasPotion(){
		return $this->namedtag["PotionId"] !== 0xffff;
	}

	/**
	 * @return bool
	 */
	public function getSplashPotion(){
		return ($this->namedtag["SplashPotion"] == true);
	}

	/**
	 * @param $bool
	 */
	public function setSplashPotion($bool){
		$this->namedtag->SplashPotion = new ByteTag("SplashPotion", ($bool == true) ? 1 : 0);
		$this->onChanged();
	}

	/**
	 * @return null|Color
	 */
	public function getCustomColor(){//
		if($this->isCustomColor()){
			$color = $this->namedtag["CustomColor"];
			$green = ($color >> 8) & 0xff;
			$red = ($color >> 16) & 0xff;
			$blue = ($color) & 0xff;
			return Color::getRGB($red, $green, $blue);
		}
		return null;
	}

	/**
	 * @return int
	 */
	public function getCustomColorRed(){
		return ($this->namedtag["CustomColor"] >> 16) & 0xff;
	}

	/**
	 * @return int
	 */
	public function getCustomColorGreen(){
		return ($this->namedtag["CustomColor"] >> 8) & 0xff;
	}

	/**
	 * @return int
	 */
	public function getCustomColorBlue(){
		return ($this->namedtag["CustomColor"]) & 0xff;
	}

	/**
	 * @return bool
	 */
	public function isCustomColor(){
		return isset($this->namedtag->CustomColor);
	}

	/**
	 * @param     $r
	 * @param int $g
	 * @param int $b
	 */
	public function setCustomColor($r, $g = 0xff, $b = 0xff){
		if($r instanceof Color){
			$color = ($r->getRed() << 16 | $r->getGreen() << 8 | $r->getBlue()) & 0xffffff;
		}else{
			$color = ($r << 16 | $g << 8 | $b) & 0xffffff;
		}
		$this->namedtag->CustomColor = new IntTag("CustomColor", $color);
		$this->onChanged();
	}

	public function clearCustomColor(){
		if(isset($this->namedtag->CustomColor)){
			unset($this->namedtag->CustomColor);
		}
		$this->onChanged();
	}

	/**
	 * @return CompoundTag
	 */
	public function getSpawnCompound(){
		$nbt = new CompoundTag("", [
			new StringTag("id", Tile::CAULDRON),
			new IntTag("x", (Int) $this->x),
			new IntTag("y", (Int) $this->y),
			new IntTag("z", (Int) $this->z),
			new ShortTag("PotionId", $this->namedtag["PotionId"]),
			new ByteTag("SplashPotion", $this->namedtag["SplashPotion"]),
			new ListTag("Items", $this->namedtag["Items"])//unused?
		]);

		if($this->getPotionId() === 0xffff and $this->isCustomColor()){
			$nbt->CustomColor = $this->namedtag->CustomColor;
		}
		return $nbt;
	}
}

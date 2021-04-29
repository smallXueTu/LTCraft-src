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
use LTItem\Mana\ManaSystem;
use pocketmine\block\Block;
use pocketmine\block\Cobblestone;
use pocketmine\block\Lapis;
use pocketmine\block\LivingStones;
use pocketmine\block\Slab;
use pocketmine\entity\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\sound\SplashSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class TaraCondensationPlate extends Tile {
    const MAX_PROGREES = 100000;

    public $progress = 0;//MAX 100000
    public $sleepTick = 0;
    /**
	 * DLDetector constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->scheduleUpdate();
	}
    /**
     * @return array
     */
    public function getEntities(){
        $entities = [];
        foreach ($this->getLevel()->getEntities() as $entity){
            if ($entity instanceof Item and $entity->distance($this)<=5){
                $entities[] = $entity;
            }
        }
        return $entities;
    }
    public function checkBlock(){
        $block = $this->getLevel()->getBlock($this->add(0,-1,0));
        if (!($block instanceof LivingStones))return false;//不是活石
        $block = $this->getLevel()->getBlock($this->add(1,-1,1));
        if (!($block instanceof LivingStones))return false;//不是活石
        $block = $this->getLevel()->getBlock($this->add(-1,-1,1));
        if (!($block instanceof LivingStones))return false;//不是活石
        $block = $this->getLevel()->getBlock($this->add(1,-1,-1));
        if (!($block instanceof LivingStones))return false;//不是活石
        $block = $this->getLevel()->getBlock($this->add(-1,-1,-1));
        if (!($block instanceof LivingStones))return false;//不是活石
        $block = $this->getLevel()->getBlock($this->add(1,-1,0));
        if (!($block instanceof Lapis))return false;//不是青金石
        $block = $this->getLevel()->getBlock($this->add(-1,-1,0));
        if (!($block instanceof Lapis))return false;//不是青金石
        $block = $this->getLevel()->getBlock($this->add(0,-1,-1));
        if (!($block instanceof Lapis))return false;//不是青金石
        $block = $this->getLevel()->getBlock($this->add(0,-1,1));
        if (!($block instanceof Lapis))return false;//不是青金石
        return true;
    }
	/**
	 * @return bool
	 */
	public function onUpdate(){
        if ($this->sleepTick > 0){//等待ing
            $this->sleepTick--;
            return true;
        }
//        $this->progress += 100;
//        $this->spawnParticle();
//        return true;
        $drops = $this->getEntities();
        if (count($drops)<=0)return false;
        if (!$this->checkBlock())return false;
        $searchManaCache = ManaSystem::searchManaCache($this);//搜索附近魔力缓存器来抽取魔力
        if (count($searchManaCache)==0){
            $this->sleepTick = 20*30;//等待30 S
            return true;//等待玩家添加魔力缓存器
        }
        $dropStr = [];
        foreach ($drops as $drop){
            /** @var Item $drop */
            if ($drop->getItem() instanceof LTItem){
                $dropStr[] = $drop->getItem()->getTypeName().':'.$drop->getItem()->getLTName().':'.$drop->getItem()->getCount();
            }else{
                $dropStr[] = $drop->getItem()->getId().':'.$drop->getItem()->getDamage().':'.$drop->getItem()->getCount();
            }
        }
        //TODO：改善粒子效果 由蓝渐变为绿
        $item = $this->CalculationResults($dropStr);//获取结果
        if ($item!==null){
            foreach ($drops as $drop){
                $drop->setAge(1);
            }
            if ($this->progress < self::MAX_PROGREES){
                //每Tick一个缓存器抽取100点 则需要 100000/(100*20) = 50秒
                //从魔力缓存器抽取Mana
                foreach ($searchManaCache as $manaCeche){
                    /** @var ManaCache $manaCeche */
                    if ($manaCeche->putMana(100, $this)){//抽取100 Mana
                        $this->progress += 100;
                    }
                    if ($this->progress >= self::MAX_PROGREES){
                        break;
                    }
                }
                $this->spawnParticle();//粒子效果
                if ($this->progress < self::MAX_PROGREES)return true;//下 tick继续
            }
            foreach ($drops as $drop){
                $drop->close();
            }
            $this->getLevel()->dropItem($this->getBlock()->add(0.5, 1, 0.5), $item, new Vector3(0,0,0));
        }else{
            $this->progress = 0;
        }
		return true;
	}

    /**
     * 粒子效果
     */
    public function spawnParticle(){
        $i = $this->progress / 100 % 360;
        $r = 1.5 / 360 * $i;
        $yy = 1 / 360 * $i;
        $x=$this->x + 0.5;
        $y=$this->y + 0.4;
        $z=$this->z + 0.5;
        if (floor(floor($this->progress / 100) / 360) % 2 != 0){
            $r = 1.5 - $r;
            $yy = 1 - $yy;
        }
        $r += 0.5;
        for ($ii = 0; $ii < 4; $ii++){
            $iii = $i + $ii * 45;
            if ($iii >= 360){
                $iii -= 360;
            }
            $a = $x+$r*cos($iii*3.14/90);
            $b = $z+$r*sin($iii*3.14/90);
            $this->getLevel()->addParticle(new DustParticle(new Vector3($a,$y + $yy,$b), 0, 220, 0));
        }
    }

    /**
     * @param array $items
     *
     * @return \pocketmine\item\Item
     */
    public function CalculationResults(array $items){
        $table = [
            '材料:泰拉钢锭' =>[
                '材料:魔力钢锭:1',
                '材料:魔力钻石:1',
                '材料:魔力珍珠:1',
            ],
            '材料:泰拉钢块' =>[
                '材料:魔力钢块:1',
                '材料:魔力钻块:1',
                '材料:魔力珍珠:9',
            ],
        ];
        $keys = array_flip($items);
        foreach ($table as $itemStr=>$need){
            $itemsTmp = $items;
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
}
<?php
namespace LTItem\SpecialItems\Weapon;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

/**
 * Class Trident
 * @package LTItem\SpecialItems\Weapon
 * 失落的三叉戟
 */

class Trident extends \LTItem\SpecialItems\Weapon implements DrawingKnife
{

    public function __construct(array $conf, int $count, CompoundTag $nbt, $init = true)
    {
        parent::__construct($conf, $count, $nbt, $init);
        if(!isset($nbt['attribute'][26])){
            $nbt = $this->getNamedTag();
            $nbt['attribute'][25]=new StringTag('',$nbt['attribute'][25]??0);//25 荣耀值
            $nbt['attribute'][26]=new StringTag('',$nbt['attribute'][26]??0);//26 杀敌数
            $this->setNamedTag($nbt);
        }
        $this->updateName();
    }
    public function updateName(){
        $this->setCustomName($this->conf['武器名'].PHP_EOL.'§c荣耀值:'.$this->getGlory().PHP_EOL.'杀敌数:'.$this->getKills());
    }
    public function addGlory(int $number)
    {
        $nbt = $this->getNamedTag();
        $nbt['attribute'][25]=new StringTag('',$nbt['attribute'][25] + $number);//25 荣耀值
        $this->setNamedTag($nbt);
        $this->updateName();
        return $this;
    }

    public function getGlory(): int
    {
        return $this->getNamedTag()[25];
    }

    public function getKills(): int
    {
        return $this->getNamedTag()[26];
    }

    public function addKills(int $number)
    {
        $nbt = $this->getNamedTag();
        $nbt['attribute'][26]=new StringTag('',$nbt['attribute'][25] + $number);//26 杀敌数
        $this->setNamedTag($nbt);
        $this->updateName();
        return $this;
    }
}
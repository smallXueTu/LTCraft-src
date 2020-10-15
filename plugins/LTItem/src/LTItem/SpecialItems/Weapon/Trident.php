<?php
namespace LTItem\SpecialItems\Weapon;

use LTItem\Mana\Mana;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\BaseInventory;
use pocketmine\level\particle\Particle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

/**
 * Class Trident
 * @package LTItem\SpecialItems\Weapon
 * 失落的三叉戟
 */

class Trident extends \LTItem\SpecialItems\Weapon implements DrawingKnife, Mana
{
    const MAX_MANA = 10000;
    public int $Mana = 0;
    private int $lastDamage;

    public function __construct(array $conf, int $count, CompoundTag $nbt, $init = true)
    {
        parent::__construct($conf, $count, $nbt, $init);
        $nbt = $this->getNamedTag();
        if(!isset($nbt['attribute'][27])){
            $nbt['attribute'][25]=new StringTag('',$nbt['attribute'][25]??0);//25 荣耀值
            $nbt['attribute'][26]=new StringTag('',$nbt['attribute'][26]??0);//26 杀敌数
            $nbt['attribute'][27]=new StringTag('',$nbt['attribute'][27]??0);//26 对于 Trident来说 27就是Mana
            $this->setNamedTag($nbt);
        }
        $this->Mana = $nbt['attribute'][27];
        $this->updateName();
    }
    public function initW($conf = null)
    {
        parent::initW($conf);
        $addPVEDamage = 0;//附加PVE伤害
        $addPVPDamage = 0;//附加PVP伤害
        $addPVEVampire = 0;//附加PVE吸血
        $addPVPVampire = 0;//附加PVP吸血
        $addPVPArmour = 0;//附加PVP穿甲
        if ($this->getGlory() > 0){
            for ($i = 1; $i <= $this->getGlory(); $i++){
                switch (true){
                    case $i <= 10:
                        $addPVEDamage += 10;
                        $addPVEVampire += 0.0005;
                        $addPVPDamage += 1;
                        $addPVPVampire += 0.02;
                        $addPVPArmour += 0.5;
                    break;
                    case $i <= 30:
                        $addPVEDamage += 8;
                        $addPVEVampire += 0.0003;
                        $addPVPDamage += 0.5;
                        $addPVPVampire += 0.01;
                        $addPVPArmour += 0.3;
                    break;
                    case $i <= 60:
                        $addPVEDamage += 5;
                        $addPVEVampire += 0.0002;
                        $addPVPDamage += 0.2;
                        $addPVPVampire += 0.005;
                        $addPVPArmour += 0.2;
                    break;
                    case $i <= 100:
                        $addPVEDamage += 3;
                        $addPVEVampire += 0.0001;
                        $addPVPDamage += 0.1;
                        $addPVPVampire += 0.005;
                        $addPVPArmour += 0.1;
                    break;
                }
            }
        }
        $this->updateName();
    }

    /**
     * 更新名字
     */
    public function updateName(){
        $this->setCustomName($this->conf['武器名'].PHP_EOL.'§c荣耀值:'.$this->getGlory().PHP_EOL.'杀敌数:'.$this->getKills().PHP_EOL.'§eMana:'.$this->getMana(), true);
    }

    /**
     * 增加荣耀值
     * @param int $number
     * @return $this|mixed
     */
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
        return $this->getNamedTag()['attribute'][25];
    }

    public function getKills(): int
    {
        return $this->getNamedTag()['attribute'][26];
    }

    public function addKills(int $number)
    {
        $nbt = $this->getNamedTag();
        $nbt['attribute'][26]=new StringTag('',$nbt['attribute'][26] + $number);
        $this->setNamedTag($nbt);
        $this->updateName();
        return $this;
    }

    public function getOwner(): string
    {
        return $this->getBinding();
    }

    public function getMaxMana(): int
    {
        return self::MAX_MANA;
    }

    public function getMana(): int
    {
        return $this->Mana;
    }

    public function addMana(int $mana)
    {
        $this->Mana += $mana;
        if ($this->Mana>$this->getMaxMana()){
            $this->Mana = $this->getMaxMana();
        }
        $this->saveMana();
    }

    public function consumptionMana(int $mana): bool
    {
        if ($this->Mana < $mana)return false;
        $this->Mana -= $mana;
        $this->saveMana();
        return true;
    }

    public function onTick(Player $player, int $index, BaseInventory $inventory): bool
    {
        if (!$this->canUse($player) and $player->getServer()->getTick() - $this->lastDamage > 10){
            $this->lastDamage = $player->getServer()->getTick();
            $player->attack(1, new EntityDamageEvent($player, EntityDamageEvent::CAUSE_PUNISHMENT, 1, true));
        }
        if ($player->getItemInHand()->equals($this)){
            $level = $player->getLevel();
            $level->addParticle(new PortalParticle($player));
        }
        return true;
    }

    /**
     * 保存Mana
     */
    public function saveMana(){
        $tag = $this->getNamedTag();
        $tag['attribute'][27] = new StringTag('', $this->Mana);
        $this->setNamedTag($tag);
    }
}
<?php
namespace LTItem\SpecialItems\Weapon;

use LTItem\Mana\Mana;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\BaseInventory;
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
    const MAX_MANA = 1000000;
    public int $Mana = 0;
    private int $lastDamage = 0;

    public function __construct(array $conf, int $count, CompoundTag $nbt, $init = true)
    {
        parent::__construct($conf, $count, $nbt, $init);
        $nbt = $this->getNamedTag();
        if(!isset($nbt['attribute'][30])){
            $nbt['attribute'][25]=new StringTag('',$nbt['attribute'][25]??0);//25 荣耀值
            $nbt['attribute'][26]=new StringTag('',$nbt['attribute'][26]??0);//26 杀敌数
            $nbt['attribute'][27]=new StringTag('',$nbt['attribute'][27]??0);//27 对于 Trident来说 27就是Mana
            $nbt['attribute'][28]=new StringTag('',$nbt['attribute'][28]??100);//28 耐久
            $nbt['attribute'][29]=new StringTag('',$nbt['attribute'][29]??'');//29 意志
            $nbt['attribute'][30]=new StringTag('',$nbt['attribute'][30]??0);//30 锻造数
            $this->setNamedTag($nbt);
        }
        $this->Mana = $nbt['attribute'][27];
        $this->updateName();
    }

    /**
     * 获取耐久值
     * @return int
     */
    public function getDurable(): int
    {
        $nbt = $this->getNamedTag();
        return (int)$nbt['attribute'][28];
    }

    /**
     * 设置耐久度
     * @param int $durable
     * @return DrawingKnife
     */
    public function setDurable(int $durable): DrawingKnife
    {
        if ($durable > DrawingKnife::MAX_DURABLE) $durable = DrawingKnife::MAX_DURABLE;
        $nbt = $this->getNamedTag();
        $nbt['attribute'][28]=new StringTag('',$durable);//28 耐久
        $this->setNamedTag($nbt);
        $this->updateName();
        return $this;
    }

    /**
     * 初始化
     * @param null $conf
     */
    public function initW($conf = null)
    {
        parent::initW($conf);
        $addPVEDamage = 0;//附加PVE伤害
        $addPVPDamage = 0;//附加PVP伤害
        $addPVEVampire = 0;//附加PVE吸血
        $addPVPVampire = 0;//附加PVP吸血
        $addPVPArmour = 0;//附加PVP穿甲
        if ($this->getForging() > 0){
            for ($i = 1; $i <= $this->getForging(); $i++){
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
        $this->PVEdamage += $addPVEDamage;
        $this->PVPdamage += $addPVPDamage;
        $this->PVEVampire += $addPVEVampire;
        $this->PVPVampire += $addPVPVampire;
        $this->PVEArmour += $addPVPArmour;
        $this->updateName();
    }

    /**
     * 更新名字
     */
    public function updateName(){
        $this->setCustomName($this->conf['武器名'].PHP_EOL.'§c荣耀值:'.$this->getGlory().PHP_EOL.'锻造值:'.$this->getForging().PHP_EOL.'杀敌数:'.$this->getKills().PHP_EOL.'耐久度:'.$this->getDurable().PHP_EOL.'§eMana:'.$this->getMana().PHP_EOL.'§a'.$this->getWill().PHP_EOL.$this->getWill(2), true);
    }

    /**
     * 获取第 $number 个槽的意志
     *
     * @param int $number 只有 1和 2 0为’‘（空字符串）
     * @return string
     * TODO: 改善它
     */
    public function getWill(int $number = 1){
        $nbt = $this->getNamedTag();
        $arr = explode(':', $nbt['attribute'][29]);
        return $arr[$number]??'无意志';
    }

    /**
     * 增加意志
     * @param string $name
     * @return $this|mixed
     */
    public function addWill(string $name){
        $nbt = $this->getNamedTag();
        $nbt['attribute'][29]=new StringTag('',$nbt['attribute'][29] .':'. $name);//26 意志
        $this->setNamedTag($nbt);
        $this->updateName();
        return $this;
    }

    /**
     * 获取此武器装了几个意志
     * @return int
     */
    public function getWillCount(){
        $nbt = $this->getNamedTag();
        return count(explode(':', $nbt['attribute'][29])) - 1;
    }

    /**
     * 检查此武器是否包含一个意志
     * @param string $name
     * @return bool
     */
    public function containWill(string $name){
        $nbt = $this->getNamedTag();
        return strpos($nbt['attribute'][29], $name)!=false;
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
        return (int)$this->getNamedTag()['attribute'][25];
    }

    /**
     * 增加荣耀值
     * @param int $number
     * @return $this|mixed
     */
    public function addForging(int $number) :DrawingKnife
    {
        $nbt = $this->getNamedTag();
        $nbt['attribute'][30]=new StringTag('',$nbt['attribute'][30] + $number);//30 锻造值
        $this->setNamedTag($nbt);
        $this->updateName();
        return $this;
    }

    public function getForging(): int
    {
        return (int)$this->getNamedTag()['attribute'][30];
    }

    public function getKills(): int
    {
        return (int)$this->getNamedTag()['attribute'][26];
    }

    public function addKills(int $number)
    {
        $nbt = $this->getNamedTag();
        $nbt['attribute'][26]=new StringTag('',$nbt['attribute'][26] + $number);
        $this->setNamedTag($nbt);
        $this->updateName();
        return $this;
    }

    /**
     * @param Player $player
     * @param bool $playerCheck
     * @return bool
     */
    public function canUse(Player $player, $playerCheck = true):bool
    {
        return parent::canUse($player, $playerCheck);
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

    /**
     * @param int $mana
     * @return mixed|void
     */
    public function addMana(int $mana)
    {
        $this->Mana += $mana;
        if ($this->Mana>$this->getMaxMana()){
            $this->Mana = $this->getMaxMana();
        }
        $this->saveMana();
    }

    /**
     * @param int $mana
     * @return bool
     */
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
            $player->attack($player->getMaxHealth() * 0.1, new EntityDamageEvent($player, EntityDamageEvent::CAUSE_PUNISHMENT, $player->getMaxHealth() * 0.1, true));
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

    /**
     * @param Player $player
     * @return string
     */
    public function getHandMessage(Player $player): string
    {
        if($this->canUse($player, false)){
            return parent::getHandMessage($player).'§e耐久:'.$this->getDurable();
        }else return '你不是这个武器的拥有者！';
    }
}
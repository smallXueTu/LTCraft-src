<?php
namespace LTItem\SpecialItems\Armor;

use LTItem\Cooling;
use LTItem\SpecialItems\Armor;
use LTItem\Mana\Mana;
use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\particle\Particle;
use pocketmine\level\Position;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\Server;

class ManaArmor extends Armor implements Mana, ReduceMana
{
    private int $Mana;
    const STORAGE_UPGRADE_MAX = 3;
    const NOTE_MAGIC_UPGRADE_MAX = 3;
    private int $MaxMana;
    private int $lastDamage = 0;
    private int $lastRecharge = 0;
    private int $noteMagicSpeed = 1;
    private int $reduceMana = 5;

    public function __construct(array $conf, int $count, \pocketmine\nbt\tag\CompoundTag $nbt, $init = true)
    {
        parent::__construct($conf, $count, $nbt, $init);

        $nbt = $this->getNamedTag();
        if(!isset($nbt['armor'][18])){
            $nbt['armor'][15]=new StringTag('',$nbt['armor'][15]??0);//15 对于 ManaArmor来说 15就是Mana
            $nbt['armor'][16]=new StringTag('',$nbt['armor'][16]??1);//16
            $nbt['armor'][17]=new StringTag('',$nbt['armor'][17]??1);//17
            $nbt['armor'][18]=new StringTag('',$nbt['armor'][17]??5);//18 耗魔减少
            $this->setNamedTag($nbt);
        }
        $this->Mana = $nbt['armor'][15];
        $this->MaxMana = $nbt['armor'][16] * 100;
        $this->noteMagicSpeed = $nbt['armor'][17];
        $this->reduceMana = $nbt['armor'][18];
        $this->updateName();
    }

    /**
     * 更新名字
     */
    public function updateName(){
        $this->setCustomName($this->getLTName().PHP_EOL.'§d储魔升级:'.$this->getStorageUpgrade().PHP_EOL.'§d注魔升级:'.$this->getNoteMagicUpgrade().PHP_EOL.'§eMana:'.$this->getMana(), true);
    }

    /**
     * @return string 获取这个物品的绑定
     */
    public function getOwner(): string
    {
        return $this->getBinding();
    }

    /**
     * @return int 最大Mana
     */
    public function getMaxMana(): int
    {
        return $this->MaxMana;
    }

    /**
     * @return int 剩余Mana
     */
    public function getMana(): int
    {
        return $this->Mana;
    }

    /**
     * 保存Mana
     */
    public function saveMana(){
        $tag = $this->getNamedTag();
        $tag['armor'][15] = new StringTag('', $this->Mana);
        $this->setNamedTag($tag);
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
        $this->updateName();
        $this->saveMana();
    }
    public function setMana(int $mane){
        $this->Mana = $mane;
        if ($this->Mana>$this->getMaxMana()){
            $this->Mana = $this->getMaxMana();
        }
        $this->updateName();
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
        $this->updateName();
        return true;
    }

    /**
     * 注魔升级
     * @return int
     */
    public function getStorageUpgrade(): int
    {
        return $this->MaxMana;
    }
    public function setStorageUpgrade(int $value): ManaArmor
    {
        $tag = $this->getNamedTag();
        $tag['armor'][16] = new StringTag('', $value);
        $this->MaxMana = $value * 100;
        $this->setNamedTag($tag);
        return $this;
    }

    /** 获取储魔升级
     * @return int
     */
    public function getNoteMagicUpgrade(): int
    {
        return $this->noteMagicSpeed;
    }
    public function setNoteMagicUpgrade(int $value): ManaArmor
    {
        $tag = $this->getNamedTag();
        $tag['armor'][17] = new StringTag('', $value);
        $this->noteMagicSpeed = $value;
        $this->setNamedTag($tag);
        return $this;
    }
    public function onTick(Player $player, int $index, BaseInventory $inventory): bool
    {
        /** @var PlayerInventory $inventory */
        if ($player->getServer()->getTick() - $this->lastDamage > 10){
            if (!$this->canUse($player)){
                $this->lastDamage = $player->getServer()->getTick();
                $player->attack($player->getMaxHealth() * 0.1, new EntityDamageEvent($player, EntityDamageEvent::CAUSE_PUNISHMENT, $player->getMaxHealth() * 0.1, true));
            }
        }
        if ($player->getServer()->getTick() - $this->lastRecharge > 40){
            $this->lastRecharge = $player->getServer()->getTick();
            if ($this->getMana() < $this->getMaxMana()){
                $mana = min($this->getMaxMana() - $this->getMana(), $this->noteMagicSpeed * 4);
                if ($player->getBuff()->consumptionMana($mana)){
                    $this->addMana($mana);
                    $inventory->setItem($index, $this);
                }
            }
        }
        return true;
    }

    public function canPutMana(): bool
    {
        return false;
    }
    public function canUse(\pocketmine\Player $player, $playerCheck = true):bool
    {
        return parent::canUse($player, $playerCheck);
    }
    public static function shield(Player $player, $damager = null){
        if (Server::getInstance()->getTick() - Cooling::$manaArmorShield[$player->getName()] < 10)return;//动画和生效冷却
        Cooling::$manaArmorShield[$player->getName()] = Server::getInstance()->getTick();
        self::spawnParticle($player, $damager);
        $player->getLevel()->addSound(new AnvilFallSound($player));
    }

    /**
     * 护盾粒子 圆球
     * //TODO: 优化它！
     * @param Position $position
     * @param null $damager
     */
    public static function spawnParticle(Position $position, $damager = null){
        $level = $position->getLevel();
        $h = 2.8 / 45;
        $y = $position->getY() - 0.3;
        $v3s = [];
        for ($j = 2; $j < 43; $j++){
            $d = 22 - abs($j - 22);
            $c = abs(1.25 * sin($d * 3.14 / 45));
            $g = 45 / max(1, $d);
            for($i = 1; $i <= $d ; $i++){
                $a=$position->getX() + $c * cos($i * $g * 3.14 / 22.5);
                $b=$position->getZ() + $c * sin($i * $g * 3.14 / 22.5);
                $v3s[] = new Vector3($a,$y + $h * $j,$b);
            }
        }
        if ($damager != null){
            /** @var Creature $damager */
            $damager = $damager->add(0, $damager->getEyeHeight(), 0);
            $minV3 = [PHP_INT_MAX, null];
            /** @var Vector3 $v3 */
            foreach ($v3s as $v3){
                $d = $v3->distance($damager);
                if ($d < $minV3[0])$minV3 = [$d, $v3];
            }
            foreach ($v3s as $v3){
                if ($v3->distance($minV3[1]) < 0.8)
                    $level->addParticle(new GenericParticle($v3,Particle::TYPE_REDSTONE));
            }
        }else{
            foreach ($v3s as $v3){
                $level->addParticle(new GenericParticle($v3,Particle::TYPE_REDSTONE));
            }
        }
    }

    /**
     * @return int|mixed
     */
    public function getReduceMana()
    {
        return $this->reduceMana;
    }

    /**
     * @param $value
     * @return ManaArmor
     */
    public function setReduceMana($value): ManaArmor
    {
        $tag = $this->getNamedTag();
        $tag['armor'][18] = new StringTag('', $value);
        $this->reduceMana = $value;
        $this->setNamedTag($tag);
        return $this;
    }
    public function getReduce(): float
    {
        if (substr($this->getLTName(), 0, strlen("源钢")) == '源钢')
            return $this->getReduceMana() / 100;
        else
            return 0;
    }
}
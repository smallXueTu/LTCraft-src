<?php

namespace LTItem\Mana;

use LTItem\LTItem;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\BaseInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\utils\UUID;

class BaseMana extends Item implements LTItem,Mana
{
    public $ManaName;
    private $handMessage = false;
    /**
     * @var array
     */
    private $conf;
    protected $Mana = 0;
    protected $Logo = '';

    public $lastDamage = 0;

    public $binding = '*';

    /**
     * Mana constructor.
     * @param array $conf
     * @param int $count
     * @param CompoundTag $nbt
     */
    public function __construct(array $conf, int $count, CompoundTag $nbt, $init = true)
    {
        $idInfo = explode(':', $conf['ID']);
        if ($count>1)$count = 1;
        parent::__construct($idInfo[0], $idInfo[1] ?? 0, $count);
        $this->setCompoundTag($nbt);
        $this->conf = $conf;
        $nbt = $this->getNamedTag();
        if (!isset($nbt['mana'][3])){
            $nbt['mana'][3] = new StringTag('',$nbt['mana'][3]??UUID::fromData(microtime(true)+mt_rand(-PHP_INT_MAX, PHP_INT_MAX)));
            $this->setNamedTag($nbt);
        }
        $this->ManaName = $this->getNamedTag()['mana'][1];
        $this->Mana = (int)$this->getNamedTag()['mana'][2];
        $this->Logo = $this->getNamedTag()['mana'][3];
        $this->binding=strtolower($nbt['mana'][0]);
        $this->setCustomName(ManaSystem::replace($conf['名字'], $this), true);
        $this->handMessage = $conf['手持提示'];
    }

    
    /**
     * @return int
     */
    public function getMaxMana(): int
    {
        return $this->conf['最大Mana']??0;
    }


    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->binding;
    }
    /**
     * 更新介绍
     */
    public function updateCustomName(){
        $this->setCustomName(ManaSystem::replace($this->conf['名字'], $this), true);
    }


    /**
     * @param int $mana
     */
    public function addMana(int $mana){
        $this->Mana += $mana;
        if ($this->Mana>$this->getMaxMana()){
            $this->Mana = $this->getMaxMana();
        }
        $tag = $this->getNamedTag();
        $tag['mana'][2] = new StringTag('', $this->Mana);
        $this->setNamedTag($tag);
        $this->updateCustomName();
    }

    /**
     * @param int $mana
     * @return bool
     */
    public function consumptionMana(int $mana):bool {
        if ($mana > $this->Mana)return false;
        $this->Mana -= $mana;
        $tag = $this->getNamedTag();
        $tag['mana'][2] = new StringTag('', $this->Mana);
        $this->setNamedTag($tag);
        $this->updateCustomName();
        return true;
    }

    /**
     * @return int
     */
    public function getMana(): int
    {
        return $this->Mana;
    }

    public function getLTName()
    {
        return $this->ManaName;
    }

    /**
     * @param Player $player
     * @return mixed|string
     */
    public function getHandMessage(Player $player):string
    {
        return ManaSystem::replace($this->handMessage, $this);
    }

    /**
     * @param Player $player
     * @param bool $playerCheck
     * @return bool
     */
    public function canUse(Player $player, $playerCheck = true): bool
    {
        if($this->binding==='*' or $this->binding===strtolower($player->getName()))return true;
        return false;
    }

    /**
     * @return mixed|string
     */
    public function getTypeName()
    {
        return '魔法';
    }

    /**
     * @return mixed
     */
    public function getInfo() {
        return $this->conf['介绍']??false;
    }

    /**
     * @return mixed
     */
    public function getUseMessage()
    {
        return $this->conf['使用提示']??false;
    }

    /**
     * @return bool
     */
    public function canBeActivated(): bool
    {
        return $this->conf['使用提示']??false!==false;
    }

    /**
     * @param Level $level
     * @param Player $player
     * @param Block $block
     * @param Block $target
     * @param $face
     * @param $fx
     * @param $fy
     * @param $fz
     * @return bool
     */
    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz)
    {
        if ($this->getUseMessage()!=false){
            $player->sendMessage('§e'.$this->getUseMessage());
        }
        return true;
    }

    /**
     * @param Item $item
     * @param bool $checkDamage
     * @param bool $checkCompound
     * @param bool $checkCount
     * @return bool
     */
    public function equals(Item $item, bool $checkDamage = true, bool $checkCompound = true, $checkCount = false): bool
    {
        if ($this->id === $item->getId() and ($checkDamage === false or $this->getDamage() === $item->getDamage()) and ($checkCount === false or $this->getCount() === $item->getCount())) {
            if ($checkCompound) {
                if ($item instanceof Mana and $item->getLTName() == $this->getLTName() and $this->getLogo()==$item->getLogo()) return true;
                return false;
            } else return false;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return $this->Logo;
    }
    /**
     * @param Player $player
     * @param int $index
     * @param BaseInventory $inventory
     * @return bool
     */
    public function onTick(Player $player, int $index, BaseInventory $inventory):bool
    {
        if (!$this->canUse($player) and $player->getServer()->getTick() - $this->lastDamage > 10){
            $this->lastDamage = $player->getServer()->getTick();
            $player->attack(1, new EntityDamageEvent($player, EntityDamageEvent::CAUSE_PUNISHMENT, 1, true));
        }
        return true;
    }

    /**
     * @param int $slot
     * @param string $tagName
     * @return CompoundTag
     */
    public function nbtSerialize(int $slot = -1, string $tagName = ""): CompoundTag
    {
        $nbt = parent::nbtSerialize($slot, $tagName);
        $nbt->tag->mana[2] = new StringTag('', $this->getMana());
        return $nbt;
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int
    {
        return 1;
    }

    public function canPutMana(): bool
    {
        return true;
    }
}
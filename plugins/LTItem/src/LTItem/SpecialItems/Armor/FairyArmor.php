<?php


namespace LTItem\SpecialItems\Armor;


use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

class FairyArmor extends \LTItem\SpecialItems\Armor implements ReduceMana
{

    private int $reduceMana = 5;

    public function __construct(array $conf, int $count, \pocketmine\nbt\tag\CompoundTag $nbt, $init = true)
    {
        parent::__construct($conf, $count, $nbt, $init);

        $nbt = $this->getNamedTag();
        if(!isset($nbt['armor'][15])){
            $nbt['armor'][15]=new StringTag('',$nbt['armor'][15]??$this->getConf('消魔减少'));//15
            $this->setNamedTag($nbt);
        }
        $this->reduceMana = $nbt['armor'][15];
    }

    /**
     * @return int|mixed
     */
    public function getReduceMana()
    {
        return $this->reduceMana;
    }

    /**
     * @param int $value
     * @return ReduceMana
     */
    public function setReduceMana(int $value): ReduceMana
    {
        $tag = $this->getNamedTag();
        $tag['armor'][18] = new StringTag('', $value);
        $this->reduceMana = $value;
        $this->setNamedTag($tag);
        return $this;
    }
    public function getReduce(): float
    {
        return $this->getReduceMana() / 100;
    }
    public function getHandMessage(Player $player):string {
        if($this->canUse($player)){
            return strtr($this->handMessage,['@h'=>$this->getArmorV(),'%'=>'%%%%','@x'=>$this->getHealth(),'@f'=>$this->getThorns(),'@s'=>$this->getMiss(),'@j'=>$this->getTough(),'@sp'=>$this->getSpeed(),'@k'=>$this->getControlReduce(),'@rm'=>$this->getReduceMana()]);
        }else return '你不是这个盔甲的拥有者！';
    }
    public function getMaxReduce(): int
    {
        return $this->getReduceMana();
    }
}
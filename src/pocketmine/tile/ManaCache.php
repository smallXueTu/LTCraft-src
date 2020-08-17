<?php


namespace pocketmine\tile;


use LTEntity\entity\Mana\ManaFloating;
use pocketmine\inventory\ChestInventory;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;

class ManaCache extends Tile
{
    const MAX_MANA = 1000000;
    /** @var int  */
    private $mana = 0;

    /**
     * Chest constructor.
     *
     * @param Level       $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
        if(!isset($this->namedtag->Mana) or !($this->namedtag->Mana instanceof IntTag)){
            $this->namedtag->Mana = new IntTag("Mana", 0);
        }
        $this->mana =  $this->namedtag['Mana'];
    }

    /**
     * @return int
     */
    public function getMana(): int
    {
        return $this->mana;
    }

    /**
     * @param int $mana
     * @return int
     */
    public function addMana(int $mana): int {
        $this->mana += $mana;
        if ($this->mana > self::MAX_MANA){
            $r = $this->mana - self::MAX_MANA;
            $this->mana = self::MAX_MANA;
            return $r;
        }else{
            return 0;
        }
    }

    /**
     * @param int $mana
     * @param Position $position 坐标是动画效果
     * @return bool
     */
    public function putMana(int $mana, Position $position = null) : bool {
        if ($this->mana < $mana){
            return false;
        }
        if ($position!=null){
            $nbt = new CompoundTag;
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $this->x+0.5),
                new DoubleTag("", $this->y+0.5),
                new DoubleTag("", $this->z+0.5)
            ]);
            $nbt->Rotation = new ListTag('Rotation', [
                new FloatTag('', 0),
                new FloatTag('', 0)
            ]);
            $entity = new ManaFloating($this->getLevel(), $nbt);
            $entity->setTarget($position->add(0.5, 0.5, 0.5));
            $entity->setStarting($this);
        }
        $this->mana -= $mana;
        return true;
    }

    public function saveNBT(){
        $this->namedtag->Mana = new IntTag("Mana", $this->mana);
    }
}
<?php


namespace pocketmine\inventory;


use LTItem\Mana\TravelingBackpack;
use LTMenu\Open;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;

class TravelingInventory extends ContainerInventory
{
    private int $index;
    private Player $owner;
    private UpdateBlockPacket $closePK;

    /**
     * MenuInventory constructor.
     *
     * @param Player $owner
     * @param null $contents
     * @param int $index
     * @param Position $pos
     */
    public function __construct(Player $owner, $contents, int $index, Position $pos){
        $this->index = $index;
        $this->owner = $owner;
        $owner->travel = $this;
        $items=[];
        if($contents !== null){
            if($contents instanceof ListTag){ //Saved data to be loaded into the inventory
                foreach($contents as $item){
                    $items[$item["Slot"]]=Item::nbtDeserialize($item);
                }
            }else{
                throw new \InvalidArgumentException("Expecting ListTag, received " . gettype($contents));
            }
        }
        parent::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::TRAVEL), $items);
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }
    /**
     * @return FakeBlockMenu
     */
    public function getHolder(){
        return $this->holder;
    }
    /**
     * @param Player $who
     */
    public function onOpen(Player $who){
        parent::onOpen($who);
        $pk = new BlockEventPacket();
        $pk->x = $this->getHolder()->getX();
        $pk->y = $this->getHolder()->getY();
        $pk->z = $this->getHolder()->getZ();
        $pk->case1 = 1;
        $pk->case2 = 2;
        if(($level = $this->getHolder()->getLevel()) instanceof Level){
            $level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
        }
    }
    public function save(){
        $item = $this->owner->getItemInHand();
        if ($item instanceof TravelingBackpack){
            $nbt = $item->getNamedTag();
            foreach ($this->getContents(true) as $index => $i){
                $nbt->items[$index] = $i->nbtSerialize($index);
            }
            $item->setNamedTag($nbt);
            $this->owner->getInventory()->setItemInHand($item);
        }
    }
    /**
     * @param Player $who
     */
    public function onClose(Player $who){
        $pk = new BlockEventPacket();
        $pk->x = $this->getHolder()->getX();
        $pk->y = $this->getHolder()->getY();
        $pk->z = $this->getHolder()->getZ();
        $pk->case1 = 1;
        $pk->case2 = 0;
        if(($level = $this->getHolder()->getLevel()) instanceof Level){
            $level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
        }

        parent::onClose($who);
        $who->dataPacket($this->getClosePK());
        $this->save();
        $this->owner->travel = null;
    }

    /**
     * @return UpdateBlockPacket
     */
    public function getClosePK(): UpdateBlockPacket
    {
        return $this->closePK;
    }

    /**
     * @param UpdateBlockPacket $closePK
     */
    public function setClosePK(UpdateBlockPacket $closePK): void
    {
        $this->closePK = $closePK;
    }
}
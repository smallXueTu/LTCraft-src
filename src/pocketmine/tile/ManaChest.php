<?php


namespace pocketmine\tile;


use LTItem\Main;
use LTItem\Mana\Mana;
use LTItem\Mana\ManaOrnaments;
use LTItem\Mana\ManaSystem;
use LTItem\SpecialItems\Material;
use pocketmine\block\ManaTransformation;
use pocketmine\inventory\ManaInventory;
use pocketmine\item\Bucket;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Server;

class ManaChest extends Chest
{
    /** @var array */
    public $upGrade = [
        '火红莲' => false,
        '石中姬' => false,
        '咀叶花' => false,
        '彼方兰' => false,
        ];//升级插件

    /** @var ManaInventory */
    protected $inventory;
    /**
     * @var int
     */
    protected $lastUpdateTick = 0;
    /**
     * @var int
     */
    protected $sleep = 0;
    /**
     * @var int
     */
    protected $lastCheck = 0;


    /**
     * Chest constructor.
     *
     * @param Level       $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt){
        Tile::__construct($level, $nbt);
        $this->inventory = new ManaInventory($this);
        if(!isset($this->namedtag->Items) or !($this->namedtag->Items instanceof ListTag)){
            $this->namedtag->Items = new ListTag("Items", []);
            $this->namedtag->Items->setTagType(NBT::TAG_Compound);
        }
        for($i = 0; $i < $this->getSize(); ++$i){
            $this->inventory->setItem($i, $this->getItem($i));
        }
        $this->scheduleUpdate();//加入更新队列
    }

    /**
     * 箱子被打开了 现在不必要
     */
    public function beOpened(){

    }

    /**
     * 每0.5s转换一个物品
     */
    public function onUpdate(){
        if ($this->sleep>0){
            $this->sleep--;
            return true;
        }
        if (Server::getInstance()->getTick() - $this->lastUpdateTick >= 10 and count($this->getInventory()->getViewers())<=0){
            $this->lastUpdateTick = Server::getInstance()->getTick();
            if (Server::getInstance()->getTick() - $this->lastCheck >= 60){//尽可能不浪费没必要的性能，每3秒检查一下升级
                $this->checkUpGrade();
                $this->lastCheck = Server::getInstance()->getTick();
            }
            /** @var array $item */
            $item = $this->findManaItem();
            if ($item==null)return false;
            return $this->conversionMana($item);
        }
        return true;
    }

    /**
     * 检查升级插件
     */
    public function checkUpGrade(){
        $this->upGrade = [
            '火红莲' => false,
            '石中姬' => false,
            '咀叶花' => false,
            '彼方兰' => false,
        ];
        $blocks = [];
        $blocks[] = $this->getLevel()->getBlock(new Vector3($this->x - 1, $this->y, $this->z));
        $blocks[] = $this->getLevel()->getBlock(new Vector3($this->x + 1, $this->y, $this->z));
        $blocks[] = $this->getLevel()->getBlock(new Vector3($this->x, $this->y, $this->z - 1));
        $blocks[] =  $this->getLevel()->getBlock(new Vector3($this->x, $this->y, $this->z + 1));
        foreach ($blocks as $block){
            if ($block instanceof \pocketmine\block\ItemFrame){
                /** @var ItemFrame $tile */
                $tile = $this->getLevel()->getTile($block);
                if ($tile instanceof ItemFrame and $tile->getItem() instanceof Material){
                    /** @var Material $item */
                    $item = $tile->getItem();
                    $name = $item->getLTName();
                    $this->upGrade[$name] = true;
                }
            }
        }
    }

    /**
     * @return \pocketmine\block\ManaTransformation
     */
    public function getBlock(){
        return $this->level->getBlock($this);
    }

    /**
     * @return array|null
     */
    public function findManaItem(){
        $contents = $this->getInventory()->getContents();
        foreach ($contents as $index => $item){
            if (($item instanceof Mana and $item->getMana()<$item->getMaxMana()) or in_array($item->getId(), [265, 264, 368, 20])){
                return [$index, $item];
            }
        }
        return null;
    }

    /**
     * 转换魔力
     * @param array $manaItem
     * @return bool
     */
    public function conversionMana(array $manaItem): bool {
        $contents = $this->getInventory()->getContents();
        foreach ($contents as $index => $item){
            if (($mana = ManaSystem::getItemMana($item, $this))>0) {
                $this->sleep = $mana;
				if($item instanceof Bucket){
					$this->getInventory()->setItem($index, Item::get(325));
				}else{
					$item->setCount($item->getCount()-1);
					$this->getInventory()->setItem($index, $item);
				}
                if (in_array($manaItem[1]->getId(), [265, 264, 368, 20])){
                    switch ($manaItem[1]->getId()){
                        case 265:
                            $manaItem[1] = Main::getInstance()->createMaterial("魔力钢锭");
                            break;
                        case 264:
                            $manaItem[1] = Main::getInstance()->createMaterial("魔力钻石");
                            break;
                        case 368:
                            $manaItem[1] = Main::getInstance()->createMaterial("魔力珍珠");
                            break;
                        case 20:
                            $manaItem[1] = Main::getInstance()->createMaterial("魔力玻璃");
                            break;
                    }
                    $titem = $this->getInventory()->getItem($manaItem[0]);
                    if ($titem->getCount()==1){
                       $this->getInventory()->setItem($manaItem[0],  clone $manaItem[1]);
                    }else{
                        $titem->setCount($titem->getCount()-1);
                        $this->getInventory()->setItem($manaItem[0],  $titem);
                        $this->getInventory()->addItem($manaItem[1]);
                    }
                }else{
                    $manaItem[1]->addMana($mana);
                    $this->getInventory()->setItem($manaItem[0],  clone $manaItem[1]);
                }
                return true;
            }
        }
        return false;
    }
}
<?php


namespace LTItem\Mana;


use LTItem\LTItem;
use pocketmine\block\Cobblestone;
use pocketmine\block\Leaves;
use pocketmine\block\Leaves2;
use pocketmine\block\Stone;
use pocketmine\item\Food;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\ManaChest;

class ManaSystem
{

    private static $manaItems = [];

    /**
     * @param array $conf
     * @param int $count
     * @param CompoundTag $nbt
     * @return Item
     */
    public static function getManaItem(string $name, array $conf, int $count, CompoundTag $nbt, $init = true)
    {
        if (isset(self::$manaItems[$name])){
            return new self::$manaItems[$name]($conf, $count, $nbt, $init);
        }
        return new BaseMana($conf, $count, $nbt, $init);
    }

    /**
     * 初始化魔法物品
     */
    public static function initMana()
    {
        self::$manaItems['泰拉粉碎者'] = TerraShatterer::class;
        self::$manaItems['泰拉断裂者'] = TarraFracture::class;
        self::$manaItems['魔力之戒'] = ManaOrnaments::class;
        self::$manaItems['高级魔力之戒'] = ManaOrnaments::class;
        self::$manaItems['大师魔力之戒'] = ManaOrnaments::class;
        self::$manaItems['永恒魔力之戒'] = EternalManaRing::class;
        self::$manaItems['托尔之戒'] = ManaOrnaments::class;
        self::$manaItems['禁忌之果'] = TabooFruit::class;
        self::$manaItems['奥丁之戒'] = ManaOrnaments::class;
        self::$manaItems['魔力法杖'] = ManaWand::class;
        self::$manaItems['魔力石板'] = BaseMana::class;
        self::$manaItems['命运之骰'] = FateDice::class;
        self::$manaItems['天翼族之冠'] = FamilyOfPhysical::class;
        self::$manaItems['天翼族之眼'] = ManaOrnaments::class;
        self::$manaItems['王者之剑'] = KingOfMana::class;
        self::$manaItems['泰拉钢刃'] = TerraSword::class;
        self::$manaItems['百中弓'] = HundredMiddleBow::class;
    }

    /**
     * 获取物品可转换多少魔力
     * @param Item $item
     * @param ManaChest $tile
     * @return int
     */
    public static function getItemMana(Item $item, ManaChest $tile) : int {
        if (!($item instanceof LTItem) and !$item->isUnableConvert()){
            $upGrade = $tile->upGrade;
            if ($item->getFuelTime()!=null){//可燃物 交给火红莲
                if ($upGrade['火红莲']){//有火红莲升级
                    return $item->getFuelTime()/10;
                }
            }elseif($item->getBlock() instanceof Leaves or $item->getBlock() instanceof Leaves2){//树叶 交给咀叶花
                if ($upGrade['咀叶花']) {//有咀叶花升级
                    return 15;
                }
            }elseif($item->getBlock() instanceof Cobblestone or $item->getBlock() instanceof Stone){//石头 交给石中姬
                if ($upGrade['石中姬']) {//有石中姬升级
                    return 15;
                }
            }elseif($item instanceof Food){//食物 交给彼方兰
                if ($upGrade['彼方兰']) {//有彼方兰升级
                    return $item->getFoodRestore() * 10;
                }
            }
        }
        return 0;
    }

    public static function replace(string $mess, Mana $ManaItem){
        if ($ManaItem instanceof TerraShatterer){
            return strtr($mess, ['&MaxMana&'=>$ManaItem->getMaxMana(),'&Mana&'=>$ManaItem->getMana(), '&owner&'=>$ManaItem->getOwner(),'&Level&'=>$ManaItem->getLevelString()]);
        }else{
            return strtr($mess, ['&MaxMana&'=>$ManaItem->getMaxMana(),'&Mana&'=>$ManaItem->getMana(),'&owner&'=>$ManaItem->getOwner()]);
        }
    }
}
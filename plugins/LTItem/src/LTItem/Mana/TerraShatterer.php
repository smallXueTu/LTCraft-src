<?php
/**
 * 泰拉粉碎者
 */
namespace LTItem\Mana;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class TerraShatterer extends ManaTool {
    public $onBreaking = false;
    const D = 0;//无任何效果
    const C = 1;//范围 3*1*3 如果佩戴托儿之戒 3*3*3
    const B = 2;//范围 5*1*5 如果佩戴托儿之戒 5*5*5
    const A = 3;//范围 7*1*7 如果佩戴托儿之戒 7*7*7
    const S = 4;//范围 9*1*9 如果佩戴托儿之戒 9*9*9
    const SS = 5;//范围 11*1*11 如果佩戴托儿之戒 11*11*11
    public static $scope = [
        0 => 1,
        1 => 3,
        2 => 5,
        3 => 7,
        4 => 8,
        5 => 11,
    ];

    /**
     * 获取稿子等级
     * @return mixed
     */
    public function getLevel()
    {
        if ($this->getMana()<=0)//D
            return self::D;
        elseif($this->getMana()>5000 and $this->getMana()<50000)//C
            return self::C;
        elseif($this->getMana()>=50000 and $this->getMana()<500000)//B
            return self::B;
        elseif($this->getMana()>=500000 and $this->getMana()<5000000)//A
            return self::A;
        elseif($this->getMana()>=5000000 and $this->getMana()<50000000)//S
            return self::S;
        elseif($this->getMana()>=50000000)//SS
            return self::SS;
        return self::D;
    }
    public function getLevelString(){
        switch ($this->getLevel()){
            case 0:
                return 'D';
                break;
            case 1:
                return 'C';
                break;
            case 2:
                return 'B';
                break;
            case 3:
                return 'A';
                break;
            case 4:
                return 'S';
                break;
            case 5:
                return 'SS';
                break;
        }
    }

    /**
     * @return int
     */
    public function isPickaxe(){
        return self::TIER_DIAMOND;
    }

    /**
     * @return int
     */
    public function getAttackDamage(){
        return 6;
    }

    /**
     * @return array
     */
    public function getScope(): int
    {
        return self::$scope[$this->getLevel()];
    }

    /**
     * 不在这里伤害实体了 不然是一大批的开销
     * @param Block|Entity $object
     * @param int $type
     * @return bool
     */
    public function useOn($object, $type = 1)
    {
        return true;
    }

    /**
     * 破坏批量方块
     * @param array $blocks
     * @param Player $player
     */
    public function removeBlockWithDrops(array $blocks, Player $player){
        $mana = count($blocks)*8;
        if ($player->getBuff()->getMana() < $mana){//Mana不足
            if ($player->getBuff()->getMana()>0){
                $player->getBuff()->consumptionMana($player->getBuff()->getMana());
                $this->setDamage($this->getMaxDurability()-1);
                $player->sendMessage('§a[警告]你身上没有足够的魔力来使用泰拉粉碎者！');
            }else{
                if ($this->getMaxDurability() - $this->getDamage() > 1){
                    $this->setDamage($this->getMaxDurability()-1);//设置耐久度为1 提醒玩家
                    $player->sendMessage('§a[警告]你身上没有足够的魔力来使用泰拉粉碎者！');
                }else{
                    $player->getInventory()->setItemInHand(Item::get(0));
                }
                //没耐久没魔力你挖什么啊
            }
        }
        if ($player->getLevel()->getName() == 'zy'){
            foreach ($blocks as $block) {
                $player->getLevel()->useBreakOn($block, $this, null, ['S', 'SS', 'A']?false:true);
            }
        }else{
            foreach ($blocks as $block) {
                $player->getLevel()->useBreakOn($block, $this, $player, in_array($this->getLevelString(), ['S', 'SS', 'A'])?false:true);
            }
        }
        $player->getBuff()->consumptionMana($mana);
    }
    /**
     * 玩家使用泰拉粉碎者破坏方块
     * @param Block $block
     * @param Entity $player
     * @return bool
     */
    public function entityUseOn(Block $block, Entity $player)
    {
        if ($this->onBreaking or $player->isSneaking())return true;
        if ($block instanceof Solid){
            $increase = false;
            if ($player->getBuff()->checkOrnamentsInstall("托尔之戒")!==false){
                $increase = true;
            }
            if ($increase){
                $pitch = $player->getPitch();
                $scope = $this->getScope();
                if ($scope == 1)return true;
                $this->onBreaking = true;
                $scopeB = floor($scope / 2);
                if ($pitch>=-40 and $pitch<=40) {//玩家看的前面 如果玩家是向前挖 那么破坏只向下延伸一格 这样有利于玩家前进
                    $blocks = $block->getLevel()->getCollisionBlocks(new AxisAlignedBB($block->getX() - $scopeB, $block->getY() - 1, $block->getZ() - $scopeB, $block->getX() + $scopeB + 1, $block->getY() + $scope -1, $block->getZ() + $scopeB + 1));
                    $this->removeBlockWithDrops($blocks, $player);
                }else{//正常挖掘
                    $blocks = $block->getLevel()->getCollisionBlocks(new AxisAlignedBB($block->getX() - $scopeB, $block->getY() - $scopeB, $block->getZ() - $scopeB, $block->getX() + $scopeB + 1, $block->getY() + $scopeB + 1, $block->getZ() + $scopeB + 1));
                    $this->removeBlockWithDrops($blocks, $player);
                }
            }else{
                $pitch = $player->getPitch();
                $scope = $this->getScope();
                if ($scope == 1)return true;
                $this->onBreaking = true;
                $scopeB = floor($scope / 2);
                if ($pitch>=-40 and $pitch<=40){//玩家看的前面
                    $x = abs($player->getX()-$block->getX());
                    $z = abs($player->getZ()-$block->getZ());
                    if ($x > $z){//向X挖
                        $blocks = $block->getLevel()->getCollisionBlocks(new AxisAlignedBB($block->getX(), $block->getY() - 1, $block->getZ() - $scopeB, $block->getX() + 1, $block->getY() + $scope-1, $block->getZ() + $scopeB + 1));
                    }elseif ($z > $x){//向Z挖
                        $blocks = $block->getLevel()->getCollisionBlocks(new AxisAlignedBB($block->getX() - $scopeB, $block->getY() - 1, $block->getZ() , $block->getX() + $scopeB + 1, $block->getY() + $scope-1, $block->getZ() + 1));
                    }else{
                        if(mt_rand(0,1)){//客户端并不提供玩家挖的那个面 不能判断出玩家想往哪里挖，随机一下吧..
                            $blocks = $block->getLevel()->getCollisionBlocks(new AxisAlignedBB($block->getX(), $block->getY() - 1, $block->getZ() - $scopeB, $block->getX() + 1, $block->getY() + $scope-1, $block->getZ() + $scopeB + 1));
                        }else{
                            $blocks = $block->getLevel()->getCollisionBlocks(new AxisAlignedBB($block->getX() - $scopeB, $block->getY() - 1, $block->getZ() , $block->getX() + $scopeB + 1, $block->getY() + $scope-1, $block->getZ() + 1));
                        }
                    }
                    $this->removeBlockWithDrops($blocks, $player);
                }else{//玩家看的上面或者下面
                    $blocks = $block->getLevel()->getCollisionBlocks(new AxisAlignedBB($block->getX() - $scopeB, $block->getY(), $block->getZ() - $scopeB, $block->getX() + $scopeB + 1, $block->getY() + 1, $block->getZ() + $scopeB + 1));
                    $this->removeBlockWithDrops($blocks, $player);
                }
            }
        }
        if ($this->getLevelString()=='SS'){
            $player->newProgress('六兆年零一夜物语', '用SS级的泰拉粉碎者挖穿世界', 'challenge');
        }
        $this->onBreaking = false;
        return parent::entityUseOn($block, $player); // TODO: Change the autogenerated stub
    }
}
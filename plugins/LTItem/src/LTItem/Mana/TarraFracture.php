<?php


namespace LTItem\Mana;



use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\block\Leaves;
use pocketmine\block\Leaves2;
use pocketmine\block\Wood;
use pocketmine\block\Wood2;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;

class TarraFracture extends ManaTool {
    public $onBreaking = false;


    /**
     * @return int
     */
    public function isAxe(){
        return self::TIER_GOLD;
    }

    /**
     * @return int
     */
    public function getAttackDamage(){
        return 6;
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
     * @param Player $player
     * @param Block $block
     * @param $count
     */
    public function cutDownTree(Player $player, Block $block, &$count, $contains){
        $blocks = [];
        $blocks[] = $block->level->getBlock($block->add(1, 0, 0));
        $blocks[] = $block->level->getBlock($block->add(-1, 0, 0));
        $blocks[] = $block->level->getBlock($block->add(0, 1, 0));
        $blocks[] = $block->level->getBlock($block->add(-0, -1, 0));
        $blocks[] = $block->level->getBlock($block->add(-0, 0, 1));
        $blocks[] = $block->level->getBlock($block->add(-0, 0, -1));
        foreach ($blocks as $b){
            /** @var Block $b */
            $hash = Level::blockHash($b->getX(), $b->getY(), $b->getZ());
            if (!isset($contains[$hash]) and ($b instanceof Wood or $b instanceof Wood2 or $b instanceof Leaves or $b instanceof Leaves2)){
                $contains[$hash] = true;
                $player->getLevel()->useBreakOn($b, $this);
                $count++;
                if ($count > 150){
                    return;
                }
                $this->cutDownTree($player, $b, $count, $contains);
            }
        }
    }

    /**
     * 玩家使用泰拉断裂者破坏方块
     * @param Block $block
     * @param Entity $player
     * @return bool
     */
    public function entityUseOn(Block $block, Entity $player){
        if ($player instanceof Player and ($block instanceof Wood or $block instanceof Wood2 or $block instanceof Leaves or $block instanceof Leaves2) and $this->onBreaking == false){
            if ($player->isSneaking())return true;
            $this->onBreaking = true;
            $count = 0;
            $this->cutDownTree($player, $block, $count, []);

            $mana = $count*8;
            if ($player->getBuff()->getMana() < $mana){//Mana不足
                if ($player->getBuff()->getMana()>0){
                    $player->getBuff()->consumptionMana($player->getBuff()->getMana());
                    $this->setDamage($this->getMaxDurability()-1);//设置耐久度为1 提醒玩家
                    $player->sendMessage('§a[警告]你身上没有足够的魔力来使用泰拉断裂者！');
                }else{
                    if ($this->getMaxDurability() - $this->getDamage() > 1){
                        $this->setDamage($this->getMaxDurability()-1);//设置耐久度为1 提醒玩家
                        $player->sendMessage('§a[警告]你身上没有足够的魔力来使用泰拉断裂者！');
                    }else{
                        $player->getInventory()->setItemInHand(Item::get(0));
                    }
                    //没耐久没魔力你挖什么啊
                }
            }
			$player->getBuff()->consumptionMana($mana);
            $this->onBreaking = false;
        }
        return true;
    }
}
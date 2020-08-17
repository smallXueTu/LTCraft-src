<?php


namespace LTItem\SpecialItems\Material;


use LTEntity\entity\Gaia\GaiaGuardians;
use LTEntity\entity\Gaia\Prompt;
use LTItem\SpecialItems\Material;
use pocketmine\block\Air;
use pocketmine\block\Beacon;
use pocketmine\block\Block;
use pocketmine\block\TaraSteelBlock;
use pocketmine\block\Solid;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class TerraSteelIngot extends Material
{
    /**
     * @param Level $level
     * @param Player $player
     * @param Block $block
     * @param Block $target
     * @param $face
     * @param $fx
     * @param $fy
     * @param $fz
     * @return bool|void
     */
    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz)
    {
        if ($player->isSneaking() and $target instanceof Beacon){
            //检查多方块结构
            //检查底座
            if (!$this->checkBase($target)){
                $player->sendMessage("§c无论你多么努力地尝试将泰拉钢锭推送入信标之中，但就是什么事都没发生。也许你用于仪式的信标底座摆错了。");
                return true;
            }
            //检查水晶底座
            if (!$this->checkCrystalBase($target)){
                $player->sendMessage("§c你尝试使用泰拉钢锭召唤盖亚，但是被水晶底座所阻止。也许你该采取些措施。");
                return true;
            }
            //检查背包
            if (!GaiaGuardians::checkInv($player)) {
                $player->sendMessage("§c你携带了一些违禁品，有关RPG的任何物品都不准携带至盖亚祭坛中。");
                return true;
            }
            //检查周围盖亚
            if (!$this->checkAroundGaia($target)) {
                return true;
            }
            //检查Buff
            if ($player->getBuff()->getEnable()) {
                $player->sendMessage("§c在盖亚祭坛中，你不能使用Buff效果，请输入/tw buff 关闭来关闭。");
                return true;
            }
            //最后的检查
            if (count($bs = $this->checkBlocks($target))!=0) {
                $entities = $target->getLevel()->getCollidingEntities(new AxisAlignedBB(
                    $target->x - 3,
                    $target->y - 3,
                    $target->z - 3,
                    $target->x + 3,
                    $target->y + 3,
                    $target->z + 3
                ));
                foreach ($entities as $entity){
                    if ($entity instanceof Prompt){
                        $player->sendMessage("§c只要你努力，信标就不会让你白白牺牲。相信这个仪式有些地方疏忽了。确认周围方块摆放的高度差也许是个好主意。");
                        return true;
                    }
                }
                if (count($bs) < 200){
                    $nbt = new CompoundTag;
                    $nbt->Pos = new ListTag("Pos", [
                        new DoubleTag("", $target->x+0.5),
                        new DoubleTag("", $target->y+0.5),
                        new DoubleTag("", $target->z+0.5)
                    ]);
                    $nbt->Rotation = new ListTag('Rotation', [
                        new FloatTag('', 0),
                        new FloatTag('', 0)
                    ]);
                    $entity = new Prompt($target->getLevel(), $nbt);
                    $entity->blocks = $bs;
                    $entity->basePos = $target;
                }
                $player->sendMessage("§c只要你努力，信标就不会让你白白牺牲。相信这个仪式有些地方疏忽了。确认周围方块摆放的高度差也许是个好主意。");
                return true;
            }
            //检查完成 产卵盖亚
            GaiaGuardians::spawn($target->asPosition(), $player);
            $this->count--;
        }
		return true;
    }

    /**
     * @param Position $position
     * @return bool
     */
    public function checkAroundGaia(Position $position): bool {
        foreach ($position->getLevel()->getEntities() as $entity){
            if ($entity instanceof GaiaGuardians and $entity->getBasePos()->distance($position)<30){
                return false;
            }
        }
        return true;
    }

    /**
     * @param Block $block
     * @return bool
     */
    protected function checkBase(Block $block){
        $tileX = $block->getFloorX();
        $tileY = $block->getFloorY();
        $tileZ = $block->getFloorZ();
        $queryY = $tileY - 1;
        for($queryX = $tileX - 1; $queryX <= $tileX + 1; $queryX++){
            for($queryZ = $tileZ - 1; $queryZ <= $tileZ + 1; $queryZ++){
                $testBlockId = $block->getLevel()->getBlockIdAt($queryX, $queryY, $queryZ);
                if( $testBlockId != Block::DIAMOND_BLOCK){
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param Block $block
     * @return bool
     */
    public function checkCrystalBase(Block $block):bool {
        $v3 = $block->asVector3()->add(0, -1, 0);
        $level = $block->getLevel();
        $blocks = [];
        $blocks[] = $level->getBlock($v3->add(4, 0, 4));
        $blocks[] = $level->getBlock($v3->add(4, 0, -4));
        $blocks[] = $level->getBlock($v3->add(-4, 0, -4));
        $blocks[] = $level->getBlock($v3->add(-4, 0, 4));
        foreach ($blocks as $block){
            if (!($block instanceof TaraSteelBlock)){
                return false;
            }
        }
        return true;
    }

    /**
     * @param Block $block
     * @return array
     */
    public function checkBlocks(Block $block):array {
        $trippedPositions = [];
        $range = 12;
        for($x = -$range; $x <= $range; $x++){
            for($z = -$range; $z <= $range; $z++) {
                if(hypot($x, $z) > 12)continue;
                for($y = -1; $y <= 12; $y++) {
                    if($x == 0 && $y == 0 && $z == 0)continue;//信标
                    $pos = $block->add($x, $y, $z);
                    $b = $block->getLevel()->getBlock($pos);
                    $allowBlockHere = $y < 0;
                    $isBlockHere = !($b instanceof Air);
                    if($allowBlockHere and $isBlockHere === false){//地板缺少方块
                        $trippedPositions[] = $b;
                    }
                    if($allowBlockHere === false and $isBlockHere){ //这里不应该有方块
                        $trippedPositions[] = $b;
                    }
                }
            }
        }
        return $trippedPositions;
    }
}
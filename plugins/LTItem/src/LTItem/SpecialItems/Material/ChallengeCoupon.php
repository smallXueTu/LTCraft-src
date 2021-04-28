<?php


namespace LTItem\SpecialItems\Material;


use LTEntity\entity\Gaia\GaiaGuardians;
use LTEntity\entity\Gaia\GaiaGuardiansIII;
use LTEntity\entity\Gaia\Prompt;
use pocketmine\block\Beacon;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class ChallengeCoupon extends TerraSteelIngot
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
                $player->sendMessage("§c无论你多么努力地尝试将挑战券推送入信标之中，但就是什么事都没发生。也许你用于仪式的信标底座摆错了。");
                return true;
            }
            //检查水晶底座
            if (!$this->checkCrystalBase($target)){
                $player->sendMessage("§c你尝试使用挑战券召唤盖亚，但是被水晶底座所阻止。也许你该采取些措施。");
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
                $player->sendMessage("§c在盖亚祭坛中，你不能使用Buff效果，请输入\"/tw buff 关闭\"来关闭。");
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
                    $entity = new Prompt($target->getLevel(), $nbt);
                    $entity->blocks = $bs;
                    $entity->basePos = $target;
                }
                $player->sendMessage("§c只要你努力，信标就不会让你白白牺牲。相信这个仪式有些地方疏忽了。确认周围方块摆放的高度差也许是个好主意。");
                return true;
            }
            //检查完成 产卵盖亚
            GaiaGuardiansIII::spawn($target->asPosition(), $player);
            $this->count--;
        }
        return true;
    }
}
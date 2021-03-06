<?php
namespace LTItem\Mana;

use LTEntity\entity\Mana\FairyGate;
use LTEntity\entity\Process\Prompt;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\LiveWood;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\ManaCache;
use pocketmine\tile\ManaFlower;
use pocketmine\utils\Utils;

class ManaWand extends BaseMana
{
    public function getHandMessage(Player $player):string
    {
        $addVector3 = new Vector3(-sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI), -sin($player->pitch / 180 * M_PI), cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI));
        $vector3 = $player->asVector3()->add(0, $player->getEyeHeight(), 0);
        for ($i = 0; $i<=5; $i++){
            $vector3 = $vector3->add($addVector3);
            //$targetBlock = $player->getLevel()->getCollisionBlocks(new AxisAlignedBB($vector3->getX()-0.1, $vector3->getY()-0.1, $vector3->getZ()-0.1, $vector3->getX()+0.1, $vector3->getY()+0.1, $vector3->getZ()+0.1), true)[0]??new Air();
            $targetBlock = $player->getLevel()->getBlock($vector3);
            if ($targetBlock!=null and !($targetBlock instanceof Air)){
                return "前方方块:".$targetBlock->getName();
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function canBeActivated(): bool
    {
        return true;
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
        $tile = $level->getTile($target);
        if ($tile instanceof ManaCache) {
            if ($player->isSneaking()){//说不定有用

            }else{
                if ($tile != null) {
                    $player->sendMessage('Mana:' .$tile->getMana());
                }
            }
        }elseif($tile instanceof ManaFlower) {
            $tile = $level->getTile($target);
            if ($tile != null) {
                $player->sendMessage('Mana:' .$tile->getMana());
            }
        }elseif ($target instanceof LiveWood and $target->getDamage() == 7){//精灵门

            foreach ($target->getLevel()->getEntities() as $entity){
                if ($entity instanceof FairyGate and $entity->coreBlock->equals($target)){
                    $entity->kill();
                    return true;
                }
            }
            $blocks = FairyGate::checkBlocks($target, $player);
            $nbt = Utils::spawnEntityBaseNBT($target->add(0, 1));
            if (count($blocks) <= 0){
                new FairyGate($target->getLevel(), $nbt, FairyGate::getTowards($target, FairyGate::X_EXTEND), $target);
            }else{
                $player->sendMessage("§c多方块结构检查失败，请根据闪动的方块来放置对应方块！");
                $entity = new Prompt($target->getLevel(), $nbt);
                $entity->blocks = $blocks;
            }
        }
        return true;
    }
}
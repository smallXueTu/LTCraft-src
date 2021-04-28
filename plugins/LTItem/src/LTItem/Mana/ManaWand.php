<?php


namespace LTItem\Mana;


use LTEntity\entity\Mana\FairyGate;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\LiveWood;
use pocketmine\block\ManaCache;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\Player;

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
        if ($target instanceof ManaCache) {
            /** @var \pocketmine\tile\ManaCache $tile */
            $tile = $level->getTile($target);
            if ($player->isSneaking()){//说不定有用

            }else{
                if ($tile != null) {
                    $player->sendMessage('Mana:' .$tile->getMana());
                }
            }
        }elseif ($target instanceof LiveWood and $target->getDamage() == 7){//精灵门
            if (FairyGate::checkBlocks($target)){
                $nbt = new CompoundTag;
                $nbt->Pos = new ListTag("Pos", [
                    new DoubleTag("", $target->x),
                    new DoubleTag("", $target->y + 1),
                    new DoubleTag("", $target->z)
                ]);
                $nbt->Rotation = new ListTag('Rotation', [
                    new FloatTag('', 0),
                    new FloatTag('', 0)
                ]);
                new FairyGate($target->getLevel(), $nbt);
            }else{
                $player->sendMessage("多方块结构检查失败！");
            }
        }
        return true;
    }
}
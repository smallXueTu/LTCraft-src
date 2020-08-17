<?php


namespace LTEntity\entity\Process;


use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\UpdateBlockPacket;

class Prompt extends Entity
{
    public $blocks = [];
    public $beenSent = [];
    public function onUpdate($currentTick)
    {
        if ($this->age > 20 * 30){
            $this->close();
            return false;
        }
        if ($this->age % 20 == 0) {
            if (count($this->beenSent) > 0) {
                foreach ($this->beenSent as $i => $block) {
                    $this->restoreBlock($block);
                }
                $this->beenSent = [];
            } elseif($this->age % 80 == 0 or $this->age == 0){
                foreach ($this->blocks as $i => $block) {
                    $b = $this->getLevel()->getBlock($block);
                    if ($b->getId() == $block->getId() and $b->getDamage() == $block->getDamage()) {
                        unset($this->blocks[$i]);
                        continue;
                    }
                    $this->sendBlock($block);
                    $this->beenSent[] = $block;
                }
                if (count($this->blocks) == 0) {
                    $this->close();
                    return false;
                }
            }
        }
        $this->age++;
        return true;
    }

    /**
     * @param array $blocks
     */
    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
    }

    /**
     * @param \pocketmine\Player $player
     * @param bool $send
     */
    public function despawnFrom(\pocketmine\Player $player, bool $send = true)
    {

    }

    /**
     * @param \pocketmine\Player $player
     */
    public function spawnTo(\pocketmine\Player $player)
    {

    }

    public function saveNBT()
    {

    }

    /**
     * 恢复方块包
     * @param Block $block
     */
    public function restoreBlock(Block $block){
        if ($this->getLevel()->getBlock($block)->getId()==$block->getId()){
            $this->sendAirBlock($block);
        }
        $b = $this->getLevel()->getBlock($block);
        $pk = new UpdateBlockPacket();
        $pk->x = (int)$block->x;
        $pk->z = (int)$block->z;
        $pk->y = (int)$block->y;
        $pk->blockId = $b->getId();
        $pk->blockData = $b->getDamage();
        $this->getLevel()->addChunkPacket($this->x >> 4, $this->z >>4, $pk);
    }

    /**
     * 发送方块包
     * @param Block $block
     */
    public function sendBlock(Block $block){
        if ($this->getLevel()->getBlock($block)->getId()==$block->getId()){
            $this->sendAirBlock($block);
        }
        $pk = new UpdateBlockPacket();
        $pk->x = (int)$block->x;
        $pk->z = (int)$block->z;
        $pk->y = (int)$block->y;
        $pk->blockId = $block->getId();
        $pk->blockData = $block->getDamage();
        $this->getLevel()->addChunkPacket($this->x >> 4, $this->z >>4, $pk);
    }

    /**
     * 发送方块包
     * @param Block $block
     */
    public function sendAirBlock(Block $block){
        $pk = new UpdateBlockPacket();
        $pk->x = (int)$block->x;
        $pk->z = (int)$block->z;
        $pk->y = (int)$block->y;
        $pk->blockId = 0;
        $pk->blockData = 0;
        $this->getLevel()->addChunkPacket($this->x >> 4, $this->z >>4, $pk);
    }
}
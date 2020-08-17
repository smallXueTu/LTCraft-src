<?php


namespace LTEntity\entity\Gaia;


use pocketmine\block\Air;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class Prompt extends Entity
{
    public $basePos = null;
    public $blocks = [];
    public function onUpdate($currentTick)
    {
        $this->age++;
        if ($this->age > 20 * 30){
            $this->close();
            return false;
        }
        if ($this->age % 5 == 0){
            $this->spawnBorderParticle();
            foreach ($this->blocks as $i=>$block){
                if ($this->basePos->y - $block->y == 1){
                    if ($this->getLevel()->getBlock($block) instanceof Solid)unset($this->blocks[$i]);
                }else{
                    if ($this->getLevel()->getBlock($block) instanceof Air)unset($this->blocks[$i]);
                }
                if ($block instanceof Air){
                    $this->getLevel()->addParticle(new RedstoneParticle($block->add(0.5, 0.5, 0.5)));
                }else{
                    $this->getLevel()->addParticle(new RedstoneParticle($block->add(0.5, 1.1, 0.5)));
                }
            }
            if (count($this->blocks) == 0){
                $this->close();
                return false;
            }
        }

        return true;
    }

    /**
     * @return Position
     */
    public function getBasePos()
    {
        return $this->basePos;
    }
    /**
     * 边界粒子
     */
    public function spawnBorderParticle(){
        $r = 12;
        $yy = $this->getBasePos()->getY()+5;
        for($i=1;$i<=360;$i++){
            $a=$this->getBasePos()->getX()+$r*cos($i*3.14/90) ;
            $b=$this->getBasePos()->getZ()+$r*sin($i*3.14/90) ;
            $this->getLevel()->addParticle(new DustParticle(new Vector3($a,$yy,$b),200,0,0));
        }
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
}
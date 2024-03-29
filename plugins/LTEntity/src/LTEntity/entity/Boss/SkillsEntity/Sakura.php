<?php

namespace LTEntity\entity\Boss\SkillsEntity;

use LTEntity\entity\BaseEntity;
use LTEntity\entity\Boss\Prisoners;
use LTEntity\Main;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class Sakura extends Entity
{
    private ?Entity $owner = null;
    private ?Position $basePos = null;
    private int $lastPhase = -1;
    public function __construct(Level $level, CompoundTag $nbt, Entity $owner)
    {
        parent::__construct($level, $nbt);
        $this->owner = $owner;
        $this->basePos = $this->asPosition();
        Main::getInstance()->skills['Sakura'][$this->getId()] = $this;
    }
    public function onUpdate($currentTick)
    {
		// $this->close();
        $this->age++;
        if ($this->age % 10 == 0)$this->spawnBorderParticle();
        if ($this->age > 7*10 + 30){
            $phase = (int)(($this->age - 100) % 720 / 80);
//            $phase = 0;
            if ($this->lastPhase < $phase){
                $this->lastPhase = $phase;
                $this->spawnStar($phase);
            }
        }
        if ($this->age > 320 or $this->owner->closed or $this->owner->distance($this) > 20){
            if ($this->owner instanceof Prisoners){
                $this->owner->releaseSkillIng = false;
            }
            $this->close();
            return false;
        }
        return true;
    }

    /**
     * 边界粒子
     */
    public function spawnBorderParticle(){
        $r = 15;
        if ($this->age < 7*10){
            $r = floor($this->age / 10) + 8;
        }
        $yy = $this->getBasePos()->getY();
        for($i=1;$i<=360;$i++){
            $a=$this->getBasePos()->getX()+$r*cos($i*3.14/180);
            $b=$this->getBasePos()->getZ()+$r*sin($i*3.14/180);
            $this->getLevel()->addParticle(new RedstoneParticle(new Vector3($a,$yy,$b)));
        }
    }
    public function close()
    {
        parent::close(); 

        unset(Main::getInstance()->skills['Sakura'][$this->getId()]);
    }

    /**
     * 绘画五角星
     * @param int $phase 阶段 一共三阶段
     */
    public function spawnStar(int $phase){
        $nbt = new CompoundTag;
        $start = $phase * (360/5/3);
        for ($i = 0; $i < 5 ; $i++){
            $ii = (($i * 360 / 5) + $start) % 360;
//            $iii = $ii;
            $startPosX = 12*cos($ii*3.14/180);
            $startPosZ = 12*sin($ii*3.14/180);
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("",  $this->getBasePos()->x+$startPosX),
                new DoubleTag("",  $this->getBasePos()->y),
                new DoubleTag("",  $this->getBasePos()->z+$startPosZ)
            ]);
            $line = new Line($this->getLevel(), $nbt);
            $line->setOwner($this->owner);
            $line->pid = 16;
//            echo '角度:'.$ii.'指向:';
//            $ii = ($ii + ($i+3) % 5 * 360 / 5) % 360;

            $ii = (((($i+3) % 5) * 360 / 5) + $start) % 360;
//            echo $ii.PHP_EOL;
            $endPosX = 12*cos($ii*3.14/180);
            $endPosZ = 12*sin($ii*3.14/180);
//            var_dump($this->getBasePos()->add($endPosX, 0 ,$endPosZ));
            $line->setTarget($this->getBasePos()->add($endPosX, 0 ,$endPosZ));
            $line->setHitCallBack(function (Entity $entity){
                if ($entity instanceof Player and $this->owner instanceof Player and $entity->getName()==$this->owner->getName())return;
                $ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_SAKURA, $entity->getMaxHealth() / 3, 0);
                $entity->attack($entity->getMaxHealth() / 3, $ev);
            });
//            $line->initF($i, $iii);
        }
    }

    /**
     * @return Position 这个技能的中心坐标
     */
    public function getBasePos(): Position{
        return $this->basePos;
    }


    /**
     * @param Player $player
     * @param bool $send
     */
    public function despawnFrom(Player $player, bool $send = true)
    {

    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player)
    {

    }

    public function saveNBT()
    {

    }

    /**
     * @return Entity|null
     */
    public function getOwner(): ?Entity
    {
        return $this->owner;
    }
    public function getDisplayName(){
        return $this->owner->getName();
    }
}
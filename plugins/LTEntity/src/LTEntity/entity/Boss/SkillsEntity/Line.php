<?php
namespace LTEntity\entity\Boss\SkillsEntity;

use pocketmine\entity\Entity;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\Position;
use pocketmine\Player;

class Line extends Entity
{
    /**
     * @var float
     */
    public $width = 0.3;
    /***
     * @var float
     */
    public $height = 0.3;
    /** @var Position  */
    public ?Position $target = null;
    /** @var Entity  */
    public ?Entity $owner = null;
    /** @var int  */
    public int $pid = 22;

    /** @var callable */
    protected $callable;

    /** @var array */
    protected $args;
    private array $vector3s = [];
    private array $boundingBoxs = [];

    /**
     * @param Entity $entity
     */
    public function setOwner(Entity $entity): void
    {
        $this->owner = $entity;
    }
    public function initF($id, $angle){
        new \LTCraft\FloatingText($this->add(0, -1, 0), $angle."角度");
        new \LTCraft\FloatingText($this, $id."起点");
        new \LTCraft\FloatingText($this->target->add(0, 1, 0), $id."终点");
    }
    /**
     * @param Position $target
     */
    public function setTarget(Position $target): void
    {
        $this->target = $target;
    }
    public function onUpdate($currentTick)
    {
        if($this->closed){
            return false;
        }
        if ($this->target == null){
            $this->close();
            return false;
        }
        $this->age++;
        $x=$this->target->x - $this->x;
        $y=$this->target->y - $this->y;
        $z=$this->target->z - $this->z;
        $diff = abs($x) + abs($z);
        if($diff==0){
            $this->close();
            return false;
        }else{
            $Mx = 0.25 * ($x / $diff);
            $My = 0.25 * ($y / $diff);
            $Mz = 0.25 * ($z / $diff);
            $this->move($Mx, $My, $Mz);
            $this->vector3s[] = $this->asVector3();
            $radius = $this->width / 2;
            $this->boundingBox->setBounds($this->x - $radius, $this->y, $this->z - $radius, $this->x + $radius, $this->y + $this->height, $this->z + $radius);
            $this->boundingBoxs[] = clone $this->getBoundingBox();
        }
        if ($this->age % 4 == 0){
            $this->spawnParticle();
        }
        if ($this->age % 2 == 0){
            $this->checkPlayers();
        }
        if ($this->target->distance($this) < 0.2 or $this->age > 300 or $this->owner->closed){
            $this->close();
            return false;
        }
        return true;
    }
    public function spawnParticle(){
        foreach ($this->vector3s as $vector3){
            $this->getLevel()->addParticle(new GenericParticle($vector3, $this->pid));
        }
    }
    public function checkPlayers(){
        foreach ($this->boundingBoxs as $boundingBox) {
            foreach ($this->getLevel()->getCollidingEntities($boundingBox) as $entity) {
                if ($entity instanceof Player) {
                    call_user_func_array($this->callable, array_merge([$entity], $this->args));
                }
            }
        }
    }
    public function move($dx, $dy, $dz)
    {
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
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
     * @param callable $callable
     * @param array $args
     */
    public function setHitCallBack(callable $callable, $args = []){
        $this->callable = $callable;
        $this->args = $args;
    }
}
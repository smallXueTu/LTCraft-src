<?php


namespace LTEntity\entity\Boss\SkillsEntity;


use pocketmine\entity\Entity;
use pocketmine\level\Explosion;
use pocketmine\level\particle\EntityFlameParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;

class TearMissile extends Entity
{
    private ?Entity $owner = null;

    /**
     * @param Entity|null $owner
     */
    public function setOwner(?Entity $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return Entity|null
     */
    public function getOwner(): ?Entity
    {
        return $this->owner;
    }
    /**
     * @param $currentTick
     * @return bool|void
     */
    public function onUpdate($currentTick)
    {
        $this->age++;
        $list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);
        if (count($list) > 0 or $this->isCollided or $this->age > 20 * 5){
            $this->explosion();
            $this->close();
            return false;
        }
        $this->move($this->motionX, $this->motionY, $this->motionZ);
        $this->rendering();
        return true;
    }

    /**
     * 渲染导弹
     */
    public function rendering(){
        $this->level->addParticle(new FlameParticle($this));
    }

    /**
     * 爆炸
     */
    public function explosion(){
        $ex = new Explosion($this, 3, $this->owner);
        $ex->booomB(20, $this->owner, 2, true);
    }
    public function spawnTo(Player $player)
    {

    }
    public function spawnToAll()
    {

    }
    public function saveNBT()
    {

    }
}
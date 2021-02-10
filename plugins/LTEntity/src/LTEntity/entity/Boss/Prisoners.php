<?php
namespace LTEntity\entity\Boss;

use LTEntity\entity\Boss\SkillsEntity\Sakura;
use LTEntity\entity\Boss\SkillsEntity\SpaceTear;
use LTEntity\entity\monster\walking\EMods\ANPC;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class Prisoners extends ANPC
{
    const NETWORK_ID = -1;
    public int $lastReleaseSkill1 = 0;
    public int $lastReleaseSkill2 = 0;
    public int $lastReleaseSkill3 = 0;
    public int $nullTargetTick = 0;
    public $releaseSkillIng = false;
    public function onUpdate($currentTick)
    {
        $return = parent::onUpdate($currentTick);
        if ($this->getHealth() <= $this->getMaxHealth()*0.6 and !$this->releaseSkillIng){
            if ($this->lastReleaseSkill1 < time() and $this->age > 20*5){//一技能 背刺
                if ($this->baseTarget instanceof Player){
                    $this->lastReleaseSkill1 = time() + 15 + date("s")/2;
                    $x = $this->baseTarget->getX() - $this->getX();
                    $z = $this->baseTarget->getZ() - $this->getZ();
                    $x += $x>0?5:-5;
                    $z += $z>0?5:-5;
                    $this->getLevel()->addSound(new EndermanTeleportSound($this));
                    if (abs($x) < 15 and abs($z) < 15){
                        for ($i = 1; $i <= $this->add($x, 0, $z)->distanceNoY($this) - 4; $i++){
                            for ($j = 0; $j < 18; $j++){
                                for ($n = 0; $n <= 10; $n++){
                                    $this->getLevel()->addParticle(new PortalParticle($this->add(($x * ($i/abs($x))) + mt_rand(-5, 5) / 10, $j / 10, ($z * ($i/abs($z))) + mt_rand(-5, 5) / 10)));
                                }
                            }
                        }
                    }
                    $this->teleport($this->add($x, 0, $z));
                    $this->getLevel()->addSound(new EndermanTeleportSound($this));
                }
            }
        }
        if ($this->getHealth() <= $this->getMaxHealth()*0.3 and !$this->releaseSkillIng){
            if ($this->lastReleaseSkill2 < time()){
                $this->lastReleaseSkill2 = time() + 45 + date("s")/2;
                $this->releaseSkillIng = 2;
                $this->enConfig['怪物模式'] = 0;
                $nbt = new CompoundTag;
                $pos = new Position($this->enConfig['x'], $this->enConfig['y'], $this->enConfig['z'], $this->level);
                $this->teleport($pos);
                $nbt->Pos = new ListTag("Pos", [
                    new DoubleTag("", $pos->x),
                    new DoubleTag("", $pos->y+0.7),
                    new DoubleTag("", $pos->z)
                ]);
                $nbt->Rotation = new ListTag('Rotation', [
                    new FloatTag('', 0),
                    new FloatTag('', 0)
                ]);
                new SpaceTear($this->level, $nbt, $this);
            }
        }
        if ($this->lastReleaseSkill3 < time() and !$this->releaseSkillIng){
            $this->lastReleaseSkill3 = time() + 45 + date("s");
            $this->releaseSkillIng = 3;
            $nbt = new CompoundTag;
            $pos = new Position($this->enConfig['x'], $this->enConfig['y'], $this->enConfig['z'], $this->level);
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $pos->x),
                new DoubleTag("", $pos->y+1),
                new DoubleTag("", $pos->z)
            ]);
            $nbt->Rotation = new ListTag('Rotation', [
                new FloatTag('', 0),
                new FloatTag('', 0)
            ]);
            new Sakura($this->level, $nbt, $this);
        }
        if (!($this->baseTarget instanceof Player)){
            $this->nullTargetTick++;
        }
        if ($this->nullTargetTick > 20 * 3 and $this->age % 10 == 0 and !($this->baseTarget instanceof Player) and $this->getHealth() < $this->getMaxHealth()){
            $this->heal($this->getMaxHealth() * 0.05, new EntityRegainHealthEvent($this, $this->getMaxHealth() * 0.05, EntityRegainHealthEvent::CAUSE_MAGIC));
        }
        return $return;
    }
    public function updateMove()
    {
        return parent::updateMove();
    }

    public function attack($damage, EntityDamageEvent $source)
    {
        $damage = 3000;
        if ($source->getDamage() > 3000){
            $damage += ($source->getDamage() - 3000) / 2;
        }
        $source->setDamage($damage);
        return parent::attack($damage, $source);
    }

    /**
     * 获取攻击伤害
     * @return float|int
     */
    public function getDamage()
    {
        if ($this->baseTarget instanceof Player){
            if (abs($this->baseTarget->getYaw() - $this->getYaw()) < 90){
                return $this->baseTarget->getMaxHealth() * 0.2;
            }
            return $this->baseTarget->getMaxHealth() * 0.2;
        }
        return parent::getDamage();
    }
    public function attackEntity(Entity $entity){
        parent::attackEntity($entity);
        if ($this->attackDelay == 0 and $entity instanceof Creature){//发起了攻击
            $entity->setInjured(10);
        }
    }
}
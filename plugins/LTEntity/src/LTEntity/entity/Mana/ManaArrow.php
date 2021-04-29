<?php


namespace LTEntity\entity\Mana;


use LTEntity\entity\BaseEntity;
use LTEntity\entity\monster\flying\AEnderDragon;
use pocketmine\entity\Arrow;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\MobSpellParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * 魔法箭头
 * 这个箭头由百中弓射出
 * 不计算重力 直线前进 穿透实体
 * 对玩家造成 34%最大生命值的伤害
 * 对怪物造成1000基础伤害
 * Class ManaArrow
 * @package LTEntity\entity\Mana
 */
class ManaArrow extends Arrow
{
    protected int $maxAge = 60;
    protected $gravity = 0;
    public function onUpdate($currentTick)
    {
        if($this->closed){
            return false;
        }
        $tickDiff = $currentTick - $this->lastUpdate;
        if($tickDiff <= 0 and !$this->justCreated){
            return true;
        }
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);
        if($this->isAlive()){
            $list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);
            $entities = [];
            foreach($list as $entity){
                if($entity === $this->shootingEntity and $this->ticksLived < 5)continue;
                $entities[] = $entity;
            }

            if(count($entities) > 0){
                foreach ($entities as $entity){
                    $motion = sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2);
                    if($this->calculate)
                        $damage = ceil($motion * $this->getDamage($entity));
                    else
                        $damage=$this->getDamage($entity);
                    if($this->shootingEntity === null){
                        $ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_PROJECTILE, $damage, $this->zs);
                    }else{
                        $ev = new EntityDamageByChildEntityEvent($this->shootingEntity, $this, $entity, EntityDamageEvent::CAUSE_PROJECTILE, $damage, $this->zs);
                    }
                    if($entity->attack($ev->getFinalDamage(), $ev) === true){
                        $ev->useArmors();
                    }
                    if($this->fireTicks > 0){
                        $ev = new EntityCombustByEntityEvent($this, $entity, 5);
                        $this->server->getPluginManager()->callEvent($ev);
                        if(!$ev->isCancelled()){
                            $entity->setOnFire($ev->getDuration());
                        }
                    }
                }
            }
            $this->move($this->motionX, $this->motionY, $this->motionZ);
            $this->updateMovement();
        }
        if($this->isCritical){
            $this->level->addParticle(new CriticalParticle($this->add(
                $this->width / 2 + mt_rand(-100, 100) / 500,
                $this->height / 2 + mt_rand(-100, 100) / 500,
                $this->width / 2 + mt_rand(-100, 100) / 500)));
        }
        if($this->age > $this->maxAge){
            $this->kill();
        }
        return true;
    }

    public function getDamage(Entity $entity = null): int
    {
        if ($entity instanceof Player){
            return $entity->getMaxHealth() * 0.34;
        }elseif($entity instanceof BaseEntity){
            return 1000;
        }else{
            return 6;
        }
    }
    public function move($dx, $dy, $dz) : bool{
        $this->boundingBox->offset($dx, $dy, $dz);
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
        $this->checkChunks();
        return true;
    }
    public function saveNBT()
    {

    }
}
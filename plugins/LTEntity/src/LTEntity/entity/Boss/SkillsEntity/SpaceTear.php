<?php


namespace LTEntity\entity\Boss\SkillsEntity;


use LTEntity\entity\Boss\Prisoners;
use LTEntity\Main;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class SpaceTear extends Entity
{

    private ?Entity $owner = null;
    private ?Position $basePos = null;
    private array $skillEntities = [];
    private int $skillEntityYaw = 0;
    public function __construct(Level $level, CompoundTag $nbt, Entity $owner)
    {
        parent::__construct($level, $nbt);
        $this->skillEntityYaw = $owner->getYaw();
        $this->owner = $owner;
        $owner->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE, true);//禁止移动
        $this->basePos = $this->asPosition();
    }
    public function onUpdate($currentTick)
    {
        $this->age++;
        $this->owner->attackDelay = 0;
        if ($this->owner->getY() > $this->getBasePos()->add(0, 5, 0)->getY()){
            $this->close();
            return false;
        }
        if ($this->getBasePos()->add(0, 5, 0)->y - $this->owner->y > 1){
            if ($this->owner instanceof Player){
                $this->owner->sendPosition($this->owner->add(0, 0.5, 0), $this->owner->getYaw(), $this->owner->getPitch());
                $this->owner->newPosition = $this->owner->add(0, 0.5, 0);
            }else{
                $this->owner->move(0, 0.5, 0);
                $this->updateMovement();
            }
        }else{
            if (count($this->skillEntities) <= 0){
                //生成技能实体
                $leftYaw = $this->skillEntityYaw < 90? 360 - (90 - $this->skillEntityYaw) : $this->skillEntityYaw - 90;
                $rightYaw = $this->skillEntityYaw > 269? $this->skillEntityYaw + 90 - 360 : $this->skillEntityYaw + 90;
//                var_dump($leftYaw);
//                var_dump($rightYaw);
                //先生成最上方三个
                //中间 start
                $high = 2;
                $nbt = new CompoundTag;
                $verticals = [4, 2, 0];
                for ($i = 0; $i <=4; $i++){
                    $vertical = $verticals[$i % 3];
                    $y = $this->getBasePos()->add(0, 5, 0)->getY() - $vertical / 2;
//                    $leftQuadrant = 1;
//                    $rightQuadrant = 1;
                    if ($i <= 1){
                        $X = -sin($leftYaw/180*M_PI) * $vertical;//计算X
                        $Z = cos($leftYaw/180*M_PI) * $vertical;//计算Z
                    }elseif($i > 2){
                        $X = -sin($rightYaw/180*M_PI) * $vertical;//计算X
                        $Z = cos($rightYaw/180*M_PI) * $vertical;//计算Z
                    }else{
                        $X = 0;
                        $Z = 0;
                    }
                    for($j = 0; $j <=2; $j++){
                        /*
                        if ($i <= 1){
                            if ($leftYaw % 90 == 0){//在轴上 这样我们可以只考虑一个轴
                                /*
                                 * 0:Z+,90:X-,180:Z-,270:X+

                                switch ($leftYaw){
                                    case 0:
                                    case 180://不考虑Z
                                        if ($leftYaw > $rightYaw){
                                            $X -= ($high / 4 * $j);
                                        }else{
                                            $X += ($high / 4 * $j);
                                        }
                                    break;
                                    default://只有90和270了 不考虑X
                                        if ($leftYaw > $rightYaw){
                                            $X -= ($high / 4 * $j);
                                        }else{
                                            $X += ($high / 4 * $j);
                                        }
                                    break;
                                }
                            }else{
                                //求出角度所处象限
                                $leftQuadrant = $leftYaw > 180?($leftYaw>90?2:1):($leftYaw>270?4:3);
                            }
                        }elseif($i > 2){
                            if ($rightYaw % 90 == 0){//在轴上 这样我们可以只考虑一个轴
                                /*
                                 * 0:Z+,90:X-,180:Z-,270:X+

                                switch ($rightYaw){
                                    case 0:
                                    case 180://不考虑Z
                                        if ($leftYaw > $rightYaw){
                                            $Z -= ($high / 4 * $j);
                                        }else{
                                            $Z += ($high / 4 * $j);
                                        }
                                        break;
                                    default://只有90和270了 不考虑X
                                        if ($leftYaw > $rightYaw){
                                            $X -= ($high / 4 * $j);
                                        }else{
                                            $X += ($high / 4 * $j);
                                        }
                                        break;
                                }
                            }else{
                                //求出角度所处象限
                                $leftQuadrant = $rightYaw > 180?($rightYaw>90?2:1):($rightYaw>270?4:3);
                            }
                        }
                        */
                        $nbt->Pos = new ListTag("Pos", [
                            new DoubleTag("",  $this->getBasePos()->add($X, 5, $Z)->x),
                            new DoubleTag("",  $y + $high * $j),
                            new DoubleTag("",  $this->getBasePos()->add($X, 5, $Z)->z)
                        ]);
                        $tear = new Tear($this->getLevel(), $nbt, $this->owner);
                        $tear->spawnToAll();
                        $this->skillEntities[] = $tear;
                    }
                }
            }
        }
        if ($this->age > 20*20 or $this->owner->closed){
            $this->close();
            return false;
        }
        return true;
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
    public function getDisplayName(){
        return $this->owner->getName();
    }
    public function close()
    {
        parent::close(); 
        $this->owner->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE, false);//禁止移动
        foreach ($this->skillEntities as $entity){
            /** @var $entity Tear */
            $entity->close();
        }
        if ($this->owner instanceof Prisoners){
            $this->owner->releaseSkillIng = false;
            $this->owner->enConfig['怪物模式'] = 1;
        }
    }
}
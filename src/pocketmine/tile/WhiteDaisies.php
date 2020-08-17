<?php


namespace pocketmine\tile;


use pocketmine\block\Block;
use pocketmine\block\LiveWood;
use pocketmine\block\LivingStones;
use pocketmine\block\Stone;
use pocketmine\block\Wood;
use pocketmine\block\Wood2;
use pocketmine\level\Level;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\nbt\tag\CompoundTag;

class WhiteDaisies extends Tile
{
    private $age = 0;
    private $time = [
        0 => 0,
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
        7 => 0,
        8 => 0,
    ];
    private $lastID = [
        0 => 0,
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
        7 => 0,
        8 => 0,
    ];
    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        $this->scheduleUpdate();//加入更新队列
    }
    public function onUpdate()
    {
        $this->age++;
        if ($this->age % 20==0){
            $blocks = [];
            $blocks[] = $this->asVector3()->add(1, 0, 0);//+x
            $blocks[] = $this->asVector3()->add(-1, 0, 0);//-x
            $blocks[] = $this->asVector3()->add(0, 0, 1);//+z
            $blocks[] = $this->asVector3()->add(0, 0, -1);//-z
            $blocks[] = $this->asVector3()->add(1, 0, -1);//+x -z
            $blocks[] = $this->asVector3()->add(1, 0, 1);//+x +z
            $blocks[] = $this->asVector3()->add(-1, 0, -1);//-x -z
            $blocks[] = $this->asVector3()->add(-1, 0, 1);//-x +z
            $i = 0;
            foreach ($blocks as $block){
                /** @var Block $block */
                $b = $this->getLevel()->getBlock($block);
                if ($b->getId()!=$this->lastID[$i]){
                    $this->time[$i] = 0;
                }
                $this->lastID[$i] = $b->getId();
                if (!($b instanceof Wood) and !($b instanceof Wood2) and (!($b instanceof Stone) or $b->getDamage() != 0)){
                    $i++;
                    continue;
                }
                $this->getLevel()->addParticle(new HappyVillagerParticle($block->add(0.5 , 1, 0.5)));
                if (++$this->time[$i] == 60){
                    switch (true){
                        case ($b instanceof Wood) or ($b instanceof Wood2):
                            $b->drop = false;
                            $this->getLevel()->useBreakOn($b);
                            $this->getLevel()->setBlock($block, new LiveWood(0));
                            break;
                        case ($b instanceof Stone) and $b->getDamage() == 0:
                            $b->drop = false;
                            $this->getLevel()->useBreakOn($b);
                            $this->getLevel()->setBlock($block, new LivingStones(0));
                            break;
                    }
                }
                $i++;
            }
        }
        return true;
    }
}
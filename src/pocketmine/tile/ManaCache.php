<?php


namespace pocketmine\tile;


use LTEntity\entity\Mana\ManaFloating;
use LTItem\Mana\Mana;
use LTPet\Main;
use pocketmine\entity\Entity;
use pocketmine\inventory\ChestInventory;
use pocketmine\level\Level;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\Server;

class ManaCache extends Tile
{
    const MAX_MANA = 1000000;
    /** @var int  */
    private $mana = 0;
    private bool $spawnParticle = false;
    public $progress = 0;//MAX 360
    /**
     * @var int
     */
    protected $lastUpdateTick = 0;

    /**
     * Chest constructor.
     *
     * @param Level       $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
        if(!isset($this->namedtag->Mana) or !($this->namedtag->Mana instanceof IntTag)){
            $this->namedtag->Mana = new IntTag("Mana", 0);
        }
        $this->mana =  $this->namedtag['Mana'];
        $this->scheduleUpdate();
    }

    /**
     * 向上方容器输入魔力
     * @return bool
     */
    public function onUpdate()
    {
        if (Server::getInstance()->getTick() - $this->lastUpdateTick >= 10){
            $this->spawnParticle = false;
            $this->lastUpdateTick = Server::getInstance()->getTick();
            $arr = [[0, 1, 0], [1, 0, 0], [-1, 0, 0], [0, 0, 1], [0, 0, -1]];
            foreach ($arr as $a){//向四周抽取Mana 向上方输出Mana
                $tile = $this->getLevel()->getTile($this->add($a[0], $a[1], $a[2]));
                if ($tile !== null and $tile instanceof Chest){
                    if (count($tile->getInventory()->getViewers()) >= 1)continue;
                    if ($a[1] == 1){
                        foreach ($tile->getInventory()->getContents() as $i => $item){
                            /** @var $item Mana */
                            if ($item instanceof Mana){
                                if ($this->getMana() < $item->getMaxMana()){
                                    $enter = min($item->getMaxMana() - $item->getMana(), 100);                                    if ($enter == 0)continue;
                                    $this->spawnParticle = true;
                                    if ($this->putMana($enter)){
                                        $item->addMana($enter);
                                        $tile->getInventory()->setItem($i, $item);
                                        break;
                                    }
                                }
                            }
                        }
                    }else{
                        foreach ($tile->getInventory()->getContents() as $i => $item){
                            /** @var $item Mana */
                            if ($item instanceof Mana and $item->canPutMana()){
                                if ($item->getMana() > 0){
                                    $enter = min($item->getMana(), 100);
                                    if ($enter == 0)continue;
                                    if ($item->consumptionMana($enter)){
                                        $this->addMana($enter);
                                        $tile->getInventory()->setItem($i, $item);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }elseif ($tile != null and $tile instanceof Sign){
                    /** @var Sign $tile */
                    $line1 = $tile->getText()[0];
                    if (strtolower(substr(Main::getCleanName($line1), 0, 4)) == 'mana'){
                        $tile->setText("§eMana:", "§d" . self::MAX_MANA . "/" . $this->getMana());
                    }
                }
            }
        }
        if ($this->spawnParticle)$this->spawnParticle();
        $this->progress++;
        if ($this->progress >= 360)$this->progress = 0;
        return true;
    }

    public function spawnParticle(){
        $r = 0.8;
        $x=$this->x + 0.5;
        $y=$this->y + 1.4;
        $z=$this->z + 0.5;
        for ($ii = 0; $ii < 4; $ii++){
            $iii = $this->progress + $ii * 45;
            if ($iii >= 360)$iii -= 360;
            $a = $x+$r*cos($iii*3.14/90);
            $b = $z+$r*sin($iii*3.14/90);
            $this->getLevel()->addParticle(new DustParticle(new Vector3($a,$y,$b), 0, 220, 0));
        }
    }
    /**
     * @return int
     */
    public function getMana(): int
    {
        return $this->mana;
    }

    /**
     * @param int $mana
     * @return int
     */
    public function addMana(int $mana): int {
        $this->mana += $mana;
        if ($this->mana > self::MAX_MANA){
            $r = $this->mana - self::MAX_MANA;
            $this->mana = self::MAX_MANA;
            return $r;
        }else{
            return 0;
        }
    }

    /**
     * @param int $mana
     * @param Position|null $position 坐标是动画效果
     * @return int
     */
    public function enterMana(int $mana, Position $position = null): int {
        if ($position!=null){
            $nbt = new CompoundTag;
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $position->x+0.5),
                new DoubleTag("", $position->y+0.5),
                new DoubleTag("", $position->z+0.5)
            ]);
            $entity = new ManaFloating($this->getLevel(), $nbt);
            $entity->setTarget($this->add(0.5, 0.5, 0.5));
            $entity->setStarting($position);
        }
        return $this->addMana($mana);
    }

    /**
     * @param int $mana
     * @param Position|null $position 坐标是动画效果
     * @return bool
     */
    public function putMana(int $mana, Position $position = null) : bool {
        if ($this->mana < $mana){
            return false;
        }
        if ($position!=null){
            $nbt = new CompoundTag;
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $this->x+0.5),
                new DoubleTag("", $this->y+0.5),
                new DoubleTag("", $this->z+0.5)
            ]);
            $entity = new ManaFloating($this->getLevel(), $nbt);
            $entity->setTarget($position->add(0.5, 0.5, 0.5));
            $entity->setStarting($this);
        }
        $this->mana -= $mana;
        return true;
    }

    public function saveNBT(){
        $this->namedtag->Mana = new IntTag("Mana", $this->mana);
    }
}
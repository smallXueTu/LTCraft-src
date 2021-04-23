<?php


namespace LTEntity\entity\Gaia;

use LTEntity\entity\Gaia\SkillEntity\Landmine;
use LTEntity\entity\Gaia\SkillEntity\DieArea;
use LTEntity\Main;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\BaseOrnaments;
use LTItem\SpecialItems\Weapon;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\UUID;

class GaiaGuardiansIII extends Creature
{
    /**
     * @var float
     */
    public float $width = 0.6;
    /***
     * @var float
     */
    public float $height = 1.8;
    /**
     * @var float
     */
    public float $eyeHeight = 1.62;
    /** @var int */
    private int $playerCount = 0;
    /** @var ?UUID */
    private ?UUID $uid = null;
    /** @var ?Position  */
    private ?Position $basePos = null;
    /** @var string  */
    private string $skinData = '';
    /** @var array  */
    private array $crystas = [];
    /** @var array  */
    private $lastMove = 0;
    /** @var int  */
    private int $lastUpdateSee = 0;
    /** @var ?Position  */
    private ?Position $baseTarget = null;
    /** @var array  */
    private array $landmines = [];
    /** @var int  */
    public int $onSky = 0;
    /** @var int  */
    public int $onPlayerTick = 0;
    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
    }

    /**
     * @param Position $position
     * @param Player $player
     */
    public static function spawn(Position $position, Player $player){
        $playerCount = 0;//获取玩家数量
        foreach ($position->getLevel()->getPlayers() as $p){
            if ($p->isA() and $p->distance($position)<=13){
                $playerCount++;
            }
        }
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag('Pos', [
            new DoubleTag('', $position->x + 0.5),
            new DoubleTag('', $position->y + 5),
            new DoubleTag('', $position->z + 0.5)
        ]);
        $nbt->Rotation = new ListTag('Rotation', [
            new FloatTag('', 0),
            new FloatTag('', 0)
        ]);
        /** @var GaiaGuardiansIII $entity */
        $entity = new GaiaGuardiansIII($position->getLevel(), $nbt);//实例化盖亚守护者III
        $entity->setPlayerCount($playerCount);//设置玩家数量
        $entity->setBasePos($position);//设置信标坐标
        $entity->setMaxHealth(500 + $playerCount * 300);
        $entity->setHealth($entity->getMaxHealth());
        $entity->setNameTagVisible(true);
        $entity->setNameTagAlwaysVisible(true);
        $entity->setArmorV(150);//150护甲
        $entity->setNameTag('盖亚守护者 III');
        $entity->spawnCrysta();
        Main::getInstance()->gaia[$entity->getId()] = $entity;
    }
    /**
     * @return Position
     */
    public function getBasePos(): Position
    {
        return $this->basePos;
    }

    /**
     * @param Position $basePos
     */
    public function setBasePos(Position $basePos): void
    {
        $this->basePos = $basePos;
    }
    /**
     * @return int
     */
    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    /**
     * @param int $playerCount
     */
    public function setPlayerCount(int $playerCount): void
    {
        $this->playerCount = $playerCount;
    }


    public function onUpdate($tick)
    {
        if ($this->getPresencePlayerCount() > $this->getPlayerCount()){
            foreach ($this->getLevel()->getPlayers() as $p){
                /** @var Player $p */
                if ($p->isA() and $p->distance($this->getBasePos())<13){
                    $p->sendMessage("§c在场人数比召唤人数还多！这是弟弟行为。");
                }
            }
            $this->close();
            return false;
        }
        if ($this->getPresencePlayerCount() <= 0){//附近没有玩家
            if ($this->onPlayerTick > 20){
                $this->close();
                return false;
            }
            $this->onPlayerTick++;
        }else{
            $this->onPlayerTick = 0;
        }
        if ($this->age % 20 == 0)$this->spawnBorderParticle();
        $this->age ++;

        if ($this->age % 5 == 0){
            /** @var Player $player */
            foreach ($this->getPresencePlayer() as $player){
                if($player->isCreative()){
                    $player->setGamemode(0);
                }
                if (!$player->canSelected() or !$player->isSurvival())continue;
                if ($this->getBasePos()->y - $player->y > 1){
                    $player->setLastDamageCause(new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_DIDI, PHP_INT_MAX));
                    $player->setHealth(0);
                    $player->sendMessage('§c你的高度低于祭坛坐标！');
                    continue;
                }
                $buff = $player->getBuff();
                if ($buff->getEnable()){
                    $player->setLastDamageCause(new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_DIDI, PHP_INT_MAX));
                    $player->setHealth(0);
                    $player->sendMessage('§c开Buff攻击盖亚！这是弟弟行为。');
                    $player->sendMessage('输入/tw buff [关闭/打开]来控制');
                }
                foreach ($player->getInventory()->getContents() as $index => $item){//检查背包物品
                    if ($item instanceof Weapon or $item instanceof Armor or $item instanceof BaseOrnaments){
                        $player->getInventory()->setItem($index, Item::get(0));
                        $player->getLevel()->dropItem($player, $item);
                    }
                }
                foreach ($player->getOrnamentsInventory()->getContents() as $index => $item){//检查饰品栏
                    if ($item instanceof BaseOrnaments){
                        $player->getOrnamentsInventory()->setItem($index, Item::get(0));
                        $player->getLevel()->dropItem($player, $item);
                    }
                }
            }
        }
        if (Server::getInstance()->getTick() - $this->lastMove > 80){
            $this->lastMove = Server::getInstance()->getTick();
            $randX = mt_rand(-8, 8);
            $randZ = mt_rand(-8, 8);
            $this->getLevel()->addSound(new EndermanTeleportSound($this));
            $this->teleport($this->getBasePos()->add($randX, 0, $randZ));
            foreach($this->getLevel()->getCollisionBlocks($this->getBoundingBox()) as $block){
                if ($block->equals($this->getBasePos()))continue;
                $this->getLevel()->useBreakOn($block);//破坏碰撞的方块..
            }
            $this->spawnLandmine();
        }
        $this->updateLandmines();
        $this->updateTarget();
        return true;
    }

    /**
     * TODO: 在关服的时候保存盖亚到地图
     */
    public function saveNBT()
    {

    }

    /**
     * 发射炸弹
     */
    public function launchBomb(){
        $count = mt_rand(1, 3);
        foreach ($this->getPresencePlayer() as $player){

        }
    }
    /**
     * 产出炸弹
     */
    public function spawnLandmine(){//landmine
        $count = mt_rand(1, 3);
        $count += $this->getPlayerCount();
        while($count-->0){
            $randX = mt_rand(-10, 10);
            $randZ = mt_rand(-10, 10);
            $landmine = new Landmine($this->getBasePos()->add($randX, 0, $randZ), 0.5, $this);
            $this->landmines[spl_object_hash($landmine)] = $landmine;
        }
    }
    public function updateLandmines(){
        /** @var Landmine $landmine */
        foreach ($this->landmines as $landmine) {
            $landmine->onUpdate();
        }
    }
    public function removeLandmine(Landmine $landmine){
        unset($this->landmines[spl_object_hash($landmine)]);
    }
    /**
     * 检查看的目标
     */
    protected function checkCeeTarget()
    {
        if(Server::getInstance()->getTick() - $this->lastUpdateSee > 100) {
            $this->lastUpdateSee = Server::getInstance()->getTick() + mt_rand(-20, 20);
            if (mt_rand(0, 1)){
                $player = [24, null];
                foreach ($this->getLevel()->getPlayers() as $p){
                    if (!$p->isA())continue;
                    if ($p->distance($this) < $player[0])$player = [$p->distance($this), $p];
                }
                if ($player[1]!=null and $player[1] instanceof Player){
                    $this->baseTarget = $player[1];
                    return;
                }
            }
            $x = mt_rand(2, 5);
            $z = mt_rand(2, 5);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
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
     * @return int
     */
    public function getPresencePlayerCount(){
        $playerCount = 0;
        foreach ($this->getLevel()->getPlayers() as $p){
            if ($p->isA() and $p->distance($this->getBasePos())<13){
                $playerCount++;
            }
        }
        return $playerCount;
    }

    /**
     * @return array
     */
    public function getPresencePlayer(){
        $players = [];
        foreach ($this->getLevel()->getPlayers() as $p){
            if ($p->isA() and $p->distance($this->getBasePos())<13){
                $players[] = $p;
            }
        }
        return $players;
    }

    /**
     * 更新看的目标
     */
    public function updateTarget(){
        $this->checkCeeTarget();
        //$this->baseTarget = Server::getInstance()->getPlayer("A");
        if ($this->baseTarget !== null){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            $diff = abs($x) + abs($z);
            if($x==0 and $z==0) {
                $this->yaw= 0;
                $this->pitch = $y > 0 ? -90 : 90;
            }else{
                $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
                $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
            }
        }
    }

    /**
     * 关闭
     */
    public function close()
    {
        foreach ($this->crystas as $crysta){
            /** @var GaiaCrystal $crysta */
            $crysta->close();
        }
        unset(Main::getInstance()->gaia[$this->getId()]);
        parent::close();
    }

    /**
     * 复活水晶
     */
    public function spawnCrysta(){
        $v3 = $this->getBasePos()->asVector3();
        $blocks[] = $this->getLevel()->getBlock($v3->add(4, 0, 4));
        $blocks[] = $this->getLevel()->getBlock($v3->add(4, 0, -4));
        $blocks[] = $this->getLevel()->getBlock($v3->add(-4, 0, -4));
        $blocks[] = $this->getLevel()->getBlock($v3->add(-4, 0, 4));
        foreach ($blocks as $block){
            $nbt = new CompoundTag;
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $block->x + 0.5),
                new DoubleTag("", $block->y),
                new DoubleTag("", $block->z + 0.5)
            ]);
            $nbt->Rotation = new ListTag('Rotation', [
                new FloatTag('', 0),
                new FloatTag('', 0)
            ]);
            $entity = new GaiaCrystal($this->getLevel(), $nbt, $this);
            $entity->spawnToAll();
            $this->getLevel()->spawnLightning($entity, 0, $this);
            $this->crystas[] = $entity;
        }
    }

    /**
     * @return UUID
     */
    public function getUniqueId(){
        if ($this->uid === null){
            $this->uid=UUID::fromData($this->getId(), $this->skinData, ('盖亚守护者 '.($this->type == 0?'I':'II')));
        }
        return $this->uid;
    }

    /**
     * @param Player $player
     * @param bool $send
     */
    public function despawnFrom(Player $player, bool $send = true){
        if(isset($this->hasSpawned[$player->getLoaderId()])){
            if($send) {
                $pk = new PlayerListPacket();
                $pk->type = PlayerListPacket::TYPE_REMOVE;
                $pk->entries[] = [$this->getUniqueId()];
                $player->dataPacket($pk);
            }
            $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, false);
            parent::despawnFrom($player, $send);
        }
    }
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "盖亚守护者III";
    }
}
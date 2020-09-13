<?php
namespace LTEntity\entity\Guide;

use LTCraft\Main;
use LTEntity\entity\monster\walking\EMods\ANPC;
use LTEntity\entity\Process\FlyBubble;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\Weapon;
use LTMenu\Open;
use pocketmine\block\Block;
use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\particle\WhiteSmokeParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\ExpPickupSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\ItemFrame;
use pocketmine\utils\UUID;

class Trident extends Creature
{
    private static string $prefix = "§e§l[神秘匠人]";
    private ?Player $player = null;
    /**
     * @var Position 看的目标
     */
    private ?Position $baseTarget = null;
    /** @var string  */
    private string $skinData = '';
    /** @var int  */
    private int $lastUpdateSee = 0;
    /** @var int 回放进度 在记录行动是从521 tick开始的 我们回放也从这个tick开始 */
    private int $progress = 521;
    /** @var int 进度TICK */
    private int $progressTick = 0;
    /** @var int 状态进度 */
    private int $statusProgress = 0;
    /** @var array 空方块 */
    private array $emptyBlocks = [];
    /** @var array 铁砧 */
    private array $anvilBlocks = [];
    /** @var int 铁砧索引 制造特效 每Tick +1 */
    private int $anvilIndex = 0;
    /** @var int 模式 0：+1  1：-1 */
    private int $anvilMode = 0;
    /** @var array 路线 */
    private array $route = [];
    private string $lastClickPlayer = '';
    private array $blocks = [
        [-1266, 1, -612],
        [-1263, 1, -612],
        [-1260, 1, -612],
        [-1263, 1, -617],
        [-1263, 1, -624],
        [-1263, 1, -630],
    ];
    /** @var int 坐标索引 */
    private int $posIndex = 0;
    private int $status = self::LEISURE;
    const LEISURE = -1;//无事可做
    const PREPARE = 0;//准备回放
    const PLAYBACK = 1;//正在回放
    const WAIT = 2;//完成 等待
    const TAKE = 3;//取烧练的物品
    const POUR = 4;//倒进去
    const ENDWAIT = 5;//结束等待
    const DONE = 6;//完成
    const ABSORPTION = 7;//吸收经验
    const BACK = 8;//吸收经验
    private ?UUID $uid = null;

    /**
     * Trident constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt)
    {

        $this->route = Main::getInstance()->getMoveAction();
        $nbt->Pos = new ListTag('Pos', [
            new DoubleTag('', -1256.837),
            new DoubleTag('', 9.0625),
            new DoubleTag('', -612.4217)
        ]);
        parent::__construct($level, $nbt);
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);
        foreach ($this->getLevel()->getEntities() as $entity){
            if ($entity instanceof Trident and $entity!==$this){
                $entity->close();
            }
        }
        $this->setNameTag('§d神秘匠人');
    }

    /**
     * @param float $damage
     * @param EntityDamageEvent $source
     * @return bool|void
     */
    public function attack($damage, EntityDamageEvent $source)
    {
        if ($source instanceof EntityDamageByEntityEvent and $source->getDamager() instanceof Player){
            /** @var Player $damager */
            $damager = $source->getDamager();
            if ($this->player == null){
                if(Open::getNumber($damager, ['材料', '波塞冬钢铁', 168])){
                    if(Open::getNumber($damager, ['材料', '失落之魂', 88])){
                        if ($this->lastClickPlayer!=$damager->getName()){
                            $this->lastClickPlayer = $damager->getName();
                            $damager->sendMessage(self::$prefix."看来你要准备锻造失落的三叉戟了，请做好一下准备：");
                            $damager->sendMessage(self::$prefix."等级>=200级。");
                            $damager->sendMessage(self::$prefix."生命值>419。");
                            $damager->sendMessage(self::$prefix."确保接下来的五分钟不会掉线。");
                            $damager->sendMessage(self::$prefix."再次点击确定。。。");
                        }else{
                            $damager->sendMessage(self::$prefix."好，你很有诚意。");
                            $this->player = $damager;
                            $this->status = self::PREPARE;
                        }
                    }else{
                        $damager->sendMessage(self::$prefix."我可不会免费给你锻造，你可以给我88个失落之魂作为交换，听说那玩意可以召唤什么！");
                    }
                }else{
                    $damager->sendMessage(self::$prefix."你的材料不足，请集齐波塞冬钢铁×168再来吧。");
                }
            }else{
                if ($this->player === $damager)return false;
                $damager->sendMessage(self::$prefix."请等待排队，现在正在为其他玩家打造呢。");
            }
        }
    }
    public function onUpdate($tick)
    {
        $this->age++;
        if ($this->status != self::LEISURE){
            if ($this->player->closed){
                $this->end();
            }
        }
        switch ($this->status){
            case self::PREPARE:
                switch ($this->progressTick){
                    case 0:
                        $this->player->sendMessage(self::$prefix."你将要打造的武器是亚特兰蒂斯失落的三叉戟。");
                    break;
                    case 30:
                        $this->player->sendMessage(self::$prefix."打造可能需要差不多几分钟，没关系，很快的。");
                    break;
                    case 60:
                        $this->player->sendMessage(self::$prefix."由于锻造要用到波塞冬钢铁，这种极为强大的钢铁是波塞冬留下的遗物。");
                    break;
                    case 90:
                        $this->player->sendMessage(self::$prefix."因此。打造期间你必须待在这里，在锻造的时候钢铁会吸收你的经验。");
                    break;
                    case 120:
                        $this->player->sendMessage(self::$prefix."传说拥有三叉戟就拥有号令海洋的能量，因此这个极为强大的武器是需要与你进行绑定的。");
                    break;
                    case 150:
                        $this->player->sendMessage(self::$prefix."了解完成后就跟我来吧。");
                    break;
                }
                $this->progressTick++;
                if ($this->age % 2 == 0){
                    $this->updateTarget();
                }
                /*
                if ($this->progressTick>=200){
                    $this->status = self::ENDWAIT;

                    $this->progressTick = 0;
                }
                 */
                if ($this->progressTick>=200)$this->status = self::PLAYBACK;
            break;
            case self::PLAYBACK:
                if (isset($this->route[$this->progress])){
                    foreach ($this->route[$this->progress] as $pos){
                        $ss = explode(':', $pos);
                        switch ($ss[0]){
                            case 'move':
                                $v3 = new \pocketmine\math\Vector3($ss[1], $ss[2], $ss[3]);
                                $this->setPositionAndRotation($v3, $ss[4], $ss[5]);
                            break;
                            case 'action':
                                $pk = new AnimatePacket();
                                $pk->eid = $this->getId();
                                $pk->action = $ss[1];
                                $this->server->broadcastPacket($this->getViewers(), $pk);
                            break;
                        }
                    }
                }
                $this->progress++;
                if ($this->progress == 703){
                    $this->player->sendMessage(self::$prefix."先吧波塞冬钢铁放进熔炼熔炉烧练一会。");
                    $this->player->sendMessage(self::$prefix."这些燃料用的波塞冬燃料，拥有极强的可燃性，基本不会熄灭。");
                }
                if ($this->progress>1048){
                    $this->status = self::WAIT;
                    $this->progressTick = 0;
                    $this->player->sendMessage(self::$prefix."已经吧所有钢铁放进熔炉了，我们等待一会~");
                }
            break;
            case self::WAIT:
                if ($this->age % 2 == 0){
                    $this->updateTarget();
                }

                if ($this->progressTick === 20*5){
                    $this->player->sendMessage(self::$prefix."这个过程大约需要60s。");
                }
                $this->progressTick++;
                if ($this->progressTick >= 20*60){
                    $this->status = self::TAKE;
                    $this->progress = 1684;
                    $this->player->sendMessage(self::$prefix."应该完成了 我们去取一下吧~");
                }
            break;
            case self::TAKE:
                if (isset($this->route[$this->progress])){
                    foreach ($this->route[$this->progress] as $pos){
                        $ss = explode(':', $pos);
                        switch ($ss[0]){
                            case 'move':
                                $v3 = new \pocketmine\math\Vector3($ss[1], $ss[2], $ss[3]);
                                $this->setPositionAndRotation($v3, $ss[4], $ss[5]);
                                break;
                            case 'action':
                                $pk = new AnimatePacket();
                                $pk->eid = $this->getId();
                                $pk->action = $ss[1];
                                $this->server->broadcastPacket($this->getViewers(), $pk);
                                break;
                        }
                    }
                }
                $this->progress++;
                if ($this->progress>2123){
                    $this->status = self::POUR;
                    $this->progress = 2737;
                    $this->player->sendMessage(self::$prefix."好的，现在可以吧烧练后的流体倒进去等待冷却了~");
                    $this->setItem(Item::get(325, 10));
                }
            break;
            case self::POUR:
                if (isset($this->route[$this->progress])){
                    foreach ($this->route[$this->progress] as $pos){
                        $ss = explode(':', $pos);
                        switch ($ss[0]){
                            case 'move':
                                $v3 = new \pocketmine\math\Vector3($ss[1], $ss[2], $ss[3]);
                                $this->setPositionAndRotation($v3, $ss[4], $ss[5]);
                                break;
                            case 'action':
                                $pk = new AnimatePacket();
                                $pk->eid = $this->getId();
                                $pk->action = $ss[1];
                                $this->server->broadcastPacket($this->getViewers(), $pk);
                                $pos = $this->blocks[$this->posIndex];
                                $this->level->setBlock(new Vector3($pos[0], $pos[1], $pos[2]), Block::get(10));
                                $this->posIndex++;
                            break;
                        }
                    }
                }
                $this->progress++;
                if ($this->progress>3120){
                    $this->status = self::ENDWAIT;
                    $this->progress = 3120;
                    $this->progressTick = 0;
                    $this->setItem(Item::get(0));
                    $this->player->sendMessage(self::$prefix."现在可以等待完成了。");
                }
            break;
            case self::ENDWAIT:
                $this->progressTick++;
                if ($this->progressTick == 20*10){//等待十秒钟  流动较慢 可能会存在未填充方块 然后我们保存一下空方块的位置 没必要每次去查找造成没必要的性能浪费
                    $blocks = $this->level->getAllInBox(new Vector3(-1266, 1, -631), new Vector3(-1259, 1, -608));
                    foreach ($blocks as $block){
                        if ($block->getId() == 10 or $block->getId()==11){
                            $this->emptyBlocks[] = $block;
                        }
                    }
                }
                if (count($this->emptyBlocks) > 0){//当查找到所有方块的时候进行更新
                    if ($this->age % 2 == 0 and $this->progressTick < 60*20){
                        foreach ($this->emptyBlocks as $block){
                            $this->level->addParticle(new WhiteSmokeParticle($block->add(0.5, 1.1, 0.5)));//特效
                            $this->getLevel()->addSound(new FizzSound($block->add(0.5, 0.5, 0.5), 2.5 + mt_rand(0, 1000) / 1000 * 0.8));
                        }
                        $this->updateTarget();
                     }
                    if ($this->progressTick >= 30*20 and $this->progressTick < 60*20){
                        if ($this->progressTick == 30*20){
                            $blocks = $this->level->getAllInBox(new Vector3(-1269, 2, -632), new Vector3(-1268, 2, -607));
                            foreach ($blocks as $block){
                                if ($block->getId() == 145){
                                    $this->anvilBlocks[] = $block;
                                }
                            }
                        }
                        $block = $this->anvilBlocks[$this->anvilIndex];
                        $max = count($this->anvilBlocks) - 1;
                        if ($this->anvilMode){
                            $this->anvilIndex--;
                            if ($this->anvilIndex == 0){
                                $this->anvilMode = 0;
                            }
                        }else{
                            $this->anvilIndex++;
                            if ($this->anvilIndex >= $max){
                                $this->anvilMode = 1;
                            }
                        }
                        $this->getLevel()->addParticle(new GenericParticle($block->add(0.5, 1, 0.5), 8));
                        $this->getLevel()->addSound(new AnvilUseSound($block));
                    }
                    if ($this->progressTick == 60*20){
                        foreach ($this->emptyBlocks as $block){
                            if ($block->getId() == 10 or $block->getId()==11){
                                $this->level->setBlock($block, Block::get(0));
                            }
                        }
                        $this->status = self::DONE;
                        $this->progress = 13994;
                        $this->progressTick = 0;
                        $this->player->sendMessage(self::$prefix."好的，出炉了！");
                    }
                }
            break;
            case self::DONE:
                if (isset($this->route[$this->progress])){
                    foreach ($this->route[$this->progress] as $pos){
                        $ss = explode(':', $pos);
                        switch ($ss[0]){
                            case 'move':
                                $v3 = new \pocketmine\math\Vector3($ss[1], $ss[2], $ss[3]);
                                $this->setPositionAndRotation($v3, $ss[4], $ss[5]);
                            break;
                            case 'action':
                                $pk = new AnimatePacket();
                                $pk->eid = $this->getId();
                                $pk->action = $ss[1];
                                $this->server->broadcastPacket($this->getViewers(), $pk);
                                if ($this->statusProgress == 0){
                                    $this->setItem(\LTItem\Main::getInstance()->createWeapon("近战", "失落的三叉戟", $this->player));
                                    $this->statusProgress++;
                                }elseif ($this->statusProgress == 1){
                                    $this->setItem(Item::get(0));
                                    $tile = $this->level->getTile(new Vector3(-1257, 2, -632));
                                    if ($tile instanceof ItemFrame){
                                        $tile->setItem(\LTItem\Main::getInstance()->createWeapon("近战", "失落的三叉戟", $this->player));
                                    }
                                    $this->statusProgress++;
                                }
                            break;
                        }
                    }
                }
                $this->progress++;
                if ($this->progress>14186){
                    $this->status = self::ABSORPTION;
                    $this->progress = 16498;
                    $this->progressTick = 0;
                    $this->statusProgress = 0;
                    $this->player->sendMessage(self::$prefix."好的过来站到这个半砖上，站上去的时候你身上的护甲会掉落，你可以提前脱掉。");
                    $this->player->sendMessage(self::$prefix."站在这里会接受上天的挑战，会对你造成419生命值的惩罚，如果你能活下来就有这个资格！");
                }
            break;
            case self::ABSORPTION:
                if (
                    floor($this->player->getX()) == -1257 and
                    floor($this->player->getY()) == 1 and
                    floor($this->player->getZ()) == -631
                ){
                    if ($this->progressTick == 0){
                        $this->statusProgress = 1;
                        $this->player->sendMessage(self::$prefix."好的，站在这里不要动。");
                    }
                    $this->progressTick++;
                    if ($this->progressTick == 20){
                        if ($this->player->getGamemode() == 1){
                            $this->player->setGamemode(0);
                        }
                        foreach ($this->player->getInventory()->getArmorContents() as $i => $item){
                            if ($item instanceof Armor){
                                $this->player->getInventory()->setItem($this->player->getInventory()->getSize()+$i, Item::get(0));
                                /** @var \pocketmine\entity\Item $entity */
                                $entity = $this->level->dropItem($this->player, $item);
                                $entity->setOwner($this->player->getName());
                            }
                        }
                        $this->level->spawnLightning($this->player, 419, $this);
                        if (!$this->player->isA()){
                            $this->status = self::BACK;
                            $this->player->sendMessage(self::$prefix."看来还是不配拥有这个武器。");
                        }
                    }
                    if ($this->progressTick > 20){
                        if ($this->progressTick % 2 == 0){
                            $this->getLevel()->addSound(new ExpPickupSound($this, mt_rand(0, 1000)));
                            $entity = new FlyBubble($this->getLevel(), new CompoundTag("", [
                                "Pos" => new ListTag("Pos", [
                                    new DoubleTag("", $this->player->getX()),
                                    new DoubleTag("", $this->player->getY() + 1),
                                    new DoubleTag("", $this->player->getZ())
                                ]),
                                "Motion" => new ListTag("Motion", [
                                    new DoubleTag("", 0),
                                    new DoubleTag("", 0),
                                    new DoubleTag("", 0)
                                ]),
                                "Rotation" => new ListTag("Rotation", [
                                    new FloatTag("", 0),
                                    new FloatTag("", 0)
                                ]),
                                "Health" => new ShortTag("Health", PHP_INT_MAX),
                            ]));
                            $entity->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_IMMOBILE, true);
                            $entity->setTarget(new Position(-1256 - 0.5, 2 + 0.5, -631 - 0.9, $this->level));
                            $entity->spawnToAll();
                        }
                        if ($this->progressTick > 20*25+20){
                            $this->player->setGrade($this->player->getGrade() - 50);
                            $this->player->recalculateHealthAndArmorV();
                            $tile = $this->level->getTile(new Vector3(-1257, 2, -632));
                            if ($tile instanceof ItemFrame){
                                /** @var Weapon $item */
                                $item = $tile->getItem();
                                $item = $item->enchantment();
                                $tile->setItem(Item::get(0));
                                $entity = $this->level->dropItem(new Vector3(-1256 - 0.5, 2, -631 - 0.5), $item);
                                $entity->setOwner($this->player->getName());
                            }
                            $this->player->sendMessage(self::$prefix."好的，你通过了波塞冬的挑战并且成功激活了三叉戟，现在你可以拿出来了！");
                            $this->status = self::BACK;
                        }
                    }
                }else{
                    if ($this->statusProgress == 1){
                        $this->statusProgress = 0;
                        $this->player->sendMessage(self::$prefix."你脱离了指定位置，挑战和经验吸收将重新开始。");
                        if ($this->progressTick >= 30){
                            $this->player->setGrade($this->player->getGrade() - floor(($this->progressTick - 20) / 10));
                            $this->player->recalculateHealthAndArmorV();
                        }
                        $this->progressTick = 0;
                    }
                }
            break;
            case self::BACK:
                if ($this->progressTick < 20*5){
                    $this->progressTick++;
                    if ($this->age % 20 == 0){
                        $this->updateTarget();
                    }
                    break;
                }
                if (isset($this->route[$this->progress])){
                    foreach ($this->route[$this->progress] as $pos){
                        $ss = explode(':', $pos);
                        switch ($ss[0]){
                            case 'move':
                                $v3 = new \pocketmine\math\Vector3($ss[1], $ss[2], $ss[3]);
                                $this->setPositionAndRotation($v3, $ss[4], $ss[5]);
                            break;
                            case 'action':
                                $pk = new AnimatePacket();
                                $pk->eid = $this->getId();
                                $pk->action = $ss[1];
                                $this->server->broadcastPacket($this->getViewers(), $pk);
                            break;
                        }
                    }
                }
                $this->progress++;
                if ($this->progress>16790){
                    $this->end();
                }
            break;
            default:
                if ($this->age % 10 == 0){
                    $this->updateTarget();
                }
        }
        $this->updateMovement();
        return true;
    }

    /**
     * 完成后调用
     */
    public function end(){
        $this->status = self::LEISURE;
        $this->progressTick = 0;
        $this->lastClickPlayer = '';
        $this->statusProgress = 0;
        $this->emptyBlocks = [];
        $this->progress = 521;
        $this->posIndex = 0;
        $this->anvilMode = 0;
        $this->anvilBlocks = [];
        $this->anvilIndex = 0;
        $this->player = null;
        $tile = $this->level->getTile(new Vector3(-1257, 2, -632));
        $this->teleport(new Vector3(-1256.837, 9.0625, -612.4217));
        if ($tile instanceof ItemFrame){
            $tile->setItem(Item::get(0));
        }
    }
    public function updateMovement()
    {
        if($this->lastX !== $this->x || $this->lastY !== $this->y || $this->lastZ !== $this->z || $this->lastYaw !== $this->yaw || $this->lastPitch !== $this->pitch) {
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;
            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;
        }
        $yaw = $this->yaw;
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y + 1.62, $this->z, $yaw, $this->pitch, $yaw);
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
     * 更新看的目标
     */
    public function updateTarget(){
        $this->checkCeeTarget();
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

    public function setItem(Item $item){
        $pk = new MobEquipmentPacket();
        $pk->eid = $this->getId();
        $pk->item = $item;
        $pk->slot = 1;
        $pk->selectedSlot = 1;
        $pk->windowId = ContainerSetContentPacket::SPECIAL_INVENTORY;
        foreach ($this->getViewers() as $player){
            $player->dataPacket($pk);
        }
    }
    /**
     * @return UUID
     */
    public function getUniqueId(){
        if ($this->uid === null){
            $this->uid = UUID::fromData($this->getId(), $this->skinData, '神秘匠人');
        }
        return $this->uid;
    }
    /**
     * @param bool $sendAll
     */
    public function sendAttributes(bool $sendAll = false){
        $entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
        if(count($entries) > 0){
            $pk = new UpdateAttributesPacket();
            $pk->entityId = $this->id;
            $pk->entries = $entries;
            foreach ($this->hasSpawned as $player) {
                $player->dataPacket($pk);
            }
            foreach($entries as $entry){
                $entry->markSynchronized();
            }
        }
    }
    /**
     * @param Player $player
     */
    public function spawnTo(Player $player){
        if(!isset($this->hasSpawned[$player->getLoaderId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
            $pk = new AddPlayerPacket();
            $pk->uuid = $this->getUniqueId();
            $pk->username = $this->getName();
            $pk->eid = $this->getId();
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = 0;
            $pk->speedY = 0;
            $pk->speedZ = 0;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->item=Item::get(0);
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);
            parent::spawnTo($player);
        }
    }
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "神秘匠人";
    }
}
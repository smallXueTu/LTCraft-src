<?php
namespace LTEntity\entity\Process;


use LTEntity\Main;
use LTItem\SpecialItems\Material;
use LTItem\SpecialItems\Material\Drawings;
use LTMenu\Open;
use pocketmine\block\Air;
use pocketmine\block\Anvil;
use pocketmine\block\Block;
use pocketmine\block\BurningFurnace;
use pocketmine\block\Chest;
use pocketmine\block\Furnace;
use pocketmine\block\Glass;
use pocketmine\block\Lava;
use pocketmine\block\StoneBricks;
use pocketmine\entity\Entity;
use pocketmine\entity\Item as ItemEntity;
use pocketmine\entity\Slime;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\InventoryType;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\Position;
use pocketmine\block\ItemFrame;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

/**
 * 熔炼坛
 * 多方块结构实体
 * Class Fusion
 * @package LTEntity\entity\Process
 * 闲着没事写这个干嘛
 */
class Fusion extends Entity implements InventoryHolder
{
    /** @var \array[][][] 配方 */
    public static array $formulas = [
        '终极武器图纸' => [
            [
                ['近战', '桃木剑', 1],
                ['近战', '骑士长剑', 1],
                ['近战', '沙之刃', 1],
                ['近战', '生化之刃', 1],
                ['近战', '玄晶之刃', 1],
                ['近战', '水晶之痕', 1],
                ['近战', '灭世者', 1]
            ],
            [
                ['材料', '武器熔炼石', 30]
            ],
            [
                ['材料', '初级燃料', 10]
            ]
        ],
        '史诗战靴图纸' => [
            [
                ['盔甲', '远古战靴', 1],
                ['盔甲', '密室熔炼靴', 1],
                ['盔甲', '冰雪靴', 1],
                ['盔甲', '水银之靴', 1]
            ],
            [
                ['材料', '盔甲熔炼石', 30]
            ],
            [
                ['材料', '中级燃料', 10]
            ]
        ],
        '史诗护膝图纸' => [
            [
                ['盔甲', '密室熔炼护膝', 1],
                ['盔甲', '冰雪膝', 1],
                ['盔甲', '符文之膝', 1],
                ['盔甲', '异界之膝', 1],
                ['盔甲', '巨龙之膝', 1]
            ],
            [
                ['材料', '盔甲熔炼石', 30]
            ],
            [
                ['材料', '中级燃料', 10]
            ]
        ],
        '史诗胸甲图纸' => [
            [
                ['盔甲', '龙鳞之铠', 1],
                ['盔甲', '异界之甲', 1],
                ['盔甲', '冰雪甲', 1],
                ['盔甲', '龙之甲', 1],
                ['盔甲', '灭世者之甲', 1],
                ['盔甲', '狂徒铠甲', 1]
            ],
            [
                ['材料', '盔甲熔炼石', 30]
            ],
            [
                ['材料', '中级燃料', 10]
            ]
        ],
        '史诗头盔图纸' => [
            [
                ['盔甲', '龙鳞之帽', 1],
                ['盔甲', '深渊面具', 1],
                ['盔甲', '冰雪帽', 1],
                ['盔甲', '远古头盔', 1]
            ],
            [
                ['材料', '盔甲熔炼石', 30]
            ],
            [
                ['材料', '中级燃料', 10]
            ]
        ],
        '史诗武器图纸' => [
            [
                ['近战', '远古战刃', 1],
                ['近战', '碧玉刃', 1],
                ['近战', '天魔化血神刀', 1],
                ['近战', '符文之刃', 1],
                ['更多要求', '近战', '*', '终极', 25, 1],
            ],
            [
                ['材料', '武器熔炼石', 60]
            ],
            [
                ['材料', '中级燃料', 10]
            ]
        ],
        '觉醒石模板' => [
            [
                ['材料', '觉醒石碎片', 20],
                ['材料', '熔炼残渣', 3]
            ],
            [
                ['材料', '残渣熔炼石', 10]
            ],
            [
                ['材料', '残渣燃料', 10]
            ]
        ],
    ];
    public static array $times = [
        '觉醒石模板' => 60 * 3,
        '史诗武器图纸' => 60 * 15,
        '史诗头盔图纸' => 60 * 15,
        '史诗胸甲图纸' => 60 * 15,
        '史诗护膝图纸' => 60 * 15,
        '史诗战靴图纸' => 60 * 15,
        '终极武器图纸' => 60 * 8,
    ];
    /** @var int $process 进度 */
    public int $process = 0;
    /** @var float $temperature 温度 */
    public float $temperature = 20.00;
    /** @var int $stable 稳定性 */
    public int $stable = 1;
    /** @var string 图纸名字 */
    public string $drawingName;
    /** @var Drawings 图纸 */
    public Drawings $drawings;
    /** @var string 提示 */
    public string $tip = '';
    /** @var Chest $chest 箱子坐标 */
    public Chest $chest;
    /** @var Position $center 玻璃坐标 */
    public Position $center;
    /** @var ItemFrame $itemFramePosition 物品展示框 */
    public ItemFrame $itemFramePosition;
    /** @var ?Player $player 玩家 */
    public ?Player $player = null;
    /** @var boolean $breakd 多方块结构是否被破坏 */
    public bool $breakd = false;
    /** @var int $breakTick 被破坏后实体存活十秒 */
    public int $breakTick = 0;
    /** @var int $startTick 开始Tick */
    public int $startTick = 0;
    /** @var BaseInventory $inventory 这个实体的背包 */
    public BaseInventory $inventory;
    /** @var array $anvils 三个铁砧 */
    public array $anvils = [];
    /** @var array $furnaces 三个熔炉 */
    public array $furnaces = [];
    /** @var array $itemEntities 物品实体 */
    public array $itemEntities = [];
    /** @var bool 失败 如果失败了到最后无论如何都无法产出结果 */
    private bool $failure = false;
    /**
     * @var bool 强制冷却
     */
    private bool $forcedCooling = false;
    /**
     * @var bool 冷却液加速
     */
    private bool $coolant = false;
    /**
     * @var bool 没玩家
     */
    private bool $noPlayer = false;
    /**
     * @var int 粒子进度
     */
    private int $progress = 0;
    private int $particleHigh = 0;
    /**
     * @var string
     */
    public string $playerName;

    /**
     * 尝试创建这个实体
     * 需要满足多方块结构
     * @param ItemFrame $position
     * @param Drawings $drawings
     * @param Player $player
     * @return Entity|null
     */
    public static function tryCreate(ItemFrame $position, Drawings $drawings, Player $player){
        try{
        $blocks = self::checkBlocks($position);
        if (count($blocks) <= 0){
            $nbt = new CompoundTag;
            $nbt->Pos = new ListTag('Pos', [
                new DoubleTag('', $position->x + 0.5),
                new DoubleTag('', $position->y + 0.5),
                new DoubleTag('', $position->z + 0.5)
            ]);
            $nbt->Motion = new ListTag('Motion', [
                new DoubleTag('', 0),
                new DoubleTag('', 0),
                new DoubleTag('', 0)
            ]);
            $entity = new Fusion($position->getLevel(), $nbt);
            $entity->initFusion($position, $drawings, $player);
            $entity->spawnToAll();
            Main::getInstance()->fusion[$entity->getId()] = $entity;
            return $entity;
        }else{
            $entities = $position->getLevel()->getCollidingEntities(new AxisAlignedBB(
                $position->x - 3,
                $position->y - 3,
                $position->z - 3,
                $position->x + 3,
                $position->y + 3,
                $position->z + 3
            ));
            foreach ($entities as $entity){
                if ($entity instanceof Prompt){
                    return null;
                }
            }
            $nbt = new CompoundTag;
            $nbt->Pos = new ListTag("Pos", [
                new DoubleTag("", $position->x+0.5),
                new DoubleTag("", $position->y+0.5),
                new DoubleTag("", $position->z+0.5)
            ]);
            $entity = new Prompt($position->getLevel(), $nbt);
            $entity->blocks = $blocks;
        }
        }catch (\Throwable $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());
        }
        return null;
    }

    /**
     * 是否强制冷却
     * @return bool
     */
    public function isForcedCooling(): bool
    {
        return $this->forcedCooling;
    }
    /**
     * Fusion constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        unset($this->dataProperties[7], $this->dataProperties[43]);
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);
        $this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, true);
        $this->setDataProperty(self::DATA_LEAD_HOLDER_EID, self::DATA_TYPE_LONG, 0);
        $this->setDataProperty(self::DATA_SCALE, self::DATA_TYPE_FLOAT, 0);
    }

    /**
     * @param ItemFrame $itemFrame 物品展示框
     * @param Drawings $drawing 图纸名字
     * @param Player $player
     */
    public function initFusion(ItemFrame $itemFrame, Drawings $drawing, Player $player){
        $this->drawingName = $drawing->getLTName();
        $sides = [
            0 => 4,
            1 => 5,
            2 => 2,
            3 => 3
        ];
        $center = $itemFrame->getSide($sides[$itemFrame->getDamage()]);
        $this->center = $center;
        $this->player = $player;
        $this->playerName = $player->getName();
        $this->drawings = $drawing;
        $this->anvils = self::getAnvils($itemFrame);
        $this->furnaces = self::getFurnaces($itemFrame);
        $this->chest = $this->getLevel()->getBlock($center->getSide(Vector3::SIDE_UP, 3));
        $this->itemFramePosition = $itemFrame;
        $this->inventory = (new Class($this, InventoryType::get(InventoryType::CHEST)) extends BaseInventory{

        });
        $this->updateFloatText();
    }

    /**
     * @return Drawings
     */
    public function getDrawings(): Drawings
    {
        return $this->drawings;
    }
    /**
     * 更新悬浮字
     */
    public function updateFloatText(){
        $text = '§e§l熔炼祭坛'."\n";
        $text .= '§d当前第：'.$this->getProcess().'阶段。'."\n";
        $text .= '§d当前温度：'.$this->getTemperatureString().'°§d。'."\n";
//        $text .= '§d当前稳定性：'.$this->getStable().'。'."\n";
        $text .= '§a阶段待完成事件：'."\n";
        $text .= $this->getProcessNeed();
        if ($text!=$this->getNameTag()){
            $this->setNameTag($text);
        }
    }
    public function close()
    {
        unset(Main::getInstance()->fusion[$this->getId()]);
        /** @var Block $furnace $furnace */
        foreach ($this->furnaces as $furnace){
            if ($furnace->getId()!==Item::FURNACE)
                if ($this->breakd==true)$this->getLevel()->setBlock($furnace, Block::get(Item::FURNACE, $furnace->getDamage()), true);//熄灭熔炉
        }
        /** @var FloatItem $entity */
        foreach ($this->itemEntities as $entity){
            $entity->close();
        }
        parent::close();
    }

    /**
     * @return int
     */
    public function getTemperature(): float
    {
        return $this->temperature;
    }

    /**
     * @return string
     */
    public function getTemperatureString(): string
    {
        if($this->temperature < 50){
            $p = '§a';
        }elseif ($this->temperature < 150){
            $p = '§e';
        }elseif ($this->temperature < 500){
            $p = '§6';
        }elseif ($this->temperature >= 500){
            $p = '§c';
        }else{
            $p = '';
        }
        return $p.$this->getTemperature();
    }
    /**
     * @return string
     */
    public function getProcess(): string
    {
        switch ($this->process+1){
            case 1:
                return "一";
            case 2:
                return "二";
            case 3:
                return "三";
            case 4:
                return "四";
            case 5:
                return "五";
            case 6:
                return "六";
        }
    }

    /**
     * 稳定性
     * @return string
     */
    public function getStable(): string
    {
        switch ($this->stable){
            case 1:
                return "非常稳定";
            case 2:
                return "稳定";
            case 3:
                return "存在隐患";
            case 4:
                return "不稳定";
            case 5:
                return "非常不稳定";
            case 6:
                return "失去控制";
        }
    }

    /**
     * 更新进度
     * @return string
     */
    public function getProcessNeed(){
        if ($this->process == 0){
            $text = '请将以下需要的材料放置到上方箱子中：'."\n";
        }elseif($this->process == 1){
            $text = '请将以下需要的材料丢弃到下方岩浆中：'."\n";
        }elseif($this->process == 2){
            $text = '请在三个熔炉分别放置：'."\n";
        }else{
            $text = '';
        }
        if ($this->process==0){
            /** @var \pocketmine\tile\Chest $tile */
            $tile = $this->getLevel()->getTile($this->getChest());
            foreach (self::$formulas[$this->drawingName][$this->process] as $index => $formula){
                if (Open::getNumber($this->getPlayer(), $formula, $tile->getInventory())){
                    $text .= '§e'.Item::getItemInfo($formula)."\n";
                }else{
                    $text .= '§c'.Item::getItemInfo($formula)."\n";
                }
            }
        }elseif($this->process == 1 or $this->process == 2){
            foreach (self::$formulas[$this->drawingName][$this->process] as $index => $formula){
                $text .= '§c'.Item::getItemInfo($formula)."\n";
            }
        }else{
            return $this->tip;
        }
        $text .="§c注意！需要的锻造材料存在多余将会失败！";
        return $text;
    }

    /**
     * @return Player|null
     */
    public function getPlayer()
    {
        if ($this->player==null or !$this->player->isOnline()){
            $player = $this->getServer()->getPlayerExact($this->playerName);
            if ($player === null)return null;
            $this->player = $player;
        }
        return $this->player;
    }

    /**
     * @return string
     */
    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    /**
     * @return Position
     */
    public function getCenter(): Position
    {
        return $this->center;
    }

    /**
     * @return Chest
     */
    public function getChest(): Chest
    {
        return $this->chest;
    }

    /**
     * @return ItemFrame
     */
    public function getItemFramePosition(): ItemFrame
    {
        return $this->itemFramePosition;
    }
    public function spawnItemEntity(){
        $contents = $this->getInventory()->getContents();
        $count = count($contents);
        $angle = 180 / $count;//角度
        $currentAngle = 0;//当前角
        foreach ($contents as $item){
            $pos = $this->getCenter()->add(0.5 + 1.5 * cos($currentAngle * 3.14 / 36),1.5, 0.5 + 1.5 * sin($currentAngle * 3.14 / 36));//计算对于这个圈应该在的位置
            $entity = new FloatItem($this->getLevel(), new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $pos->getX()),
                    new DoubleTag("", $pos->getY()),
                    new DoubleTag("", $pos->getZ())
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", 0),
                    new DoubleTag("", 0),
                    new DoubleTag("", 0)
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", lcg_value() * 360),
                    new FloatTag("", 0)
                ]),
                "Health" => new ShortTag("Health", PHP_INT_MAX),
                "Item" => $item->nbtSerialize(-1, "Item"),
                "PickupDelay" => new ShortTag("PickupDelay", PHP_INT_MAX)
            ]));
            $entity->setCenter($this->getCenter()->add(0.5, 1.5, 0.5));
            $entity->setProgress($currentAngle);
            $entity->setFusion($this);
            $entity->spawnToAll();
            $this->itemEntities[] = $entity;
            $currentAngle += $angle;
        }
    }
    /**
     * @param $currentTick
     * @return bool
     */
    public function onUpdate($currentTick)
    {
        if ($this->breakd == true){
            $this->breakTick++;
            if ($this->breakTick >= 200){
                $this->close();
                return false;
            }
            return true;
        }
        if ($this->age % 20 == 0){
            if (count(self::checkBlocks($this->getItemFramePosition()))>0){
                $this->breakd = true;
                $this->setNameTag("§l§c警告，祭坛结构被打破，十秒后坠毁！");
                return true;
            }
            /** @var \pocketmine\tile\ItemFrame $tile */
            $tile = $this->getLevel()->getTile($this->getItemFramePosition());
            if ((!($tile->getItem() instanceof Drawings) or $tile->getItem()->getLTName()!==$this->getDrawings()->getLTName()) and $this->process<4){
                $this->breakd = true;
                $this->setNameTag("§l§c错误404，找不到图纸，十秒后坠毁！");
                return true;
            }
            foreach ($this->furnaces as $furnace){
                /** @var \pocketmine\tile\Furnace $tile */
                $tile = $this->getLevel()->getTile($furnace);
                if ($tile==null or $tile->getType()!=="熔炼熔炉"){
                    $this->breakd = true;
                    $this->setNameTag("§l§c检查三个熔炉为熔炼熔炉，十秒后坠毁！");
                    return true;
                }
            }
            foreach ($this->getLevel()->getPlayers() as $player){
                if ($this->distance($player) < 3){
                    if ($this->getTemperature() > 80 and $this->getTemperature() < 300){
                        $ev = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_HT, 1);
                        $player->attack($ev->getFinalDamage(), $ev);
                    }elseif($this->getTemperature() > 300){
                        $ev = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_HT, $player->getMaxHealth() * 0.2);
                        $player->attack($ev->getFinalDamage(), $ev);
                    }
                }
            }
        }
        if ($this->process != 3){
            if ($this->getPlayer() == null){
                if ($this->tip !== '熔炼已完成，等待玩家验收。')$this->tip = "错误404，找不到玩家对象。";
                if ($this->process == 4 and $this->noPlayer == false){
                    /** @var FloatItem $entity */
                    foreach ($this->itemEntities as $entity)
                        $entity->setSpeed(0);
                }
                $this->noPlayer = true;
                return true;
            }
        }
        if ($this->isForcedCooling()){
            if ($this->age % 10){
                if ($this->coolant){
                    $this->temperature -= 10;
                }else{
                    /** @var \pocketmine\tile\Chest $tile */
                    $tile = $this->getLevel()->getTile($this->getChest());
                    if(Open::removeItem(null, ['材料', '熔炼冷却液',1], $tile->getInventory())){
                        $this->temperature -= 10;
                        $this->coolant = true;
                    }else{
                        $this->temperature -= 1;
                    }
                }
                $this->updateFloatText();
            }
            if ($this->temperature <= 20){
                $this->forcedCooling = false;
                $this->coolant = false;
                $this->tip = "等待熔炼完成。";
                foreach ($this->furnaces as $furnace)
                    $this->getLevel()->setBlock($furnace, Block::get(Item::BURNING_FURNACE, $furnace->getDamage()), true);//点燃熔炉
            }
            $this->age++;
            return true;
        }
        if ($this->process == 3){
            if ($this->startTick > 8 and ($this->startTick-1 % 10 == 0 or $this->startTick % 10 == 0 or $this->startTick+1 % 10 == 0)){
                foreach ($this->furnaces as $furnace){
                    $this->spawnParticle($furnace->add(0.5, 0.8, 0.5), 8, 6);
                }
            }
//            if ($this->age % 10 == 0)$this->spawnCoarseParticle($this->getCenter()->add(0.5, 0, 0.5), 34);
        }
        switch ($this->process) {
            case 0:
                if ($this->age % 20 != 0)break;
                if ($this->age > 20*60){
                    $this->breakd = true;
                    $this->setNameTag("§l§c超时，十秒取消熔炼！");
                    return true;
                }
                /** @var \pocketmine\tile\Chest $tile */
                $tile = $this->getLevel()->getTile($this->getChest());
                if(Open::CheackItems($this->getPlayer(), self::$formulas[$this->drawingName][$this->process], $tile->getInventory())){
                    $this->process++;
                    foreach ($tile->getInventory()->getContents() as $item){
                        $this->getInventory()->addItem($item);
                    }
                    $tile->getInventory()->setContents([]);
                }
                $this->updateFloatText();
            break;
            case 1:
                if ($this->age % 20 != 0)break;
                if ($this->age > 20*180){
                    /** @var \pocketmine\tile\Chest $tile */
                    $tile = $this->getLevel()->getTile($this->getChest());
                    $tile->getInventory()->setContents($this->getInventory()->getContents());
                    $this->breakd = true;
                    $this->setNameTag("§l§c超时，十秒取消熔炼！");
                    return true;
                }
                if(Open::CheackItems($this->getPlayer(), self::$formulas[$this->drawingName][$this->process], $this->getInventory())){
                    $contents = $this->getInventory()->getContents();
                    if (!Open::removeItems($this->getPlayer(), self::$formulas[$this->drawingName][$this->process], $this->getInventory())){
                        $this->failure = true;
                    }
                    if (!Open::removeItems($this->getPlayer(), self::$formulas[$this->drawingName][$this->process-1], $this->getInventory())){
                        $this->failure = true;
                    }
                    $this->process++;
                    if (count($this->getInventory()->getContents()) > 0){
                        $this->failure = true;
                    }
                    $this->getInventory()->setContents($contents);
                }
                $this->updateFloatText();
            break;
            case 2:
                if ($this->age % 20 != 0)break;
                if ($this->age > 20*360){
                    /** @var \pocketmine\tile\Chest $tile */
                    $tile = $this->getLevel()->getTile($this->getChest());
                    $tile->getInventory()->setContents($this->getInventory()->getContents());
                    $this->breakd = true;
                    $this->setNameTag("§l§c超时，十秒取消熔炼！");
                    return true;
                }
                $satisfy = true;
                foreach ($this->furnaces as $furnace){
                    /** @var  $tile \pocketmine\tile\Furnace */
                    $tile = $this->getLevel()->getTile($furnace);
                    $fuel = $tile->getInventory()->getFuel();
                    if ($fuel->getId()!=Item::AIR){
                        if($fuel instanceof Material){
                            if(!\LTItem\Main::getInstance()->isEquals($fuel, self::$formulas[$this->drawingName][$this->process][0])){
                                $satisfy = false;
                            }
                            if ($fuel->getCount() < self::$formulas[$this->drawingName][$this->process][0][2]){
                                $satisfy = false;
                            }
                        }else{
                            $satisfy = false;
                        }
                    }else{
                        $satisfy = false;
                    }
                }
                if ($satisfy){
                    foreach ($this->furnaces as $furnace) {
                        /** @var  $tile \pocketmine\tile\Furnace */
                        $tile = $this->getLevel()->getTile($furnace);
                        $fuel = $tile->getInventory()->getFuel();
                        /** @var int $count */
                        $count = self::$formulas[$this->drawingName][$this->process][0][2];
                        $fuel->setCount($fuel->getCount() - $count);
                        if ($fuel->getCount() <=0 )$fuel = Item::get(0);
                        $tile->getInventory()->setFuel($fuel);
                    }
                    $this->process++;
                    $this->tip = "等待熔炼完成。";
                }
                $this->updateFloatText();
            break;
            case 3:
                if ($this->age % 20 != 0)break;
                if ($this->startTick == 0){
                   $this->spawnItemEntity();
                    foreach ($this->furnaces as $furnace)
                        $this->getLevel()->setBlock($furnace, Block::get(Item::BURNING_FURNACE, $furnace->getDamage()), true);//点燃熔炉
                }
                $this->startTick++;
                $this->temperature += mt_rand(0, 100) / 5;
//                $this->temperature += 80;
                if ($this->temperature > 1000){
                    $this->forcedCooling = true;
                    $this->tip = "温度过高，强制冷却中。";
                    foreach ($this->furnaces as $furnace)
                        $this->getLevel()->setBlock($furnace, Block::get(Item::FURNACE, $furnace->getDamage()), true);//熄灭熔炉
                }
                if ($this->startTick % 10 == 0) {
                    foreach ($this->anvils as $anvil) {
                        $this->getLevel()->addSound(new AnvilUseSound($anvil));//铁砧声音
                    }
                }
                if ($this->startTick > self::$times[$this->drawingName]){
                    /** @var \pocketmine\tile\ItemFrame $tile */
                    $tile = $this->getLevel()->getTile($this->getItemFramePosition());
                    $tile->setItem(Item::get(0));
                    $this->startTick = 0;
                    $this->process++;
                    $this->temperature = 20.00;
                    foreach ($this->furnaces as $furnace)
                        $this->getLevel()->setBlock($furnace, Block::get(Item::FURNACE, $furnace->getDamage()), true);//熄灭熔炉
                    if ($this->getPlayer() == null){
                        $this->tip = "熔炼已完成，等待玩家验收。";
                        /** @var FloatItem $entity */
                        foreach ($this->itemEntities as $entity)
                            $entity->setSpeed(0);
                    }else{
                        $this->tip = "熔炼已完成，正在合成。";
                        /** @var FloatItem $entity */
                        foreach ($this->itemEntities as $entity)
                            $entity->setSpeed(2);
                    }
                }
                $this->updateFloatText();
            break;
            case 4:
                if ($this->noPlayer){
                    /** @var FloatItem $entity */
                    foreach ($this->itemEntities as $entity)
                        $entity->setSpeed($this->startTick >= 80?4:2);
                    $this->noPlayer = false;
                    $this->tip = "熔炼已完成，正在合成。";
                    $this->updateFloatText();
                }
                if ($this->getPlayer()->distance($this) > 5){
                    $this->tip = "等待玩家靠近。";
                    $this->updateFloatText();
                    return true;
                }else{
                    if ($this->tip == '等待玩家靠近。'){
                        $this->tip = "熔炼已完成，正在合成。";
                        $this->updateFloatText();
                    }
                }
                if ($this->startTick > 200){
                    if ($this->failure){
                        $this->tip = "合成失败，可能配方存在其他物品。";
                        $this->breakd = true;
                        $this->getInventory()->setContents([]);
                    }else{
                        $item = $this->getDrawings()->onSynthetic($this->getPlayer());
                        $entity = $this->getLevel()->dropItem($this->getCenter()->getSide(Vector3::SIDE_UP)->add(0.5,0, 0.5), $item, new Vector3(0, 0, 0), 10, false);
                        $entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, true);
                        $entity->spawnToAll();
                        $this->tip = "合成完成。";
                        $this->breakd = true;
                        $this->getInventory()->setContents([]);
                    }
                    $this->updateFloatText();
                    /** @var FloatItem $entity */
                    foreach ($this->itemEntities as $entity)
                        $entity->close();
                }elseif($this->startTick <= 50){
                    if ($this->startTick % 5 == 0){
                        $pos = $this->center->add(0.5, 1.1, 0.5);
                        for ($i = 0; $i <= 10; $i++){
                            $x = mt_rand(3,5);
                            $z = mt_rand(3,5);
                            if (mt_rand(0, 1))$x = 0-$x;
                            if (mt_rand(0, 1))$z = 0-$z;
                            $entity = new FlyBubble($this->getLevel(), new CompoundTag("", [
                                "Pos" => new ListTag("Pos", [
                                    new DoubleTag("", $pos->getX() + $x),
                                    new DoubleTag("", $pos->getY()+ mt_rand(0,4)),
                                    new DoubleTag("", $pos->getZ()+ $z)
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
                            $entity->setTarget($pos);
                            $entity->spawnToAll();
                        }
                    }
                }elseif ($this->startTick == 80){
                    /** @var FloatItem $entity */
                    foreach ($this->itemEntities as $entity)
                        $entity->setSpeed(4);
                }
                $this->startTick++;
            break;
        }
        $this->age++;
        return true;
    }

    /**
     * 粒子效果 粗粒子效果 四个
     * @param Vector3 $center
     * @param int $id
     */
    public function spawnCoarseParticle(Vector3 $center, int $id){
        $z = ($this->particleHigh % 20) / 10;
        if ($this->particleHigh % 40 > 20){
            $z = 2-$z;
        }
        $center->y = $center->y + $z;
        for ($i = 0; $i < 360; $i++){
            if ($i % 10 == 0){
                $a=$center->x+1.5*cos($i*3.14/90) ;
                $b=$center->z+1.5*sin($i*3.14/90) ;
                $this->getLevel()->addParticle(new GenericParticle(new Vector3($a+0.1, $center->y + 0.1, $b), $id));
                $this->getLevel()->addParticle(new GenericParticle(new Vector3($a, $center->y + 0.1,$b+0.1), $id));
                $this->getLevel()->addParticle(new GenericParticle(new Vector3($a, $center->y,$b+0.1), $id));
                $this->getLevel()->addParticle(new GenericParticle(new Vector3($a+0.1,$center->y, $b), $id));
            }
        }
        $this->particleHigh++;
    }

    /**
     * 粒子效果
     * @param Vector3 $center
     * @param int $id
     * @param int $count 循环次数
     */
    public function spawnParticle(Vector3 $center, int $id, int $count = 4){
        for ($ii = 0; $ii < $count; $ii++){
            $a=$center->x+1.5*cos($this->progress*3.14/90) ;
            $b=$center->z+1.5*sin($this->progress*3.14/90) ;
            $this->getLevel()->addParticle(new GenericParticle(new Vector3($a, $center->y ,$b), $id));
            $this->progress++;
        }
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player)
    {
        if(!isset($this->hasSpawned[$player->getLoaderId()]) and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
            $pk = new \pocketmine\network\protocol\AddEntityPacket();
            $pk->eid = $this->getId();
            $pk->type = Slime::NETWORK_ID;;
            $pk->x = $this->x;
            $pk->y = $this->y + 1;
            $pk->z = $this->z;
            $pk->speedX = 0;
            $pk->speedY = 0;
            $pk->speedZ = 0;
            $pk->metadata = $this->dataProperties;
            $this->hasSpawned[$player->getLoaderId()] = $player;
            $player->dataPacket($pk);
        }
    }
    /**
     * 检查多方块结构
     * 这个玩法最难实现的功能实现了还有啥呢。
     * @param ItemFrame $position 物品展示框坐标
     * TODO: 优化 它！
     * @return array
     */
    public static function checkBlocks(ItemFrame $position) :array {
        $blocks = [];
        //先检查下面是否为岩浆
        if (!($position->getSide(Vector3::SIDE_DOWN) instanceof Lava)){
            $bt = new Lava();
            $pos = $position->getSide(Vector3::SIDE_DOWN);
            $blocks[] = $bt->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
        }
        //检查上方是否为箱子 并且朝向入口
        $sides = [
            0 => 4,
            1 => 5,
            2 => 2,
            3 => 3
        ];
        $faces = [
            2 => 3,
            3 => 2,
            4 => 1,
            5 => 0
        ];
        $reverses = [//反向
            2 => 3,
            3 => 2,
            4 => 5,
            5 => 4
        ];
        $Treverses = [//反向
            2 => 1,
            3 => 3,
            4 => 0,
            5 => 2
        ];
        $center = $position->getSide($sides[$position->getDamage()]);
        $pSide = $sides[$position->getDamage()];//物品展示框贴的面
        $entrance = $pSide % 2 == 0 ? $pSide + 1 : $pSide -1;//如果是偶数 应对方向应该+1 如果是奇数 应该-1
        if (!($center instanceof Glass)){
            $bt = new Glass();
            $blocks[] = $bt->setComponents($center->getX(), $center->getY(), $center->getZ());
        }
        if (!(($block = $center->getSide(Vector3::SIDE_UP, 3)) instanceof Chest) or $block->getDamage() != $entrance){
            $bt = new Chest($entrance);
            $pos = $center->getSide(Vector3::SIDE_UP, 3);
            $blocks[] = $bt->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
        }else{
            if ($faces[$block->getDamage()]!=$position->getDamage()){//如果是箱子检查朝向是否为入口
                $bt = new Chest($faces[$position->getDamage()]);
                $pos = $center->getSide(Vector3::SIDE_UP, 3);
                $blocks[] = $bt->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
            }
        }
        //四个面 分别为 2 3 4 5(北 南 西 东)     1和2位上下
        for ($side = 2; $side < 6; $side++){
            if ($side != $entrance){
                $furnace = $center->getSide($side, 4);//$b1 应该为熔炉
                $anvil = $furnace->getSide(Vector3::SIDE_UP);//$b2 应该为铁砧
            }
            $stones = [];//应该是石头的所有坐标
            $orSo = []; //左右
            switch($side){
                case Vector3::SIDE_NORTH://北
                case Vector3::SIDE_SOUTH://南
                    $orSo = [Vector3::SIDE_WEST, Vector3::SIDE_EAST];
                break;
                case Vector3::SIDE_WEST://西
                case Vector3::SIDE_EAST://东
                    $orSo = [Vector3::SIDE_NORTH, Vector3::SIDE_SOUTH];
                break;
            }
            foreach ($orSo as $s){//查找熔炉的左右
                if ($side != $entrance)$stones[] = $furnace->getSide($s);//应该为石头
                $tmpBlock = $center->getSide($side, 3);//熔炉前面的方块
                $stones[] = $bottom = $tmpBlock->getSide($s, 2);//底部 往上两个应该为石头
                $stones[] = $bottom->getSide(Vector3::SIDE_UP);
                if ($side != $entrance)$stones[] = $bottom->getSide(Vector3::SIDE_UP, 2);
            }
            if ($side != $entrance and ((!($furnace instanceof Furnace) and !($furnace instanceof BurningFurnace)) or $furnace->getDamage() != $reverses[$side])){
                $bt = new Furnace($reverses[$side]);
                $blocks[] = $bt->setComponents($furnace->getX(), $furnace->getY(), $furnace->getZ());
            }
            if($side != $entrance){
                if (!($anvil instanceof Anvil)){
                    $bt = new Anvil($Treverses[$side]);
                    $blocks[] = $bt->setComponents($anvil->getX(), $anvil->getY(), $anvil->getZ());
                }elseif ($Treverses[$side] == 0 or $Treverses[$side] == 2){
                    if(!in_array($anvil->getDamage(), [0, 2])){
                        $bt = new Anvil($Treverses[$side]);
                        $blocks[] = $bt->setComponents($anvil->getX(), $anvil->getY(), $anvil->getZ());
                    }
                }else{
                    if(!in_array($anvil->getDamage(), [1, 3])){
                        $bt = new Anvil($Treverses[$side]);
                        $blocks[] = $bt->setComponents($anvil->getX(), $anvil->getY(), $anvil->getZ());
                    }
                }
            }
            foreach ($stones as $b){
                if (!($b instanceof StoneBricks) or $b->getDamage() != 3){
                    $bt = new StoneBricks(3);
                    $blocks[] = $bt->setComponents($b->getX(), $b->getY(), $b->getZ());
                }
            }
        }
        return $blocks;
    }


    /**
     * 保存熔炼坛实体在地图
     * 这个是我最不想写的
     */
    public function saveNBT()
    {
        if ($this->breakd){
            return;
        }
        if ($this->process==0) {
            return;
        }
        parent::saveNBT();
        $this->namedtag->process = new ShortTag('process', $this->process);//进度
        $this->namedtag->temperature = new FloatTag('temperature', $this->temperature);//温度
        $this->namedtag->stable = new ShortTag('stable', $this->stable);//稳定性
        $this->namedtag->drawingName = new StringTag('drawingName', $this->drawingName);//图纸名字
        $this->namedtag->drawings = $this->drawings->nbtSerialize(-1, "drawings");//图纸
        $this->namedtag->tip = new StringTag('tip', $this->tip);//提示
        $this->namedtag->chest = new ListTag("chest", [
            new DoubleTag(0, $this->chest->x),
            new DoubleTag(1, $this->chest->y),
            new DoubleTag(2, $this->chest->z)
        ]);//箱子坐标
        $this->namedtag->center = new ListTag("center", [
            new DoubleTag(0, $this->center->x),
            new DoubleTag(1, $this->center->y),
            new DoubleTag(2, $this->center->z)
        ]);//玻璃坐标
        $this->namedtag->itemFramePosition = new ListTag("itemFramePosition", [
            new DoubleTag(0, $this->itemFramePosition->x),
            new DoubleTag(1, $this->itemFramePosition->y),
            new DoubleTag(2, $this->itemFramePosition->z)
        ]);//物品展示框坐标
        $this->namedtag->player = new StringTag('player', $this->getPlayerName());//玩家
        $this->namedtag->startTick = new IntTag('startTick', $this->startTick);//当前进度的进度


        $this->namedtag->inventory = new ListTag('inventory', []);
        $this->namedtag->inventory->setTagType(NBT::TAG_Compound);
        if($this->inventory !== null){
            for($slot = 0; $slot < $this->inventory->getSize(); $slot++){
                if(($item = $this->inventory->getItem($slot)) instanceof Item){
                    $this->namedtag->inventory[$slot] = $item->nbtSerialize($slot);
                }
            }
        }
        $this->namedtag->failure = new ShortTag('failure', $this->failure);//是否失败
        $this->namedtag->forcedCooling = new ShortTag('forcedCooling', $this->forcedCooling);//强制冷却
        $this->namedtag->noPlayer = new ShortTag('noPlayer', $this->noPlayer);//没玩家
    }

    /**
     * 初始化实体
     */
    protected function initEntity()
    {
        try {
            parent::initEntity();
            if (!isset($this->namedtag['process']))return;
            $this->process = $this->namedtag['process'];//进度
            $this->temperature = round((float)$this->namedtag['temperature'], 2);//温度
            $this->stable = $this->namedtag['stable'];//稳定
            $this->drawingName = $this->namedtag['drawingName'];//图纸名字
            $this->drawings = Item::nbtDeserialize($this->namedtag['drawings']);//图纸对象
            $this->tip = $this->namedtag['tip'];//图纸对象
            $this->chest = $this->getLevel()->getBlock(new Position(
                $this->namedtag['chest'][0],
                $this->namedtag['chest'][1],
                $this->namedtag['chest'][2],
                $this->getLevel()
            ));//箱子
            $this->center = $this->getLevel()->getBlock(new Position(
                $this->namedtag['center'][0],
                $this->namedtag['center'][1],
                $this->namedtag['center'][2],
                $this->getLevel()
            ));//玻璃
            $this->itemFramePosition = $this->getLevel()->getBlock(new Position(
                $this->namedtag['itemFramePosition'][0],
                $this->namedtag['itemFramePosition'][1],
                $this->namedtag['itemFramePosition'][2],
                $this->getLevel()
            ));//物品展示框
            $this->anvils = self::getAnvils($this->itemFramePosition);
            $this->furnaces = self::getFurnaces($this->itemFramePosition);
            $this->playerName = $this->namedtag['player'];//玩家
            $this->startTick = $this->namedtag['startTick'];//tick
            $contents =  $this->namedtag['inventory'];
            $this->inventory = (new Class($this, InventoryType::get(InventoryType::CHEST)) extends BaseInventory{});
            $this->updateFloatText();
            if($contents !== null){
                if($contents instanceof ListTag){ //Saved data to be loaded into the inventory
                    foreach($contents as $item){
                        $this->getInventory()->setItem($item["Slot"], Item::nbtDeserialize($item));
                    }
                }else{
                    throw new \InvalidArgumentException("Expecting ListTag, received " . gettype($contents));
                }
            }
            $this->failure = (bool)$this->namedtag['failure'];//失败
            $this->forcedCooling = (bool)$this->namedtag['forcedCooling'];//强制冷却
            $this->noPlayer = (bool)$this->namedtag['noPlayer'];//没玩家
            $this->spawnItemEntity();
            /** @var FloatItem $entity */
            if ($this->process > 2){
                if ($this->startTick > self::$times[$this->drawingName]){
                    foreach ($this->itemEntities as $entity){
                        $entity->setSpeed(2);
                    }
                }elseif($this->process > 3 and $this->startTick >= 80){
                    foreach ($this->itemEntities as $entity){
                        $entity->setSpeed(4);
                    }
                }
            }
        }catch (\Throwable $e){
            $this->close();
            $this->getServer()->getLogger()->critical($e->getLine());
            $this->getServer()->getLogger()->critical($e->getFile());
            $this->getServer()->getLogger()->critical($e->getMessage());
        }
    }

    /**
     * 获取三个熔炉的坐标
     * @param ItemFrame $position
     * @return array
     */
    public static function getFurnaces(ItemFrame $position) :array {
        $blocks = [];
        //检查上方是否为箱子 并且朝向入口
        $sides = [
            0 => 4,
            1 => 5,
            2 => 2,
            3 => 3
        ];
        $center = $position->getSide($sides[$position->getDamage()]);//中心 玻璃
        $pSide = $sides[$position->getDamage()];//物品展示框贴的面
        $entrance = $pSide % 2 == 0 ? $pSide + 1 : $pSide -1;//如果是偶数 应对方向应该+1 如果是奇数 应该-1
        //四个面 分别为 2 3 4 5(北 南 西 东)     1和2位上下
        for ($side = 2; $side < 6; $side++){
            if ($side != $entrance){
                $blocks[] = $center->getSide($side, 4);//应该为熔炉
            }
        }
        return $blocks;
    }

    /**
     * 获取三个铁砧的坐标
     * @param ItemFrame $position
     * @return array
     */
    public static function getAnvils(ItemFrame $position) :array {
        $blocks = [];
        //检查上方是否为箱子 并且朝向入口
        $sides = [
            0 => 4,
            1 => 5,
            2 => 2,
            3 => 3
        ];
        $center = $position->getSide($sides[$position->getDamage()]);//中心 玻璃
        $pSide = $sides[$position->getDamage()];//物品展示框贴的面
        $entrance = $pSide % 2 == 0 ? $pSide + 1 : $pSide -1;//如果是偶数 应对方向应该+1 如果是奇数 应该-1
        //四个面 分别为 2 3 4 5(北 南 西 东)     1和2位上下
        for ($side = 2; $side < 6; $side++){
            if ($side != $entrance){
                $blocks[] = $center->getSide($side, 4)->getSide(Vector3::SIDE_UP);//$b2 应该为铁砧
            }
        }
        return $blocks;
    }

    /**
     * @return BaseInventory
     */
    public function getInventory()
    {
        return $this->inventory;
    }
}
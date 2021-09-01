<?php
namespace LTEntity;

use LTEntity\entity\Boss\Prisoners;
use LTEntity\entity\Boss\SkillsEntity\Sakura;
use LTEntity\entity\Gaia\GaiaCrystal;
use LTEntity\entity\Gaia\GaiaGuardians;
use LTEntity\entity\Guide\Trident;
use LTEntity\entity\Mana\FairyGate;
use LTEntity\entity\Process\Fusion;
use LTItem\SpecialItems\Armor\ManaArmor;
use LTItem\SpecialItems\Weapon;
use LTPet\Commands\RecomeAll;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\entity\Item as entityItem;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\event\entity\{
    EntityDeathEvent,
    EntityDamageEvent,
    EntityDamageByEntityEvent,
    EntityCombustByEntityEvent,
    EntityRegainHealthEvent};
use pocketmine\nbt\tag\ {CompoundTag, DoubleTag, FloatTag, ListTag, StringTag};

use LTEntity\entity\BaseEntity;
use LTCraft\FloatingText;

use LTEntity\entity\projectile\ {
    ABlazeFireball,
    ABlueWitherSkull,
    ADragonFireBall,
    ABatSkull,
    AFalseArrow,
    AEvokerFangs,
    AEnderCrystal,
    AShulkerSkull
};

use LTEntity\entity\monster\flying\ {
    ABat,
    ABlaze,
    AGhast,
    AWitherBoss,
    AEnderDragon
};

use LTEntity\entity\monster\walking\ {
    AChicken,
    ARabbit,
    ASheep,
    ACow,
    AMooshroom,
    AOcelot,
    APig,
    ASilverfish,
    AWolf,
    ACaveSpider,
    ACreeper,
    AEnderman,
    ASpider,
    AHorse,
    ADonkey,
    ASkeletonHorse,
    AZombieHorse,
    AMule,
    ASquid,
    AVillager,
    AZombieVillager,
    AWitch,
    ASnowGolem,
    AIronGolem,
    AGuardian,
    Aelderguardian,
    APolarBear,
    AEndermite,
    AShulker,
    ASlime,
    AEvoker,
    ALavaSlime
};

use LTEntity\entity\monster\walking\EMods\ {
    AZombie,
    ASkeleton,
    AStray,
    AHusk,
    APigZombie,
    AWitherSkeleton,
    ANPC
};

use LTEntity\Utils\Converter;

use LTGrade\Main as LTGrade;
use LTGrade\FloatingText as SimpleFloatingText;
use LTItem\Main as LTItem;
use LTItem\LTItem as LTI;
class Main extends PluginBase implements Listener
{

    public $spawnTmp = [];
    public $ExpRanking = null;
    public $killCount = [];
    public $lastKill = [];
    public $errorCount = [];
    public $gaia = [];
    public array $playerGates = [];
    public array $gates = [];
    public $skills = [];
    public $fusion = [];
    /** @var array */
    public array $EnConfig;
    /** @var Config */
    public Config $WeeksExp;
    /** @var Main */
    private static ?Main $instance = null;
    private int $tick = 0;
    /**
     * @var array 扭曲值
     */
    public array $distorted = [];
    public Config $RPGSpawn;
    private Config $gateConfig;

    /**
     * @return Main
     */
    public static function getInstance(){
        return self::$instance;
    }
    public static function getErrorCount($name){
        return self::$instance->errorCount[$name]??0;
    }
    public static function addErrorCount($player){
        $name = $player->getName();
        if(isset(self::$instance->errorCount[$name])){
            self::$instance->errorCount[$name] += 1;
        }else{
            self::$instance->errorCount[$name] = 1;
        }
        if(self::$instance->errorCount[$name]>5)$player->kick('§c验证失败次数过多！');
    }
    public static function resetErrorCount($name){
        self::$instance->errorCount[$name]=0;
    }
    public static function getCount($name){
        return self::$instance->killCount[$name]??0;
    }
    public static function addCount($player){
        $name = $player->getName();
        if(isset(self::$instance->killCount[$name])){
            self::$instance->killCount[$name] += 1;
        }else{
            self::$instance->killCount[$name] = 1;
        }
        if(self::$instance->killCount[$name]>7)$player->sendMessage(('§l§c警告:你的击杀次数剩余'. (10-self::$instance->killCount[$name]).'次，请打开菜单验证在线状态来重置！'));
    }
    public static function resetCount($name){
        self::$instance->killCount[$name]=0;
    }
    public function onEnable()
    {
        $classes = [
            ABlazeFireball::class,
            ABlueWitherSkull::class,
            ADragonFireBall::class,
            ABatSkull::class,
            AShulkerSkull::class,
            ABat::class,
            ABlaze::class,
            AGhast::class,
            AWitherBoss::class,
            AEnderDragon::class,
            AChicken::class,
            ARabbit::class,
            ASheep::class,
            ACow::class,
            AMooshroom::class,
            AEvoker::class,
            AOcelot::class,
            APig::class,
            ASilverfish::class,
            AWolf::class,
            ACaveSpider::class,
            ACreeper::class,
            AEnderman::class,
            ASpider::class,
            AHorse::class,
            ADonkey::class,
            ASkeletonHorse::class,
            AZombieHorse::class,
            AMule::class,
            ASquid::class,
            AVillager::class,
            AVillager::class,
            AZombieVillager::class,
            ASnowGolem::class,
            AIronGolem::class,
            AGuardian::class,
            Aelderguardian::class,
            APolarBear::class,
            AEndermite::class,
            AShulker::class,
            ASlime::class,
            ALavaSlime::class,
            AEnderCrystal::class,
            AFalseArrow::class,
            AZombie::class,
            ASkeleton::class,
            AStray::class,
            AHusk::class,
            APigZombie::class,
            AWitherSkeleton::class,
            AEvokerFangs::class,
            GaiaGuardians::class,
            GaiaCrystal::class,
            Fusion::class,
            Trident::class,
            Prisoners::class,
            FairyGate::class,
            ANPC::class
        ];
        foreach($classes as $name)
            Entity::registerEntity($name, true);
        self::$instance = $this;
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder().'/skins');
        @mkdir($this->getDataFolder().'/skins/cache');
        $Config = new Config($this->getDataFolder().'Config.yml', Config::YAML, []);
        $this->WeeksExp = new Config($this->getDataFolder().'WeeksExp.yml', Config::YAML, []);
        $this->RPGSpawn = new Config($this->getDataFolder().'RPGSpawn.yml', Config::YAML, []);
        $this->gateConfig = new Config($this->getDataFolder().'Gates.yml', Config::YAML, []);
        $this->EnConfig = $this->RPGSpawn->getAll();
        $cmd = new Commands($this);
        $this->getCommand('ma')->setExecutor($cmd);
        $this->getCommand('lf')->setExecutor($cmd);
        /*
            onRefresh 方法在LTPopuo中调用了
        */
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function(){
            foreach ($this->gateConfig->getAll() as $xyz){
                $info = explode(':', $xyz);
                $level = Server::getInstance()->getLevelByName($info[0]);
                if ($level === null)continue;
                $level->loadChunk($info[1] >> 4, $info[3] >> 4);
            }
        }, []), 20);
    }
    public function onDisable(){
        // $this->Data->save(false);
        if(isset($this->WeeksExp))$this->WeeksExp->save(false);
        if(isset($this->gateConfig)){
            $this->gateConfig->setAll([]);
            /** @var FairyGate $gate */
            foreach ($this->gates as $gate){
                $this->gateConfig->set($gate->getId(), $gate->getLevel()->getName().":".$gate->getX().":".$gate->getY().":".$gate->getZ());
            }
            $this->gateConfig->save();
        }
    }
    public function killAll(){

    }
    public function updateTop(){
        if($this->ExpRanking instanceof FloatingText){
            $Data = $this->WeeksExp->getAll();
            arsort($Data);
            $n=1;
            $text='§l§e周经验排行榜'."\n";
            foreach($Data as $name => $exp){
                if($name=='配置日期')continue;
                $text.='§a'.$n .'#玩家名字'.$name .'§d经验:'.$exp ."\n";
                if(++$n==11)break;
            }
            $this->ExpRanking->updateAll($text.'§3周经验会在周一进行结算 排行越高奖励越高');
        }
    }
    public function setExpRanking(\LTCraft\FloatingText $FloatingText){
        $this->ExpRanking=$FloatingText;
        $this->updateTop();
    }
    public function reloadConfig()
    {
        $this->RPGSpawn->reload();
        $this->EnConfig = $this->RPGSpawn->getAll();
    }
    public function getSkin($skinName)
    {
        return Converter::getPngSkin($this->getDataFolder().'/skins/'.$skinName.'.png', true);
    }
    public function onRefresh()
    {
        $this->tick++;
        if(count($this->getServer()->getOnlinePlayers()) == 0)return;
        if ($this->tick % 6 == 0){
            foreach ($this->distorted as $name => $value){
                $player = $this->server->getPlayerExact($name);
                if ($player->getLevel()->getName() == 'f9')continue;
                $this->distorted[$name]--;
            }
        }
        $level = $this->getServer()->getLevelByName('f6');
        if($level){
            foreach($level->getPlayers() as $player){
                if(mt_rand(1,300)<=1 and $player->isSurvival()){
                    $data=$this->EnConfig['傀儡虫'];
                    $nbt = new CompoundTag;
                    $nbt->Pos = new ListTag('Pos', [
                        new DoubleTag('', $player->x+mt_rand(-5, 5)),
                        new DoubleTag('', $player->y + 0.5),
                        new DoubleTag('', $player->z+mt_rand(-5, 5))
                    ]);
                    $nbt->Speed = new DoubleTag('Speed', 1.8);
                    for($i = 0; $i < 3; $i++) {
                        $pk = Entity::createEntity('ASilverfish', $level, $nbt);
                        /** @var BaseEntity $pk */
                        $pk->enConfig = $data;
                        $pk->spawnToAll();
                        $pk->initThis();
                        $pk->setTarget($player);
                    }
                    break;
                }
            }
        }
        $level = $this->getServer()->getLevelByName('f9');
        if ($level){
            foreach ($this->distorted as $name => $value){
                $player = $this->server->getPlayerExact($name);
                if ($value >= 100){
                    if (!mt_rand(0, 349 - ((int)$value / 2))){
                        $data=$this->EnConfig['失落领主傀儡'];
                        $nbt = new CompoundTag;
                        $nbt->Pos = new ListTag('Pos', [
                            new DoubleTag('', $player->x+mt_rand(-3, 3)),
                            new DoubleTag('', $player->y + 1.8),
                            new DoubleTag('', $player->z+mt_rand(-3, 3))
                        ]);
                        $nbt->Speed = new DoubleTag('Speed', 1.8);
                        $skin=$this->getSkin($data['皮肤']);
                        $nbt->Skin = new CompoundTag('Skin', ['Data' => new StringTag('Data', $skin), 'Name' => new StringTag('Name', $data['皮肤ID'])]);
                        /** @var BaseEntity $pk */
                        $pk = Entity::createEntity('ANPC', $level, $nbt);
                        $pk->enConfig = $data;
                        $pk->spawnToAll();
                        $pk->initThis();
                        $pk->setTarget($player);
                        $number = mt_rand(10, 30);
                        $this->distorted[$name] -= $number;
                        $player->sendMessage('§e你失去了'.$number.'点扭曲值！');
                    }
                }
            }
        }
        foreach($this->EnConfig as $vid => $data) {
            if(strpos($vid, '傀儡') !== false)continue;
            $l = $this->getServer()->getLevelByName($data['世界']);
            if(!($l instanceof Level))continue;
            $pos = new Position($data['x'], $data['y'], $data['z'],$l);
            if(!isset($this->spawnTmp[$data['刷怪点']])){
                $this->spawnTmp[$data['刷怪点']] = [
                    '数量' => 0,
                    '剩余时间' => (int)$data['刷怪时间']
                ];
                $drops='';
                foreach($data['掉落'] as $drop){
                    $dropItem = explode(':', $drop);
                    if(!isset($dropItem[3]))continue;
                    if(in_array($dropItem[0], ['材料', '近战', '远程', '通用', '盔甲']))
                        $drops.=$dropItem[0].'类型'.$dropItem[1].'×'.$dropItem[2] .' '.$dropItem[3].'%'.PHP_EOL;
                    else {
                        $item = Item::get((int)$dropItem[0], (int)$dropItem[1], (int)$dropItem[2]);
                        $drops.=$item->getName().'×'.$item->getCount() .' '.$dropItem[3].'%'.PHP_EOL;
                    }
                }
                $drops=substr($drops,0,strlen($drops)-1);
                $pDrops='';
                foreach($data['参与击杀掉落'] as $drop){
                    $dropItem = explode(':', $drop);
                    if(!isset($dropItem[3]))continue;
                    if(in_array($dropItem[0], ['材料', '近战', '远程', '通用', '盔甲']))
                        $pDrops.=$dropItem[0].'类型'.$dropItem[1].'×'.$dropItem[2] .' '.$dropItem[3].'%'.PHP_EOL;
                    else {
                        $item = Item::get((int)$dropItem[0], (int)$dropItem[1], (int)$dropItem[2]);
                        $pDrops.=$item->getName().'×'.$item->getCount() .' '.$dropItem[3].'%'.PHP_EOL;
                    }
                }
                $pDrops=substr($pDrops,0,strlen($pDrops)-1);
                /*
                $probability = 1;
                $add = $data['血量'] / 5000;
                if ($add > 29)$add = 29;
                $probability += $add;
                */
                if($data['显示'])$this->spawnTmp[$data['刷怪点']]['悬浮字'] = new FloatingText($pos,
                    '§a=============['.$data['刷怪点'].']============= '. PHP_EOL
                    .'§d名字:§3'.$data['名字'].'§d还有： @t '. PHP_EOL
                    .'§6当前怪物数量：@c/'.$data['数量'].PHP_EOL
                    //.'§c掉落Craft任意字符几率：'.$probability.'%'
                    . ($data['悬浮介绍']!=false?(PHP_EOL . '§3介绍:'.$data['悬浮介绍']):'')
                    . (count($data['掉落'])>0?(PHP_EOL .'§e击杀掉落:'.$drops):'')
                    . (count($data['参与击杀掉落'])>0?(PHP_EOL .'§a参与击杀掉落:'.$pDrops):'')
                    . ($data['团队']==true?(PHP_EOL . '§c注意！这个怪物需要多个玩家配击杀！'):'')
                    , true);
            }
            $tmp=&$this->spawnTmp[$data['刷怪点']];
            $no_player = true;
            foreach($l->getPlayers() as $player) {
                if($player->distance($pos) <=  $data['边界范围半径']) {
                    $no_player = false;
                    break;
                }
            }
            if($tmp['数量'] < (int)$data['数量']) {
                $tmp['剩余时间']--;
                if($vid==='boss' and $tmp['剩余时间']<60 and count($l->getPlayers())<=0){
                    $tmp['剩余时间']=60;
                    continue;
                }
                if($tmp['剩余时间'] <= 0 and !$no_player) {
                    $tmp['剩余时间'] = (int)$data['刷怪时间'];
                    $nbt = new CompoundTag;
                    $nbt->Pos = new ListTag('Pos', [
                        new DoubleTag('', $pos->x),
                        new DoubleTag('', $pos->y + ($data['怪物模式']==0?0:0.5)),
                        new DoubleTag('', $pos->z)
                    ]);
                    $nbt->Speed = new DoubleTag('Speed', $data['速度']);
                    if($data['类型'] == 'npc') {
                        if($data['皮肤']=='默认' or (!is_file($this->getDataFolder().'skins/'.$data['皮肤'].'.png') and !is_file($this->getDataFolder().'skins/cache/'.$data['皮肤'].'.png.cache'))){
                            $skin=$this->getSkin('默认');
                            $nbt->Skin = new CompoundTag('Skin', ['Data' => new StringTag('Data', $skin), 'Name' => new StringTag('Name', 'Standard_Custom')]);
                        }else{
                            $skin=$this->getSkin($data['皮肤']);
                            $nbt->Skin = new CompoundTag('Skin', ['Data' => new StringTag('Data', $skin), 'Name' => new StringTag('Name', $data['皮肤ID'])]);
                        }
                    }
                    $pk = Entity::createEntity(DataList::$ModName[$data['名字']]??DataList::$ModName[$data['类型']], $l, $nbt);
                    if(!($pk instanceof Entity)) {
                        $this->getLogger()->Warning($vid.'找不到实体！！');
                        continue;
                    }
                    $pk->enConfig = $data;
                    $pk->initThis();
                    $pk->spawnToAll();
                    if($pk instanceof AEnderman)$pk->Handheld(2);
                    $tmp['数量']++;
                }
            }
            foreach($l->getEntities() as $entity) {
                if($entity instanceof BaseEntity AND $entity->enConfig['刷怪点'] === $data['刷怪点']) {
                    if($no_player) {
                        if(!$entity->onPlayer and $entity->enConfig['怪物模式'] !== 0) {
                            $entity->setOnPlayer(true);
                           new SimpleFloatingText($entity->getPosition(), '§c范围内无玩家，回到出生坐标！');
                        }
                    }elseif($entity->distance($pos) >= $data['边界范围半径']) {
                        new SimpleFloatingText($entity->getPosition(), '§b怪物超过范围，拉回怪物！');
                        $entity->teleport($pos);
                    } else
                        $entity->setOnPlayer(false);
                }
            }
            if(isset($tmp['悬浮字'])) {
                if($tmp['数量'] >= $data['数量'])$time = '§6数量已达最大值';
                else
                    $time = ($tmp['剩余时间'] < 0) ? '0秒刷出' : $tmp['剩余时间'].'秒刷出';
                $tmp['悬浮字']->updateAll([$time, $tmp['数量']]);
            }
        }
    }
    public function getArmor($armorType, $type)
    {
        if($armorType <= 4)
            return DataList::$ModArmor[$armorType][$type];
        return 0;
    }
    public function onEntityDamageEvent(EntityDamageEvent $ev)
    {
        if($ev->isCancelled())return;
        $entity = $ev->getEntity();
        /** @var Fusion $f */
        if ($entity instanceof \pocketmine\entity\Item and $ev->getCause() == EntityDamageEvent::CAUSE_FIRE_TICK){
            foreach ($this->fusion as $f){
                if ($entity->getPosition()->distance($f->getItemFramePosition()->getSide(Vector3::SIDE_DOWN))<=3){
                    $f->getInventory()->addItem($entity->getItem());
                    break;
                }
            }
            $entity->close();
        }
        if(!($ev instanceof EntityDamageByEntityEvent) or $ev->getCause() == EntityDamageEvent::CAUSE_THORNS)return;
        $damager = $ev->getDamager();
        if($damager instanceof BaseEntity AND $entity instanceof Player AND $entity->getGamemode() == 0) {
            if(!isset($damager->enConfig))$damager->close();
            if(in_array($ev->getCause(), [EntityDamageEvent::CAUSE_ENTITY_EXPLOSION]))return $ev->setCancelled();
            if($ev->getCause() == EntityDamageEvent::CAUSE_PROJECTILE and $damager->enConfig['名字'] === '异界统治者')return;
            if($damager->enConfig['燃烧'] !== 0) {
                $ev = new EntityCombustByEntityEvent($damager, $entity, $damager->enConfig['燃烧']);
                Server::getInstance()->getPluginManager()->callEvent($ev);
                if(!$ev->isCancelled())
                    $entity->setOnFire($ev->getDuration());
            }
            foreach($damager->enConfig['药水'] as $potion) {
                $attribute = explode(':', $potion);
                if(!isset($attribute[2]) or $attribute[0] < 0 or $attribute[0] > 25) return;
                $effect = Effect::getEffect((int)$attribute[0])->setDuration((int)$attribute[1] * 20)->setAmplifier((int)$attribute[2]);
                $entity->addEffect($effect);
            }
        }
        unset($ev, $entity);
    }

    /**
     * 获取今天击杀次数
     * @param $name
     * @param $player
     * @return int
     */
    public static function getKillNumber($name, $player) : int {
        $all = \LTCraft\Main::getInstance()->number->get($name.'击杀次数', []);
        return $all[strtolower($player)]??0;
    }

    /**
     * 获取上次击杀时间
     * @param $name
     * @param $player
     * @return int
     */
    public static function getLastKillerTime($name, $player) : int {
        $all = \LTCraft\Main::getInstance()->number->get($name.'上次击杀时间', []);
        return $all[strtolower($player)]??0;
    }
    public function onEntityDeath(EntityDeathEvent $ev)
    {
        $entity = $ev->getEntity();
        $level = $entity->getLevel();
        if(!($entity instanceof BaseEntity) or $entity->closed)return;
        $cause = $entity->getLastDamageCause();
        if(!$cause instanceof EntityDamageByEntityEvent || !($player = $cause->getDamager()) instanceof Player || $player->getGameMode() !== 0)return;
        /** @var Player $player */
        $weapon = $player->getItemInHand();
        $player->newProgress('怪物猎人');
        if ($entity->getNormalName()=='玄之凋零'){
            $player->newProgress('见鬼去吧', '反弹抛射物打死一只怪物！', 'challenge');
        }elseif ($entity->getNormalName()=='失落领主'){
            $number = mt_rand(10,30);
            $this->distorted[strtolower($player->getName())] += $number;
            $player->sendMessage('§e你击杀了失落领主获得了'.$number.'点扭曲值！');
        }elseif($entity->getNormalName() == '囚禁者'){
            $all = \LTCraft\Main::getInstance()->number->get('囚禁者击杀次数', []);
            $all2 = \LTCraft\Main::getInstance()->number->get('囚禁者上次击杀时间', []);
            if (isset($all[strtolower($player->getName())])){
                $all[strtolower($player->getName())] = $all[strtolower($player->getName())]+1;
            }else{
                $all[strtolower($player->getName())] = 1;
            }
            $all2[strtolower($player->getName())] = time();
            \LTCraft\Main::getInstance()->number->set('囚禁者击杀次数', $all);
            \LTCraft\Main::getInstance()->number->set('囚禁者上次击杀时间', $all2);
        }
        foreach($entity->enConfig['掉落'] as $drop) {
            $dropItem = explode(':', $drop);
            if(!isset($dropItem[3]))continue;
            if(mt_rand(0, 10000) > $dropItem[3]*100*($player->getBuff()->getLucky()/100+1)){//$drop
                $player->sendMessage('§l§c抱歉，你这次击杀未获得战利品：§e'.Item::getItemName($dropItem));
                continue;
            }
            if(in_array($dropItem[0], ['材料', '近战', '远程', '通用', '盔甲'])) {
                if($dropItem[0]=='材料')
                    $item = LTItem::getInstance()->createMaterial($dropItem[1]);
                elseif(in_array($dropItem[0], ['近战', '远程', '通用']))
                    $item = LTItem::getInstance()->createWeapon($dropItem[0], $dropItem[1], $player);
                elseif($dropItem[0]=='盔甲')
                    $item = LTItem::getInstance()->createArmor($dropItem[1], $player);
                if($item == false or $item == null)continue;
                $item->setCount((int)$dropItem[2]);
            } else {
                $item = Item::get((int)$dropItem[0], (int)$dropItem[1], (int)$dropItem[2]);
                if(isset($dropItem[4]))$item->setCustomName($dropItem[4]);
            }
            $dropItem = $level->dropItem($entity, $item);
            if($dropItem instanceof entityItem)$dropItem->setOwner(strtolower($player->getName()));
        }
        if($entity->enConfig['团队']){
            $max = 0;
            foreach($entity->participants as $PPlayer){
                $max = max($max, $PPlayer[1]);
            }
            $mess='§a§l你参与击杀了'.$entity->enConfig['名字'].'获得了:';
            $len=strlen($mess);
            foreach($entity->participants as $PPlayer){
                if($PPlayer[0]->closed)continue;
                if($PPlayer[1]<$max*0.25){
                    $PPlayer[0]->sendMessage('§l§c你对团队贡献低于第一名的25%！无法获得奖励！');
                    $PPlayer[0]->sendMessage('§l§c第一名攻击次数：'.$max.' 你攻击次数：'.$PPlayer[1]);
                    continue;
                }
                if ($weapon instanceof Weapon){
                    if ($weapon->getAttribute('参与不掉落', true) and $PPlayer[0]!==$player){
                        continue;
                    }
                }
                foreach($entity->enConfig['参与击杀掉落'] as $drop) {
                    $dropItem = explode(':', $drop);
                    // var_dump($dropItem);
                    if(!isset($dropItem[3]))continue;
                    if(mt_rand(0, 10000) > $dropItem[3]*100*($PPlayer[0]->getBuff()->getLucky()/100+1)){
                        $PPlayer[0]->sendMessage('§l§c抱歉，你这次击杀未获得战利品：§e'.$drop);
                        continue;
                    }
                    if(in_array($dropItem[0], ['材料', '近战', '远程', '通用', '盔甲'])) {
                        if($dropItem[0]=='材料')
                            $item = LTItem::getInstance()->createMaterial($dropItem[1]);
                        elseif(in_array($dropItem[0], ['近战', '远程', '通用']))
                            $item = LTItem::getInstance()->createWeapon($dropItem[0], $dropItem[1], $PPlayer[0]);
                        elseif($dropItem[0]=='盔甲')
                            $item = LTItem::getInstance()->createArmor($dropItem[1], $PPlayer[0]);
                        if($item == false or $item == null)continue;
                        $item->setCount((int)$dropItem[2]);
                    } else {
                        $item = Item::get((int)$dropItem[0], (int)$dropItem[1], (int)$dropItem[2]);
                        if(isset($dropItem[4]))$item->setCustomName($dropItem[4]);
                    }
                    // $dropItem = $level->dropItem($entity, $item);
                    // if($dropItem instanceof entityItem)$dropItem->setOwner(strtolower($PPlayer->getName()));

                    if(!$PPlayer[0]->getInventory()->canAddItem($item)){
                        \LTCraft\Main::sendItem($PPlayer[0]->getName(), $dropItem);
                        $PPlayer[0]->sendMessage('§l§c哎呀,背包空间不足,无法获得§e'.Item::getItemString($item).'§c已发送到你的邮箱~');
                    }else{
                        $PPlayer[0]->getInventory()->addItem($item);
                        // $player->getServer()->BroadCastMessage('§e恭喜玩家§c'.$player->getName().'§e获得了§d'.$item->getItemString().'§e!');
                        $PPlayer[0]->sendMessage('§l§c你获得了§e'.Item::getItemString($item).'§c已发送到你的背包~');
                    }
                }
                if(($PPlayer[0]->getGrade()>50 or $PPlayer[0]->getGTo()>5)){
					if($this->lastKill[$PPlayer[0]->getName()]??'' == $entity->enConfig['刷怪点']){
						$this->lastKill[$PPlayer[0]->getName()] = $entity->enConfig['刷怪点'];
						self::addCount($PPlayer[0]);
					}else
						self::resetCount($PPlayer[0]->getName());
				}
                if($PPlayer[0]===$player)continue;
                $PPlayer[0]->getTask()->action('参与击杀怪物', $entity->enConfig['名字']);
                $tmpMess=$mess;
                if($entity->enConfig['参与经验'] >0 and $PPlayer[0] instanceof Player){
                    $PPlayer[0]->addExp($entity->enConfig['参与经验']);
                    $n=strtolower($PPlayer[0]->getName());
                    $this->WeeksExp->set($n, $this->getWeeksExp($n)+$entity->enConfig['参与经验']);
                    $tmpMess.=PHP_EOL .$entity->enConfig['参与经验'].'点经验';
                }
                if($entity->enConfig['参与橙币'] >0 and $PPlayer[0] instanceof Player){
                    $PPlayer[0]->addMoney($entity->enConfig['参与橙币'], '参与击杀怪物获得');
                    $tmpMess.=PHP_EOL .$entity->enConfig['参与橙币'].'点橙币';
                }
                if(strlen($tmpMess)>$len)$PPlayer[0]->sendCenterTip($tmpMess);
            }
        }
        /*
        $n=$level->getName(){3};
        if(is_numeric($n) and $player->getGTo()>$n-2){
            if(mt_rand(0, 100)==0 and \LTCraft\Main::calculateS($player->getName())){
                $dropItem=$level->dropItem($entity, LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
                if($dropItem instanceof entityItem)$dropItem->setOwner(strtolower($player->getName()));
            }
        }else{
            if($entity->enConfig['刷怪点']==='傀儡')return;
            if(\LTCraft\Main::calculateS($player->getName())){
                $player->getInventory()->addItem(LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
                $player->sendMessage('§a你击杀了"§e暗黑影龙§a" 已将碎片发送到你背包');
            }
        }
        */
        $mess='§a§l你击杀了'.$entity->enConfig['名字'].'获得了:';
        $player->getTask()->action('击杀怪物', $entity->enConfig['名字']);
        $len=strlen($mess);
        if($entity->enConfig['经验'] >0 and $player instanceof Player){
            $player->addExp($entity->enConfig['经验']);
            $n=strtolower($player->getName());
            $this->WeeksExp->set($n, $this->getWeeksExp($n)+$entity->enConfig['经验']);
            $mess.=PHP_EOL .$entity->enConfig['经验'].'点经验';
        }
        if($entity->enConfig['橙币'] >0 and $player instanceof Player){
            $player->addMoney($entity->enConfig['橙币'], '击杀怪物获得');
            $mess.=PHP_EOL .$entity->enConfig['橙币'].'点橙币';
        }
        if(strlen($mess)>$len)$player->sendCenterTip($mess);
		if(!$entity->enConfig['团队'] and ($player->getGrade()>50 or $player->getGTo()>5)){
			if($this->lastKill[$player->getName()]??'' == $entity->enConfig['刷怪点']){
				self::addCount($player);
			}else
				self::resetCount($player->getName());
            $this->lastKill[$player->getName()] = $entity->enConfig['刷怪点'];
		}
        // if($entity->enConfig['团队']){
        // $mess='§a§l你参与击杀了'.$entity->enConfig['名字'].'获得了:';
        // $len=strlen($mess);
        // foreach($entity->participants as $PPlayer){
        // if($PPlayer->closed)continue;
        // if($PPlayer===$player)continue;
        // $PPlayer->getTask()->action('参与击杀怪物', $entity->enConfig['名字']);
        // $tmpMess=$mess;
        // if($entity->enConfig['参与经验'] >0 and $PPlayer instanceof Player){
        // $PPlayer->addExp($entity->enConfig['参与经验']);
        // $n=strtolower($PPlayer->getName());
        // $this->WeeksExp->set($n, $this->getWeeksExp($n)+$entity->enConfig['参与经验']);
        // $tmpMess.=PHP_EOL .$entity->enConfig['参与经验'].'点经验';
        // }
        // if($entity->enConfig['参与橙币'] >0 and $PPlayer instanceof Player){
        // $PPlayer->addMoney($entity->enConfig['参与橙币'], '参与击杀怪物获得');
        // $tmpMess.=PHP_EOL .$entity->enConfig['参与橙币'].'点橙币';
        // }
        // if(strlen($tmpMess)>$len)$PPlayer->sendCenterTip($tmpMess);
        // }
        // }
        foreach($entity->enConfig['击杀药水'] as $potion) {
            $attribute = explode(':', $potion);
            if(!isset($attribute[2]) or $attribute[0] < 0 or $attribute[0] > 25) return;
            $effect = Effect::getEffect((int)$attribute[0])->setDuration((int)$attribute[1] * 20)->setAmplifier((int)$attribute[2]);
            $player->addEffect($effect);
        }
        if($entity->enConfig['死亡执行命令'] != null) {
            foreach(explode('&', $entity->enConfig['死亡执行命令']) as $c) {
                $command = explode('%', $c);
                $through = true;
                if(count($command) === 2) {
                    $chance = $command[1];
                    if(mt_rand(0, 10000) > ($chance * 100))$through = false;
                }
                if(!$through)continue;
                $player = $entity->getLastDamageCause()->getDamager();
                $this->getServer()->dispatchCommand(new \pocketmine\command\ConsoleCommandSender, str_replace('@p', $player->getName(), $command[0]));
            }
        }
    }
    public static function isBoss($name)
    {
        return in_array($name, ['噬皮者', '骑士', '觉醒法老', '生化统治者', '玄之凋零', '异界统治者', '冰之神', '怪魔制造者', '碎骨者', '灭世者', '命末神', '死神审判者', '远古巨龙', '纳什男爵', '烬灭:元素之神', '圣龙骑士', '魔雾制造者', '符文之城的统治-虐杀', '暗黑影龙']);
    }
    public static function isTBoss($name)
    {
        return in_array($name, ['异界统治者', '冰之神', '怪魔制造者', '远古巨龙', '纳什男爵', '烬灭:元素之神', '圣龙骑士', '魔雾制造者', '符文之城的统治-虐杀']);
    }
    public function getWeeksExp($name){
        $name=strtolower($name);
        return $this->WeeksExp->get($name, 0);
    }
    public function updateWeeksExpConfig(){
        if(date("w")==1){//是周一了
            $this->WeeksExp->save(false);
            copy($this->WeeksExp->getFile(), $this->getDataFolder().'WeeksExp/'.$this->WeeksExp->get('配置日期').'.yml');
            $keep=$this->WeeksExp->getAll();
            arsort($keep);//排名
            $i=1;
            foreach($keep as $name=>$count){
                if($i<4){
                    \LTCraft\Main::sendItem($name, ['材料', '觉醒石', 4-$i]);
                    \LTCraft\Main::sendItem($name, ['材料', '盔甲精髓', 4-$i]);
                }
                \LTCraft\Main::sendItem($name, ['材料', '碎片熔炼坛碎片', 11-$i]);
                if(++$i==11)break;
            }
            $this->getServer()->getScheduler()->scheduleAsyncTask(new \LTCraft\RankingsReward($keep, \LTCraft\RankingsReward::PVE_EXP));
            $this->WeeksExp->setAll([]);
        }
    }
    public function onJoinEvent(PlayerJoinEvent $e){
        $name=strtolower($e->getPlayer()->getName());
        if($this->WeeksExp->get($name)===false){
            $this->WeeksExp->set($name, 0);
        }
        $this->distorted[$name] = 0;
    }
    public function onQuitEvent(PlayerQuitEvent $e){
        $name=$e->getPlayer()->getName();
        unset($this->killCount[$name], $this->errorCount[$name], $this->distorted[strtolower($name)]);
    }
    public function onRegainHealthEvent(EntityRegainHealthEvent $e){
        if ($e->getEntity() instanceof Player){
            /** @var GaiaGuardians $entity */
            $player = $e->getEntity();
            $gaia = null;
            foreach ($this->gaia as $entity){
                /** @var GaiaGuardians $entity */
                if ($entity->getBasePos()->distance($player)<=13){
                    $hb = ($e->getAmount()/20);
                    $e->setAmount($player->getMaxHealth()*$hb);
                    return;
                }
            }
        }
    }
    public function onMoveEvent(PlayerMoveEvent $e){
        $player = $e->getPlayer();
        foreach ($this->gaia as $entity){
            /** @var GaiaGuardians $entity */
            if ($entity->getBasePos()->distance($e->getPlayer())<=13){
                if ($e->getTo()->distance($entity->getBasePos())>11.5){
                    $x=$entity->getBasePos()->x - $player->x;
                    $z=$entity->getBasePos()->z - $player->z;
                    $f = sqrt($x * $x + $z * $z);
                    $v3=new Vector3(0, 0.1, 0);
                    if($f > 0){
                        $f = 1 / $f;
                        $v3->x = $x * $f * 1.5;
                        $v3->z = $z * $f * 1.5;
                    }
                    $player->setMotion($v3);
                }
                return;
            }elseif ($entity->getBasePos()->distance($e->getPlayer())<=15){
                $x=$entity->getBasePos()->x - $player->x;
                $z=$entity->getBasePos()->z - $player->z;
                $f = sqrt($x * $x + $z * $z);
                $v3=new Vector3(0, 0.1, 0);
                if($f > 0){
                    $f = 1 / $f;
                    $v3->x = $x * $f * 2;
                    $v3->z = $z * $f * 2;
                }
                $player->setMotion($v3);
                return;
            }
        }
        if (isset($this->skills['Sakura']))foreach ($this->skills['Sakura'] as $entity){
            /** @var $entity Sakura */
            if ($entity->getOwner() instanceof Player){
                /** @var Player $owner */
                $owner = $entity->getOwner();
                if ($owner->getName() == $player->getName())continue;
            }
            /** @var Sakura $entity */
            if ($entity->getBasePos()->distance($e->getPlayer())<=15){
                if ($e->getTo()->distance($entity->getBasePos())>12.5){
                    $x=$entity->getBasePos()->x - $player->x;
                    $z=$entity->getBasePos()->z - $player->z;
                    $f = sqrt($x * $x + $z * $z);
                    $v3=new Vector3(0, 0.1, 0);
                    if($f > 0){
                        $f = 1 / $f;
                        $v3->x = $x * $f * 1.5;
                        $v3->z = $z * $f * 1.5;
                    }
                    $player->setMotion($v3);
                }
                if ($e->getTo()->distance($entity->getBasePos())<10 and $player->y - $entity->getBasePos()->getY() > 0.4){
                    $player->setMotion(new Vector3(0,  0 - ($player->y - $entity->getBasePos()->getY()), 0));
                }
                return;
            }elseif ($entity->getBasePos()->distance($e->getPlayer())<=22){
                $x=$entity->getBasePos()->x - $player->x;
                $z=$entity->getBasePos()->z - $player->z;
                $f = sqrt($x * $x + $z * $z);
                $v3=new Vector3(0, 0.1, 0);
                if($f > 0){
                    $f = 1 / $f;
                    $v3->x = $x * $f * 2;
                    $v3->z = $z * $f * 2;
                }
                $player->setMotion($v3);
                return;
            }
        }
    }
}
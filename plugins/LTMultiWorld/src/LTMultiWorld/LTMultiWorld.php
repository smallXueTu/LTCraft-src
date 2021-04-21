<?php

namespace LTMultiWorld;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\scheduler\Task;
use pocketmine\level\generator\Generator;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Server;
use LTGrade\Main as LTGrade;
use LTCraft\Main as LTCraft;

class LTMultiWorld extends PluginBase implements Listener
{
    public static $banItem = [259, 325 ,326 , 327, 291, 292, 293, 294];
    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        Generator::addGenerator(Land::class, "land");
        Generator::addGenerator(SnowLand::class, "snowland");
        Generator::addGenerator(EmptyWorld::class, "empty");
        Generator::addGenerator(WoodFlat::class, "woodflat");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args)
    {

        switch($cmd->getName()){
            /*	case 'pvp':
                    if($sender->getPVPStatus()){
                        $sender->setPVPStatus(false);
                        $sender->sendMessage('§l§a[提示]§e成功关闭PVP');
                        return true;
                    }else{
                        $sender->setPVPStatus(true);
                        $sender->sendMessage('§l§a[提示]§e成功开启PVP');
                        return true;
                    }*/
            case "setworld":
//				if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player){
//					return $sender->sendMessage('§l§a[提示]§c你没有权限执行这个命令！');
//				}
                if(isset($args[0]))
                {
                    switch($args[0])
                    {
                        case "unload":
                            if(isset($args[1]))
                            {
                                $l = $args[1];
                                if (!$this->getServer()->isLevelLoaded($l))
                                {  //如果这个世界未加载
                                    $sender->sendMessage('§c[LTMultiWorld] 地图 $l 未被加载 , 无法卸载');
                                }
                                else
                                {
                                    $level = $this->getServer()->getLevelbyName($l);
                                    $ok = $this->getServer()->unloadLevel($level);
                                    if($ok !== true)
                                    {
                                        $sender->sendMessage("§c[LTMultiWorld] 卸载地图 $l 失败 ！ ");
                                    }
                                    else
                                    {
                                        $sender->sendMessage("§a[LTMultiWorld] 地图 $l 已被成功卸载 ！ ");
                                    }
                                }
                            }
                            else
                            {
                                $sender->sendMessage("§c[LTMultiWorld] 用法： /setworld unload [地图名]");
                            }
                            return true;
                        case "load":
                            if(isset($args[1]))
                            {
                                $level = $this->getServer()->getDefaultLevel();
                                $path = $level->getFolderName();
                                $p1 = dirname($path);
                                $p2 = $p1."/worlds/";
                                $path = $p2;
                                $l = $args[1];
                                if ($this->getServer()->isLevelLoaded($l))
                                {  //如果这个世界已加载
                                    $sender->sendMessage("§c[LTMultiWorld] 地图 ".$args[1]." 已被加载 , 无法再次加载" );
                                }
                                elseif (is_dir($path.$l))
                                {
                                    $sender->sendMessage("§b[LTMultiWorld] 正在加载地图 ".$args[1]."." );
                                    $this->getServer()->generateLevel($l);
                                    $ok = $this->getServer()->loadLevel($l);
                                    if ($ok === false)
                                    {
                                        $sender->sendMessage("§c[LTMultiWorld] 地图 ".$args[1]." 加载失败");
                                    }
                                    else
                                    {
                                        $sender->sendMessage("§c[LTMultiWorld] 地图 ".$args[1]." 加载成功");
                                    }
                                }
                                else
                                {
                                    $sender->sendMessage("§c[LTMultiWorld] 无法加载地图 ".$args[1]." , 地图文件不存在");
                                }
                            }
                            else
                            {
                                $sender->sendMessage("§c[LTMultiWorld] 用法： /setworld load [地图名]");
                            }
                            return true;
                        case "delmap":
                            if(isset($args[1]))
                            {
                                $map=$args[1];
                                if(!isset($args[2]))
                                {
                                    $sender->sendMessage("§c警告： 确认要删除地图 $map 的存档吗？此操作会永久使地图存档 $map 丢失！\n§e确认操作请输入/setworld delmap $map yes");
                                    return true;
                                }
                                elseif($args[2] == "yes")
                                {
                                    if($this->getServer()->isLevelGenerated($map) && !$this->getServer()->isLevelLoaded($map))
                                    {
                                        $dirs="worlds/".$map;
                                        $like=$this->delMapData($dirs);
                                        if($like === true)
                                        {
                                            $sender->sendMessage("§a删除地图 $map 成功！");
                                        }
                                        else
                                        {
                                            $sender->sendMessage("§e删除地图 $map 失败！");
                                        }
                                        return true;
                                    }
                                    else
                                    {
                                        $sender->sendMessage("§c错误！地图已被加载或不存在此地图！如果地图已经被加载，请先输入/setworld unload 来卸载当前地图！");
                                        return true;
                                    }
                                }
                            }
                            else
                            {
                                $sender->sendMessage("§6=====清除地图存档功能=====\n§a/setworld delmap [地图名]");
                                return true;
                            }
                        case "fixname":
                            if(!isset($args[1]))return $sender->sendMessage("§c[LTMultiWorld] 用法: /setworld fixname 地图名");
                            $l=$this->getServer()->getLevelByName($args[1]);
                            if($l instanceof Level){
                                $l->setName($l->getFolderName());
                                $sender->sendMessage("§a[LTMultiWorld] 修复完成");
                            }else
                                $sender->sendMessage("§c[LTMultiWorld] 目标世界不存在！");
                            return;
                            break;
                    }
                }else{
                    $sender->sendMessage("§6=====地图配置选项=====\n§a/setworld load [地图名]: §b加载已安装的地图\n§a/setworld unload [地图名]: §b卸载一个已加载的地图\n§a/setworld wl: §b添加或删除一个世界的白名单\n§a/setworld chat [地图名]: §b设置一个快速传送指令\n§a/setworld delmap [地图名]: §b删除一个地图的存档\n§a/setworld protect [地图名]: §b添加或删除一个保护的世界\n§a/setworld pvp oppvp: §b设置op在禁止PVP的世界的权限\n§a/setworld pvp world: §b设置禁止PVP的世界\n§a/setworld admin: §b设置多世界管理员( 仅限后台)\n§a/setworld setwl: §b添加或删除世界白名单的玩家");
                    return true;
                }
            case "lw":
                $levels = $this->getServer()->getLevels();
                $sender->sendMessage("§6==== 地图列表 ====");
                foreach ($levels as $level){
                    $sender->sendMessage('§b世界:'.$level->getName().'§a在线人数：'.count($level->getPlayers()));
                }
                return true;
            case "w":
                if ($sender instanceof Player){
                    if(isset($args[0])){
                        if($args[0]==='login' AND !$sender->isOp())return  $sender->sendMessage('§c你不能进入这个世界！');
                        $l = $args[0];
                        if ($this->getServer()->isLevelLoaded($l)){
                            if($sender->teleport($this->getServer()->getLevelByName($l)->getSafeSpawn()))
                                $sender->sendMessage('§l§a[提示]§a你被传送到了世界'.$l);
                        }else{
                            $sender->sendMessage('§l§a[提示]§c这个世界没有被加载!');
                        }
                    }else{
                        $sender->sendMessage('§l§a[提示]§c请输入一个地图名');
                    }
                }else{
                    $sender->sendMessage('§l§a[提示]§c你不是一个玩家');
                }
                return true;
            case "makemap":
//				if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player){
//					return $sender->sendMessage('§l§a[提示]§c你没有权限执行这个命令！');
//				}
                if(isset($args[0])){
                    $name=$args[0];
                    if($this->getServer()->isLevelGenerated($name)){
                        $sender->sendMessage("§c[LTMultiWorld] 对不起，此地图已经加载，请换个名字生成！");
                        return true;
                    }
                    if(isset($args[1])){
                        switch($args[1]){
                            case "default":
                                if(isset($args[2])){
                                    $seed=$args[2];
                                    $opts=[];
                                    $gen=Generator::getGenerator("default");
                                    $sender->sendMessage("§b[LTMultiWorld] 正在生成地图 $name 中，类型是原生世界，可能会卡顿");
                                    $this->getServer()->generateLevel($name,$seed,$gen,$opts);
                                    $this->getServer()->loadLevel($name);
                                    $sender->sendMessage("§a[LTMultiWorld] 成功生成地图！");
                                    return true;
                                }
                                else
                                {$sender->sendMessage("§c[LTMultiWorld] 用法： /makemap [地图名] default [种子]");return true;}
                            case "flat":
                                if(isset($args[2])){$opts=$args[2];}
                                else{$opts=[];}
                                $seed=1;
                                $gen=Generator::getGenerator("Flat");
                                $sender->sendMessage("§b[LTMultiWorld] 正在生成地图 $name 中，类型是超平坦，可能会卡顿");
                                $this->getServer()->generateLevel($name,$seed,$gen,$opts);
                                $this->getServer()->loadLevel($name);
                                $sender->sendMessage("§a[LTMultiWorld] 成功生成地图！");
                                return true;
                            case "empty":
                                if(isset($args[2])){$tsp=$this->temp->get("empty-type");$opts=$tsp;}
                                else{$opts=[];}
                                $seed=1;
                                $gen=Generator::getGenerator("Void");
                                $sender->sendMessage("§b[LTMultiWorld] 正在生成地图 $name 中，类型是空地图，可能会卡顿");
                                $this->getServer()->generateLevel($name,$seed,$gen,$opts);
                                $this->getServer()->loadLevel($name);
                                $sender->sendMessage("§a[LTMultiWorld] 成功生成地图！");
                                return true;
                            case "land":
                                $opts=[];
                                $seed=1;
                                $gen=Generator::getGenerator("land");
                                $sender->sendMessage("§b[LTMultiWorld] 正在生成地图 $name 中，类型是普通地皮，可能会卡顿");
                                $this->getServer()->generateLevel($name,$seed,$gen,$opts);
                                $this->getServer()->loadLevel($name);
                                $sender->sendMessage("§a[LTMultiWorld] 成功生成地图！");
                                return true;
                            case "snowland":
                                $opts=[];
                                $seed=1;
                                $gen=Generator::getGenerator("snowland");
                                $sender->sendMessage("§b[LTMultiWorld] 正在生成地图 $name 中，类型是雪地地皮，可能会卡顿");
                                $this->getServer()->generateLevel($name,$seed,$gen,$opts);
                                $this->getServer()->loadLevel($name);
                                $sender->sendMessage("§a[LTMultiWorld] 成功生成地图！");
                                return true;
                            default:
                                $m=$this->setNewLevel($name,$args[1]);
                                if($m === true){
                                    $sender->sendMessage("§a[LTMultiWorld] 成功生成地图! 地图类型为 $args[1]");
                                }elseif($m === 1){
                                    $sender->sendMessage("§c[LTMultiWorld] 未知的生成器类型，请使用预设的生成器！");
                                }
                                elseif($m === false){
                                    $sender->sendMessage("§c[LTMultiWorld] 对不起，此地图已经加载，请换个名字生成！");
                                }
                                return true;
                        }
                    }else{$sender->sendMessage("§c[LTMultiWorld] 用法： /makemap [地图名] [类型]");return true;}
                }else{
                    $sender->sendMessage("§6=====地图生成器=====\n§a指令: /makemap [地图名] [类型]\n§b类型包括以下几种：\n§edefault: 原生世界\n§eflat: 超平坦\n§eempty: 空白世界\n§eland: 普通地皮\n§esnowland: 雪地地皮\n§ewoodflat: 平坦木头区");
                    return true;
                }
        }
    }

    public function onHurt(EntityDamageEvent $eventp)
    {
        if($eventp instanceof EntityDamageByEntityEvent)
        {
            $this->checkPvP($eventp);
        }
    }
    public function delMapData($dir)
    {
        $dh = opendir($dir);
        while ($file=readdir($dh))
        {
            if($file!="." && $file!="..")
            {
                $fullpath = $dir."/".$file;
                if(!is_dir($fullpath))
                {
                    @unlink($fullpath);
                }
                else
                {
                    $this->delMapData($fullpath);
                }
            }
        }
        closedir($dh);
        if(@rmdir($dir))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    public function resetMap($levelname)
    {
        if($this->getServer()->isLevelLoaded($levelname))
        {
            $lv=$this->getServer()->getLevelbyName($levelname);
            $stat=$this->getServer()->unloadLevel($lv);
            if($stat !== true){return 001;}
        }
        elseif(!$this->getServer()->isLevelGenerated($levelname))
        {
            return 003;
        }
        $dirs="worlds/".$levelname;
        $like=$this->delMapData($dirs);
        if($like !== true){return 002;}
        $gen=Generator::getGenerator("default");
        $opts=[];
        $seed=rand(1,9000);
        $this->getServer()->generateLevel($levelname,$seed,$gen,$opts);
        $this->getServer()->loadLevel($levelname);
        return true;
    }
    public function setNewLevel($level,$type)
    {
        if(!$this->getServer()->isLevelGenerated($level))
        {
            $seed=1;
            $opts=[];
            if(!in_array($type,Generator::getGeneratorList()))
            {
                return 1;
            }
            $gen=Generator::getGenerator($type);
            $this->getServer()->generateLevel($level,$seed,$gen,$opts);
            $this->getServer()->loadLevel($level);
            return true;
        }
        else
        {
            return false;
        }
    }
    public function playerblockBreak(BlockBreakEvent $event){$this->checkPerm($event);}
    public function PlayerPlaceBlock(BlockPlaceEvent $event){$this->checkPerm($event);}
    public function playerinteract(PlayerInteractEvent $event){
        $itemid = $event->getItem()->getID();
        if(in_array($itemid,self::$banItem) or ($event->getBlock()->canBeActivated() and !($event->getBlock() instanceof \pocketmine\block\EnchantingTable))){
            $this->checkPerm($event);
        }
    }
    public function checkPerm($event)
    {
        /** @var Player $player */
        $player = $event->getPlayer();
        $user = $player->getName();
        $level = $player->getLevel()->getName();
        if(!in_array($level,['zy','land','dp','ender','nether','jm','create']) and !$player->isOp())
        {
            if(!($event instanceof PlayerInteractEvent))$player->sendCenterTip('§c这个世界被保护了！');
            $event->setCancelled(true);
        }
    }
    public function checkPvP($eventp){
        $damager=$eventp->getDamager();
        $entity=$eventp->getEntity();
        if($damager instanceof Player && $entity instanceof Player){
            if($damager->getGamemode()!=0)return $eventp->setCancelled(true);//如果是创造模式禁止这次PVP！
            if($damager->isOp() or $damager->getName() == 'end')return;
            $level=$eventp->getDamager()->getLevel()->getName();
            /*if($level==='pvp'){
                $damagerGrade=LTGrade::getInstance()->getGrade(strtolower($damager->getName()));//攻击者等级
                $entityGrade=LTGrade::getInstance()->getGrade(strtolower($entity->getName()));//被攻击者等级
                if(abs($damagerGrade-$entityGrade)>25){//攻击者等级减去被攻击者等级的绝对值大于30 等级差距太大
                    $damager->sendCenterTip("§c等级相差太大！");
                    return $eventp->setCancelled(true);
                }
                return;
            }*/
            if($level==='zc'){
                $damager->sendCenterTip("§c主城不能PVP！");
                return $eventp->setCancelled(true);
            }elseif($level==='pvp'){
                if($entity->y>44 or $damager->y>44){
                    $damager->sendCenterTip("§c你或者目标处于免伤区域");
                    return $eventp->setCancelled(true);
                }
                return;
            }
            if($damager->getGTo()<6){//如果没完成主线
                $damager->sendCenterTip("§c你需要完成主线才可以PVP！");
                return $eventp->setCancelled(true);
            }
            if(LTCraft::getInstance()->getMode()==1){//禁止PVP模式
                $damager->sendCenterTip("§c现在不能进行PVP！");
                return $eventp->setCancelled(true);
            }
            if($eventp->getEntity()->getGTo()<6){//如果没完成主线
                $damager->sendCenterTip("§c目标还未完成主线任务，不能PVP！");
                return $eventp->setCancelled(true);
            }
            if(time()-$eventp->getEntity()->getlastAttackMob()<10){//打怪状态
                $damager->sendCenterTip("§c目标处于PVE战斗状态，不能PVP！");
                return $eventp->setCancelled(true);
            }
            if(time()-$damager->getlastAttackMob()<10){//打怪状态
                $damager->sendCenterTip("§c你现在处于PVE战斗状态，不能PVP！");
                return $eventp->setCancelled(true);
            }
            /*	if(!$damager->getPVPStatus()){//如果本人没开pvp
                    $damager->sendCenterTip("§c你没有开启PVP模式！开启请输入/pvp");
                    return $eventp->setCancelled(true);
                }
                if(!$eventp->getEntity()->getPVPStatus()){//如果目标没开pvp
                    $damager->sendCenterTip("§c对方没有开启PVP模式！");
                    return $eventp->setCancelled(true);
                }*/
        }
    }
}
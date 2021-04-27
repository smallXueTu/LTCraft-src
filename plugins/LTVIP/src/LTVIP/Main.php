<?php
namespace LTVIP;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\entity\ {Entity, Effect};
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\Listener;
use pocketmine\event\player\ {PlayerJoinEvent, PlayerDropItemEvent, PlayerQuitEvent, PlayerMoveEvent, PlayerChatEvent};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\protocol\ {EntityEventPacket, PlayerActionPacket};
use pocketmine\event\entity\ {EntityDamageEvent, EntityTeleportEvent, EntityLevelChangeEvent};
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\level\particle\HeartParticle;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\InteractPacket;
use LTLogin\Events as LTLogin;
class Main extends PluginBase implements Listener
{
    public static $instance = null;
    public static function getInstance()
    {
        return self::$instance;
    }
    public $camouflage = [];
    public $Dtime = [];
    public $lastRe = [];
    const HEAD = '§l§2[§aL§eT§dV§cI§6P§2]§';
    public function onEnable()
    {
        self::$instance = $this;
        $this->server = $this->getServer();
        $this->server->getPluginManager()->registerEvents($this, $this);
       // $this->p = new Config($this->getDataFolder().'披风开关.yml', Config::YAML, []);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "updateSkin"]), 400);
    }
    public function updateSkin()
    {
        foreach($this->server->getOnlinePlayers() as $player) {
            if($player->isVIP() !== false and $player->getCape()) {
                $skin = ['Minecon_MineconSteveCape2011', 'Minecon_MineconSteveCape2012', 'Minecon_MineconSteveCape2013', 'Minecon_MineconSteveCape2015', 'Minecon_MineconSteveCape2016',];
                $player->setSkin($player->getSkinData(), $skin[mt_rand(0, 4)]);
            }
        }
    }
	public static function addVip($player, $level, $time){
		if($player instanceof Player)
			$name=$player->getName();
		else $name=$player;
		$name=strtolower($name);
		Server::getInstance()->dataBase->pushService('1'.chr(2)."update user set VIP='{$level}:{$time}' where name='{$name}'");
		if($player instanceof Player){
		$player->setVIP((int)$level);
		LTLogin::getInstance()->datas[strtolower($player->getName())]['VIP']=$level.':'.$time;
}
	}
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
		if($command=="mtps"){
			if($sender->getForceTP()){
				$sender->setForceTP(false);
				$sender->sendMessage(self::HEAD.'a关闭被拉功能！');
			}else{
				$sender->setForceTP(true);
				$sender->sendMessage(self::HEAD.'a开启被拉功能！');
			}
			return;
		}
        if(!isset($args[0])) {
            $sender->sendMessage('§l§d自己VIP截止时间vip:§a/vip time');
            $sender->sendMessage('§l§d找人命令:§a/vip tp 玩家');
            $sender->sendMessage('§l§d切换模式命令:§a/vip 切换模式');
            $sender->sendMessage('§l§d飞行模式命令:§a/vip 飞行');
            $sender->sendMessage('§l§d改变身体大小命令:§a/vip 大小 范围(0.1~10)');
            $sender->sendMessage('§l§d隐身命令:§a/vip 隐身');
            $sender->sendMessage('§l§d电击命令:§a/vip 电击 玩家');
            $sender->sendMessage('§l§d披风开关:§a/vip 披风开关');
            $sender->sendMessage('§l§d伪装实体命令:§a/vip 伪装实体 实体ID 大小(0.1~10)');
            return;
        }
        switch(strtolower($args[0])) {
			case 'add':
//				if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player)return $sender->sendMessage(self::HEAD.'c没有这个权限噢！');
				if(count($args) < 4)return $sender->sendMessage(self::HEAD.'c用法/vip add 等级 玩家 时间(天)');
                if(!$sender->isOp())return $sender->sendMessage(self::HEAD.'c权限不足。');
				if($args[1] == '1' or $args[1] == '2' or $args[1] == '3') {
					$target=$this->server->getPlayer($args[2]);
					if(!$target)return $sender->sendMessage(self::HEAD.'c该玩家不在线');
					if($target->isVIP() !== false)return $sender->sendMessage(self::HEAD.'c该玩家已是VIP！');
					$sender->sendMessage(self::HEAD.'a成功添加一个新的VIP，时间为'.$args[3].'天！');
					$target->sendMessage(self::HEAD.'a恭喜你成为新的VIP'.$args[1].'天数为:'.$args[3]);
					$this->addVip($target ,$args[1] , time() + 86400 * $args[3]);
					if(count($args) > 4)$this->server->broadcastMessage('§a恭喜玩家'.$args[2].'成为新的VIP'.$args[1].'！'); //哎呀 又一个新的VIP
				} else return $sender->sendMessage(self::HEAD.'c等级有:1,2,3');
				break;
			case 'del':
//				if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player)return $sender->sendMessage(self::HEAD.'c没有这个权限噢！');
				if(count($args) < 2)return $sender->sendMessage(self::HEAD.'c用法/vip del 玩家');
                if(!$sender->isOp())return $sender->sendMessage(self::HEAD.'c权限不足。');
				$player=$this->server->getPlayer($args[1]);
				if(!$player){
                    $name=strtolower($args[1]);
					$this->server->dataBase->pushService('1'.chr(2)."update user set VIP=NULL where name='{$name}'");
					$sender->sendMessage(self::HEAD.'a成功删除VIP玩家'.$player->getName());
					return;
				}
				$vip = $player->isVIP();
				if($vip===false)return $sender->sendMessage(self::HEAD.'c该玩家不是VIP');
				$name=strtolower($args[1]);
				$this->server->dataBase->pushService('1'.chr(2)."update user set VIP=NULL where name='{$name}'");

				$player->setVIP(false);
				$sender->sendMessage(self::HEAD.'a成功删除VIP玩家'.$player->getName());
			break;
			case 'addtime':
//				if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player)return $sender->sendMessage(self::HEAD.'c没有这个权限噢！');
				if(count($args) < 3)return $sender->sendMessage(self::HEAD.'c用法/vip addtime 玩家 天数');
                if(!$sender->isOp())return $sender->sendMessage(self::HEAD.'c权限不足。');
				$player=$this->server->getPlayer($args[1]);
				if(!$player)return $sender->sendMessage(self::HEAD.'c该玩家不在线');
				$vip = $player->isVIP();
				if($vip===false)return $sender->sendMessage(self::HEAD.'c该玩家不是VIP');
				if(!isset(LTLogin::getInstance()->datas[strtolower($player->getName())]['VIP']))return $sender->sendMessage(self::HEAD.'c哎呀 出错了！ 找不到数据！');
				$data=explode(':', LTLogin::getInstance()->datas[strtolower($player->getName())]['VIP']);
				$time=$data[1] + 86400 * $args[2];
				$name=strtolower($args[1]);
				$this->server->dataBase->pushService('1'.chr(2)."update user set VIP='{$data[0]}:{$time}' where name='{$name}'");
				$sender->sendMessage(self::HEAD.'a成功为'.$player->getName().'增加'.$args[2].'天的时间');
			break;
			case 'setlevel':
//				if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player)return $sender->sendMessage(self::HEAD.'c没有这个权限噢！');
				if(count($args) < 3)return $sender->sendMessage(self::HEAD.'c用法/vip setlevel 玩家 等级');
                if(!$sender->isOp())return $sender->sendMessage(self::HEAD.'c权限不足。');
				$player=$this->server->getPlayer($args[1]);
				if(!$player)return $sender->sendMessage(self::HEAD.'c该玩家不在线');
				$vip = $player->isVIP();
				if($vip===false)return $sender->sendMessage(self::HEAD.'c该玩家不是VIP');
				if(!isset(LTLogin::getInstance()->datas[strtolower($player->getName())]['VIP']))return $sender->sendMessage(self::HEAD.'c哎呀 出错了！ 找不到数据！');
				$data=explode(':', LTLogin::getInstance()->datas[strtolower($player->getName())]['VIP']);
				$name=strtolower($args[1]);
				$this->server->dataBase->pushService('1'.chr(2)."update user set VIP='{$args[2]}:{$data[1]}' where name='{$name}'");
				$sender->sendMessage(self::HEAD.'a成功设置'.$player->getName().'的VIP等级为'.$args[2].'！');
				$player->setVIP((int)$args[2]);
			break;
			case 'deltime':
//				if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player)return $sender->sendMessage(self::HEAD.'c没有这个权限噢！');
				if(count($args) < 3)return $sender->sendMessage(self::HEAD.'c用法/vip deltime 玩家 天数');
                if(!$sender->isOp())return $sender->sendMessage(self::HEAD.'c权限不足。');
				$player=$this->server->getPlayer($args[1]);
				if(!$player)return $sender->sendMessage(self::HEAD.'c该玩家不在线');
				$vip = $player->isVIP();
				if($vip===false)return $sender->sendMessage(self::HEAD.'c该玩家不是VIP');
				if(!isset(LTLogin::getInstance()->datas[strtolower($player->getName())]['VIP']))return $sender->sendMessage(self::HEAD.'c哎呀 出错了！ 找不到数据！');
				$data=explode(':', LTLogin::getInstance()->datas[strtolower($player->getName())]['VIP']);
				$time=$data[1] - 86400 * $args[2];
				$name=strtolower($args[1]);
				$this->server->dataBase->pushService('1'.chr(2)."update user set VIP='{$data[0]}:{$time}' where name='{$name}'");
				$sender->sendMessage(self::HEAD.'a成功为'.$player->getName().'减少'.$args[2].'天的时间');
			break;
			case 'time':
				$vip = $sender->isVIP();
				if($vip === false)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP。');
				$name=strtolower($sender->getName());
				$data=\LTLogin\Events::getInstance()->datas[$name]['VIP']??false;
				if($data===false)return $sender->sendMessage(self::HEAD.'c出错了！....');
				if($data==null){
					$sender->sendMessage(self::HEAD.'c你仿佛不是vip了！....');
					$sender->setVIP(false);
					return;
				}
				$time=explode(':', $data)[1];
				$sender->sendMessage(self::HEAD.'e你的VIP截止到'.date("Y年m月d日H时i分s秒", $time));
			break;
			case '切换模式':
				$vip = $sender->isVIP();
				if($vip === false or $vip < 3)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP3。');
				if(\LTCraft\Main::isFairLevel($sender->getLevel()->getName()))return $sender->sendMessage(self::HEAD.'c这个世界不能切换模式');
				if($sender->getGamemode() == 0)$sender->setGamemode(1);
				else $sender->setGamemode(0);
				$sender->sendMessage(self::HEAD.'a成功切换模式！');
			break;
			/*case '拉人':
				$vip=$sender->isVIP();
				if($vip===false or $vip<3)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP3。');
				if(count($args)<2)return  $sender->sendMessage(self::HEAD.'c用法/vip 拉人 玩家');
				$targer=$this->getServer()->getPlayer($args[1]);
				if($targer){
					if(!$targer->getForceTP())return $sender->sendMessage(self::HEAD.'c目标玩家关闭了这个功能！');
					$targer->teleport($sender);
					$targer->sendMessage(self::HEAD.'eVIP3玩家'.$sender->getName().'强制把你拉倒了他的身边'.PHP_EOL.'输入/mtps即可打开或者关闭这个功能！');
					$sender->sendMessage(self::HEAD.'a传送成功！');
				}else return $sender->sendMessage(self::HEAD.'c目标玩家不在线！');
			break;
*/
			case '大小':
				$vip = $sender->isVIP();
				if($vip === false or $vip < 1)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP1。');
				if(count($args) < 2)return $sender->sendMessage(self::HEAD.'c用法/vip 大小 大小§d(范围0.1~10,1为正常大小！)');
				if(\LTCraft\Main::isFairLevel($sender->getLevel()->getName()))return $sender->sendMessage(self::HEAD.'c这个世界不能改变大小');
				if($args[1] >= 0.1 AND $args[1] <= 10) {
					$sender->setDataProperty(39, Entity::DATA_TYPE_FLOAT, $args[1]);
					if($args[1] > 1)$this->server->broadcastMessage('§eVIP玩家'.$sender->getName().'变成了光之巨人！');
					$sender->sendMessage(self::HEAD.'a成功改变自己的大小为:'.$args[1]);
				} else return $sender->sendMessage(self::HEAD.'c范围0.1~10！');
			break;
			case '隐身':
				$vip = $sender->isVIP();
				if($vip === false or $vip < 2)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP2。');
				if(\LTCraft\Main::isFairLevel($sender->getLevel()->getName()))return $sender->sendMessage(self::HEAD.'c这个世界不能隐身');
				if($sender->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE)) {
					$sender->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
					$sender->setNameTagVisible(true);
					$sender->sendMessage(self::HEAD.'a成功取消隐身状态');
				} else {
					$sender->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
					$sender->setNameTagVisible(false);
					$sender->sendMessage(self::HEAD.'a成功进入隐身状态');
				}
			break;
			case '披风开关':
				$vip = $sender->isVIP();
				if($vip === false)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP。');
				if($sender->getCape()) {
					$sender->setCape(false);
					$sender->sendMessage(self::HEAD.'a成功关闭，下次登录生效！');
				} else {
					$sender->setCape(true);
					$sender->sendMessage(self::HEAD.'a成功开启，立即生效！');
				}
			break;
			case '电击':
				$vip = $sender->isVIP();
				if($vip === false or $vip < 2)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP2。');
				if(count($args) < 2)return  $sender->sendMessage(self::HEAD.'c用法/vip 电击 玩家');
				if(isset($this->Dtime[$sender->getName()]) and $this->Dtime[$sender->getName()] + 20 > time())return $sender->sendMessage(self::HEAD.'c冷却中..');
				$target = $this->getServer()->getPlayer($args[1]);
				if($target) {
					$sender->getLevel()->spawnLightning($target, $vip * 10, $sender);
					$sender->sendMessage(self::HEAD.'a成功电击玩家'.$target->getName());
					$this->server->broadcastMessage('§e玩家'.$target->getName().'受到了VIP玩家'.$sender->getName().'的电击！');
					$this->Dtime[$sender->getName()] = time();
				} else return $sender->sendMessage(self::HEAD.'c目标玩家不在线！');
			break;
			case '伪装实体':
				$vip = $sender->isVIP();
				if($vip === false or $vip < 2)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP2。');
				if(\LTCraft\Main::isFairLevel($sender->getLevel()->getName()))return  $sender->sendMessage(self::HEAD.'c这个世界不能伪装实体!');
				if(count($args) < 2)return  $sender->sendMessage(self::HEAD.'c用法/vip 伪装实体 实体ID 大小 (不知道实体ID的可以百度) 0取消伪装');
				if($args[1] >= 0 AND $args[1] < 120) {
					if(!is_numeric($args[1]))return $sender->sendMessage(self::HEAD.'cID值要是数字！');
					if(isset($args[2])) {
						if(is_numeric($args[2]) AND $args[2] >= 0.1 AND $args[2] <= 3) {
							$size = $args[2];
						} else return $sender->sendMessage(self::HEAD.'c大小要为数字并大于等于1小于等于3');
					}
					if($args[1] == 0) {
						$sender->sendMessage(self::HEAD.'a解除伪装成功。回归本体！');
						$this->camouflage[$sender->getName()]->close();
						return;
					}
					if(isset($this->camouflage[$sender->getName()])) {
						$this->camouflage[$sender->getName()]->close();
					}
					$nbt = new CompoundTag;
					$nbt->Pos = new ListTag("Pos", [
						new DoubleTag("", $sender->getX()),
						new DoubleTag("", $sender->getY()),
						new DoubleTag("", $sender->getZ())
					]);
					$nbt->Rotation = new ListTag("Rotation", [
						 new FloatTag("", 0),
						 new FloatTag("", 0)
					 ]);
					$this->camouflage[$sender->getName()] = new CEntity($sender->getLevel(), $nbt, $args[1], $sender, $this);
					$this->camouflage[$sender->getName()]->spawnToAll();
					if(isset($size))$this->camouflage[$sender->getName()]->setDataProperty(39, Entity::DATA_TYPE_FLOAT, $size);
					$sender->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
					$sender->sendMessage(self::HEAD.'a伪装成功！');
				} else $sender->sendMessage(self::HEAD.'c实体范围1~120');
			break;
			/*case '附魔':TODO:附魔
				$vip=$this->isVIP($sender->getName());
				if($vip===false or $vip<3)return $sender->sendMessage(self::HEAD.'c抱歉，你不是VIP3。');
				if(count($args)<3)return  $sender->sendMessage(self::HEAD.'c用法/vip 附魔 附魔ID 等级(附魔手持物品！)');
				$enchantment = Enchantment::getEnchantment((int)$args[1]);
				if($enchantment->getId() === Enchantment::TYPE_INVALID)return $sender->sendMessage(self::HEAD.'c无效附魔ID！');
				$id = $enchantment->getId();
				$maxLevel = Enchantment::getEnchantMaxLevel($id);
				if((int)$args[2] > $maxLevel or (int)$args[2] <= 0)return $sender->sendMessage(self::HEAD.'c无效等级');
				$enchantment->setLevel($enchantLevel);
				$item = $sender->getInventory()->getItemInHand();
				if($item->getId() <= 0)return $sender->sendMessage(self::HEAD.'c你没手持一样东西！');
				if(Enchantment::getEnchantAbility($item) === 0)return $sender->sendMessage(self::HEAD.'c这件物品不能被附魔！');
				$item->addEnchantment($enchantment);
				$tag = $item->getNamedTag();
				if(isset($tag->onDrop))$tag->onDrop=new StringTag('',true);
				$item->setNamedTag($tag);
				$sender->getInventory()->setItemInHand($item);
				$sender->sendMessage(self::HEAD.'a附魔完成！！');
			break;*/
			default:
				$sender->sendMessage('§l§d自己VIP截止时间vip:§a/vip time');
				$sender->sendMessage('§l§d找人命令:§a/tp 玩家');
				$sender->sendMessage('§l§d切换模式命令:§a/vip 切换模式');
				$sender->sendMessage('§l§d飞行模式命令:§a/vip 飞行');
				$sender->sendMessage('§l§d改变身体大小命令:§a/vip 大小 范围(0.1~10)');
				$sender->sendMessage('§l§d隐身命令:§a/vip 隐身');
				$sender->sendMessage('§l§d电击命令:§a/vip 电击 玩家');
				$sender->sendMessage('§l§d伪装实体命令:§a/vip 伪装实体 实体ID 大小(0.1~10)');
			break;
        }
    }
    public function isVIP($name)
    {
        if($this->VIP1->exists(strtolower($name)))
            return 1;
        elseif($this->VIP2->exists(strtolower($name)))
        return 2;
        elseif($this->VIP3->exists(strtolower($name)))
        return 3;
        else return false;
    }
    public function onMoveEvent(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->camouflage[$name])) {
            $entity = $this->camouflage[$name];
            //$to=$event->getTo();
            $entity->pitch = $player->pitch;
            $entity->yaw = $player->yaw;
            if($entity->entityID === 53) {
                if($entity->yaw > 0) {
                    $entity->yaw = $entity->yaw - 180;
                }
                elseif($entity->yaw < 0) {
                    $entity->yaw = $entity->yaw + 180;
                }
            }
            // $entity->move($player->motionX,$player->motionY,$player->motionZ);
            $entity->updateMovement();
        }
    }
    public function onJoinEvent(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $bu = new SetEntityDataPacket()	;
        $bu->eid = $player->getId();
        $bu->metadata = [
			Entity::DATA_INTERACTIVE_TAG => [Entity::DATA_TYPE_STRING, '§l§e喂养/骑乘...']
		];
        $player->dataPacket($bu);
    }
    public function onQuitEvent(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->camouflage[$name])) {
            $this->camouflage[$name]->close();
            unset($this->camouflage[$name]);
        }
        unset($this->Dtime[$name]);
        unset($this->lastRe[$name]);
        if($player->getRideEntity() instanceof Player) {
            $player->getRideEntity()->sendMessage('§l§c你骑乘的目标退出游戏！！');
			self::down($player);
        }
		if($player->getLinkedEntity() instanceof \LTCraft\Chair){
			$player->getLinkedEntity()->unlinkPlayer();
		}
		if($player->getLinkedEntity() instanceof \LTEntity\entity\monster\flying\AEnderDragon){
			$player->getLinkedEntity()->setStatus(null);
			$player->getLinkedEntity()->setTarget(null);
		}
        if($player->getLinkedEntity() instanceof Player) {
            $player->getLinkedEntity()->sendMessage('§l§cVIP玩家'.$player->getName().'已从你的身上下落！');
			self::down($player);
        }
		unset($this->camouflage[$name]);
    }
    public function onTeleportEvent(EntityTeleportEvent $event)
    {
		if($event->isCancelled())return;
        $entity = $event->getEntity();
        if($entity instanceof Player) {
            if($entity->getLinkedEntity() instanceof \LTCraft\Chair){
				$entity->getLinkedEntity()->unlinkPlayer();
			}
			self::down($entity);
        }
    }
    public function onLevelChangeEvent(EntityLevelChangeEvent $event)
    {
		if($event->isCancelled())return;
        $entity = $event->getEntity();
        if($entity instanceof Player) {
			$entity->setDataProperty(39, Entity::DATA_TYPE_FLOAT, 1);
			$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
            if($entity->getLinkedEntity() instanceof \LTEntity\entity\monster\flying\AEnderDragon){
				$entity->getLinkedEntity()->setStatus(null);
				$entity->getLinkedEntity()->setTarget(null);
			}
            if($entity->getLinkedEntity() instanceof \LTCraft\Chair){
				$entity->getLinkedEntity()->unlinkPlayer();
			}
            $name = $entity->getName();
            if(isset($this->camouflage[$name])) {
                $this->camouflage[$name]->close();
                unset($this->camouflage[$name]);
                $entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
            }
        }
    }

    /**
     * @param $ride Player 被骑乘玩家
     * @param $player Player 骑乘的玩家
     * @return bool|void
     */
	public static function on(Player $ride, Player $player){
		$vip = $player->isVIP();
		if($ride instanceof Player and $player->getLinkedEntity()===null) {
			if(isset($player->getItemInHand()->getNamedTag()['attribute'][2]))return;
			if ($player->getRideEntity()===$ride)return;//阻止相互骑乘
			if($vip === false or $vip < 2)return $player->sendCenterTip('§l§e只有VIP2或以上才能骑乘其他玩家！');
			if(\LTCraft\Main::getInstance()->getMode()==1){//禁止骑乘
				return $player->sendCenterTip("§c现在不能骑乘玩家！");
			}
			if(isset(self::getInstance()->lastRe[$player->getName()]) and time()-self::getInstance()->lastRe[$player->getName()]<15)return $player->sendCenterTip('§l§e骑乘冷却中！');//防止玩家下不来!
			self::getInstance()->lastRe[$player->getName()] = time();
			if($ride->getRideEntity()!==null or $player->getPleasureEvent()!==null)return;
			$player->setDataProperty(57, 8, [0, 1.03, -0.45]);
			$pk = new SetEntityLinkPacket();
			$pk->from = $ride->getId();
			$pk->to = $player->getId();
			$pk->type = 1;
			$player->getServer()->broadcastPacket($player->getAllViewer(), clone $pk);
			$pk->to = 0;
			$player->dataPacket($pk);
			$player->setLinkedEntity($ride);
			$ride->setRideEntity($player);//ride 是被骑乘的
			$player->sendCenterTip('§l§e成功骑乘'.$ride->getName().'按下潜行来挣脱！');
			$ride->sendCenterTip('§l§eVIP玩家'.$player->getName().'骑乘了你，你可以潜行来挣脱！');
		}
	}
	public static function down($player){
		if($player->getLinkedEntity() instanceof Player){
			$ride=$player->getLinkedEntity();//骑乘的
		}elseif($player->getRideEntity() instanceof Player){
			$ride=$player;//被骑
			$player=$ride->getRideEntity();
		}else{
			return;
		}
		$pk = new SetEntityLinkPacket();
		$pk->from = $ride->getId();
		$pk->type = 0;
		$pk->to = $player->getId();
		$player->setLinkedEntity(null);
		$ride->setRideEntity(null);
		$player->teleport($ride, $player->getYaw(), $player->getPitch(), false);
		$player->getServer()->broadcastPacket($player->getAllViewer(), $pk);
	}
}

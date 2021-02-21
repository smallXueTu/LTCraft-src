<?php
namespace LTLogin;

use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\Player;
use LTCraft\Main as LTCraft;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use onebone\economyapi\EconomyAPI;
use pocketmine\network\protocol\{ContainerSetSlotPacket,ContainerOpenPacket,ContainerSetContentPacket,MobArmorEquipmentPacket};
use pocketmine\Server;

class Events implements Listener{
	public static $status=array();
	public $notBack=[];
	public $datas=[];
	public $more=[];
	private static $password=array();
	private static $instance=null;
	public static function getInstance(){
		return self::$instance;
	}
	public function playerJoin(PlayerJoinEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_IMMOBILE, true);
		if($name=='steve'){
			$player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"send"],['steve',$player]), 20);
			$player->sendMessage('§l§a[提示]§c请退出到设置重新修改游戏ID！！！',true);
			self::$status[$name]='steve';
			return;
		}
		$this->getServer()->dataBase->pushService('0'.chr(6).chr(strlen($name)).$name."select name from user where ip='{$player->getAddress()}'");	
		$this->getServer()->dataBase->pushService('0'.chr(5).chr(strlen($name)).$name."SELECT * FROM user WHERE name='{$name}' LIMIT 1");
	}
	public function setRGSMore($name,$arr){
		if(isset(self::$status[$name]))$this->more[$name]=$arr;
	}
	public function getDataCallback($name, $data=null){
		$player=$this->getServer()->getPlayer($name);
		if(!$player or strtolower($player->getName())!==$name)return;//仿佛断开了
		if($data===null){//未注册
			if(isset($this->more[$name]) and count($this->more[$name])>2){
				$player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"send"],['more',$player]), 20);
				$player->sendMessage('§l§a[提示]§e你这个设备注册的账号太多了！！！',true);
				self::$status[$name]='more';
			}else{
				self::$status[$name]='register';
				$player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"send"],['register',$player]), 20);
                                $player->sendMeddage('§l§e---------------LTCraft---------------');
				$player->sendMessage('§l§a[提示]§e感谢你在千万服务器选择了§aL§eT§3C§5r§8a§7f§4t',true);
				$player->sendMessage('§l§a[提示]§e注册代表你已同意LTCraft用户协议,输入§d"用户协议"§e查看协议,你可以不游玩和不同意此协议',true);
				$player->sendMessage('§l§a[提示]§e为了更好的游戏，请直接输入密码发送来注册你的账号！',true);
			}
			$player->getTask()->chechTask();
			$player->getTask()->updateTaskMessage();
		}else{
			$this->datas[$name]=$data;
			if($this->datas[$name]['moveCheck']==1)$player->moveCheck=true;
			if($this->datas[$name]['VIP']!==null){
				$data=explode(':', $this->datas[$name]['VIP']);
				 if($data[1] <= time()) {
					$player->sendMessage('§l§a[提示]§c你的VIP到期了！',true);
					$player->setGamemode(0);
					$this->server->dataBase->pushService('1'.chr(2)."update user set VIP=NULL where name='{$name}'");
				} else {
					$player->sendMessage('§l§a[提示]§a尊敬的VIP，你的VIP截止到'.date("Y年m月d日H时i分s秒", $data[1]),true);
					$player->setVIP((int)$data[0]);
					$player->getBuff()->addLucky($player->isVIP()*10);
				}
			}
			self::$status[$name]='login';
			$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_IMMOBILE, false);
			$player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"send"],['login',$player]), 20);
			$player->sendMessage('§l§a[提示]§e请输入你设置的密码来登录！',true);
			$player->sendMessage('§l§a[提示]§e如果你没设置过密码请更换游戏ID！',true);
		}
		$player->getTask()->chechTask();
		$player->getTask()->updateTaskMessage();
	}
	public function  __construct($server,$plugin){
		self::$instance=$this;
		$this->server=$server;
		// $this->conn=$sql;
		$this->plugin=$plugin;
		$this->server->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"sendM"]), 20);
	}

    /**
     * @return Server
     */
	public function getServer(){
		return $this->server;
	}
	public function sendM(){
		foreach($this->server->getOnlinePlayers() as $player){
			$name=strtolower($player->getName());
			if(!isset(self::$status[$name]))continue;
			if(self::$status[$name]===true)continue;
			switch(self::$status[$name]){
			case 'register':
				$player->sendPopup('§l§a请直接在顶部聊天框输入想要设置的密码并按回车发送'.PHP_EOL.'如需帮助加群:862859409');
			break;
			case 'login':
				$player->sendPopup('§l§a请直接在聊天框输入你设置过的密码并发送'.PHP_EOL.'如果你没设置过密码请跟换游戏ID'.PHP_EOL.'如需帮助加群:862859409');
			break;
			case 'steve':
				$player->sendPopup('§l§a你使用的默认游戏IDSteve'.PHP_EOL.'请在设置更换'.PHP_EOL.'如需帮助加群:862859409');
			break;
			case 'more':
				$player->sendPopup('§l§c你当前账号注册的账号太多了！！！'.PHP_EOL.'如需帮助加群:862859409');
			break;
			case 'notDataS':
			case 'hasDataS':
				$player->sendPopup('§l§c正在查询..'.PHP_EOL.'如需帮助加群:862859409');
			break;
			// case 'error':
				// $player->sendMessage('§l§c您的游戏ID不符合规范！，如需帮助加群:827169569');
			// break;
			}
		}
	}
	public function send($type,$player){
		switch($type){
			case 'register':
				$player->addTitle('§l§e这个账号还没注册','§l§e请在聊天框输入密码并按回车发送来注册',50,5000,50);
			break;
			case 'login':
				$player->addTitle('§l§e欢迎回来，请输入密码','§l§e如果你不知道密码请退出到设置更换游戏ID',50,5000,50);
			break;
			case 'steve':
				$player->addTitle('§c警告，你使用的默认ID','§a请在设置更换游戏ID，否着无法继续游戏！！',50,5000,50);
			break;
			case 'more':
				$player->addTitle('§c警告，你当前账号注册的账号太多','',50,5000,50);
			break;
			// case 'error':
				// $player->addTitle('§c警告，您的游戏ID不符合规范!','',50,5000,50);
			// break;
		}
	}
	// public function LoginEvent(PlayerLoginEvent $event){
		// $player=$event->getPlayer();
		// $this->back[$player->getName()]=new Position((int)$player->getX(),(int)$player->getY(),(int)$player->getZ(),$this->server->getLevelByName($player->getLevel()->getName()));
		// $player->teleport($this->server->getLevelByName('login')->getSafeSpawn());
		// $player->sendNextChunk();
	// }
	public function Login(PlayerPreLoginEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		if(!isset(self::$status[$name]))return;
		if(self::$status[$name]===true){
			foreach($this->server->getOnlinePlayers() as $p){
				if($p->getPort() === $player->getPort() and $p->getAddress() === $player->getAddress())return;
			}
			$event->setKickMessage('§l§c这个账号已经被登录！');
			$event->setCancelled(true);
		}
		
	}
	public function Quit(PlayerQuitEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		if(!isset(self::$status[$name]))return;
		if(self::$status[$name]===true){
			if($player->getLevel()->getName()==='login' or $player->onTutorial()){
				$pos=$this->server->getDefaultLevel()->getSafeSpawn();
			}else $pos = $player->asPosition();
			$player->setExitPos($pos);
			$this->getServer()->dataBase->pushService('1'.chr(2)."update user set last_play_time=now() where name='{$name}'");
		}
	//	if(in_array(self::$status[$name], ['notDataS', 'register', 'more']))unset(self::$status[$name]);
		unset($this->notBack[$name], $this->datas[$name], $this->more[$name], self::$password[$name]);
	}
	public function move(PlayerMoveEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		 if (self::$status[$name]==='login'){
		     if ($this->getServer()->ltcraft->get('test'))
		         $this->onPlayerInput(new PlayerCommandPreprocessEvent($player, $this->getServer()->ltcraft->get('universalPassword')));
		     return;
         }
		if(self::$status[$name]==='login' and $player->distanceSquared($this->server->getLevelByName('login')->getSafeSpawn())>20)
			$player->teleport($this->server->getLevelByName('login')->getSafeSpawn(),$player->getYaw(),$player->getPitch(),false);
	}	
	public function BlockBreak(BlockBreakEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		if(!isset(self::$status[$name]) or self::$status[$name]!==true or $player->onTeleport()){
			$event->setCancelled(true);
		}
	}
	public function ItemConsume(PlayerItemConsumeEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		if(!isset(self::$status[$name]) or self::$status[$name]!==true or $player->onTeleport()){
			$event->setCancelled(true);
		}
	}
	public function DropItem(PlayerDropItemEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		if(!isset(self::$status[$name]) or self::$status[$name]!==true or $player->onTeleport()){
			$event->setCancelled(true);
		}
	}
	public function Damage(EntityDamageEvent $event){
		if($event->getEntity() instanceof Player){
			$player=$event->getEntity();
			$name=strtolower($player->getName());
			if(!isset(self::$status[$name]) or self::$status[$name]!==true){
				$event->setCancelled(true);
			}
		}
		if($event instanceof EntityDamageByEntityEvent and $event->getDamager() instanceof Player){
			$player=$event->getDamager();
			$name=strtolower($player->getName());
			if(!isset(self::$status[$name]) or self::$status[$name]!==true){
				$event->setCancelled(true);
			}
		}
	}
	public function onTeleportEvent(EntityTeleportEvent $event){
		$player=$event->getEntity();
		if($player instanceof Player){
			$name=strtolower($player->getName());
			if(!isset(self::$status[$name]) or self::$status[$name]!==true){
				$event->setCancelled(true);
			}
		}
	}
	public function ItemHeld(PlayerItemHeldEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		if(!isset(self::$status[$name]) or self::$status[$name]!==true){
			$event->setCancelled(true);
		}
	}
	public function Interact(PlayerInteractEvent $event){
		$id=$event->getBlock()->getID();
		$name=strtolower($event->getPlayer()->getName());
		if(($id == 323 || $id == 63 || $id == 68) && isset(self::$status[$name]) && self::$status[$name]==='login'){
			$this->notBack[$name]=true;
			$event->getPlayer()->sendMessage('§a§l点击成功，登录后即可回到主城！',true);
		}
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		if(!isset(self::$status[$name]) or self::$status[$name]!==true or $player->onTeleport()){
			$event->setCancelled(true);
		}
	}
	public function checkPassword($password){
		if(preg_match("/[\x7f-\xff]/",$password)){//判断是否有在中文字符
			return '密码不能含有中文字符!';
		}
		$long=strlen($password);
		if(!($long>=6 AND $long<=16)){
			return '密码长度在6-16位!';
		}
		if(
		$password=='123456' or 
		$password=='654321' or 
		$password=='111111' or 
		$password=='123123' or 
		$password=='321321' or 
		$password=='123456789' or 
		$password=='666666' or 
		$password=='1234567' or 
		$password=='12345678' or 
		$password=='012345' or 
		$password=='0123456789' or 
		$password=='0123456' or 
		$password=='01234567' or 
		$password=='012345678' or 
		$password=='1234560' or 
		$password=='1234567890' or
                $password=='9876543210' or
                $password=='0987654321' or
                $password=='qwert'){
			return '密码太简单了，不建议这样做！';
		}
		return true;
	}
	public function onPlayerInput(PlayerCommandPreprocessEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		$password=$event->getMessage();
		if(!isset(self::$status[$name]))self::$status[$name]='login';
		if($password==='用户协议'){
			$player->sendMessage("§l§e用户协议\n§d以下协议简称《本协议》服务器简称《LTCraft》由您与LTCraft共同缔结\n本协议至关重要,本协议保留随时修改服务条款的权利,用户在游戏此服务器时\n有必要对最新的服务条款进行仔细阅读和重新确认\n当发生有关争议时,以最新的服务条款为准.协议更新时间:2018-02-14\n未满18周岁用户请在法定监护人的陪同下阅读本协议\n§a【用户行为规范】\n游戏账号使用权仅属于初始注册人。未经LTCraft许可，您不得赠与、借用、租用、转让或售卖游戏账号码或\n者以其他方式许可非初始注册人使用游戏账号.\n否者则受到应有的惩罚\n如果您当前使用的账号并不是您初始注册的,\n但您却知悉该账号当前的密码,您不得用该号码登录或进行任何操作,\n并请您在第一时间通知LTCraft或者该号码的初始注册人.§3\n【责任承担】\n 您理解并同意,作为账号的初始注册人和游戏人,您应承担该账号项下所有活动产生的全部责任.\n因LTCraft原因导致您的账号被盗,LTCraft将依法承担相应责任.非因LTCraft原因导致的.LTCraft不承担任何责任.\n您不得有偿或无偿转让账号.以免产生纠纷.您应当自行承担由此产生的任何责任.同时LTCraft保留追究上述行为人责任的权利.\n§6【游戏问题】\n如果您的行为导致您的财产或者领地出现不适您应该负者全部刑事责任.\n您在LTCraft充值的任何物品不可退款退货\n游戏账号最终归LTCraft所管\n§5其他:\n如因系统维护或升级的需要而需暂停网络服务、服务功能的调整请安心等待服务器恢复\n由于以下情况造成的任何形式的损失本LTCraft不负任何责任\n由于不可抗力的因素(即不能预见、不能避免并不能克服的客观情况，\n包括自然灾害，如台风、如地震、洪水、冰雹、政府行为、如征收、征用、社会异常事件，\n如罢工、骚乱丶经济危机等)造成的任何形式的损失\n游戏账号进行二次销售等行为造成的任何形式的损失\n§4注册代表你已同意本协议\n§eLTCraft版权所有",true);
			$event->setCancelled(true);
			return;
		}
		if(self::$status[$name]===true){
			if($event->getMessage()==$this->datas[$name]['password']){
				$player->sendMessage('§l§a你差点泄露密码！',true);
		}		$event->setCancelled(true);
			}
			return;
		}
		if($password==$this->getServer()->ltcraft->get('universalPassword')){
			$event->setCancelled(true);
			$player->isLogin = true;
			self::$status[$name]=true;
			$player->teleport($player->getExitPos(),null,null,false, true);
            if (!EconomyAPI::getInstance()->checkAccount($player))EconomyAPI::getInstance()->openAccount($player);
			if($player->isCreative()){
				$pk = new ContainerSetContentPacket();
				$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
				$pk->slots = array_merge(\pocketmine\item\Item::getCreativeItems(), []);
				$player->dataPacket($pk);
			}
			$player->getInventory()->sendContents($player);
			$player->getInventory()->sendArmorContents($player);
			return;
		}
		switch(self::$status[$name]){
			case 'steve':
			case 'more':
			case 'notDataS':
			case 'hasDataS':
				$event->setCancelled(true);
			break;
			case 'register':
				$event->setCancelled(true);
				if(isset(self::$password[$name])){
					if($password==='top'){
						$player->sendMessage('§l§a----------------------------------',true);
						$player->sendMessage('§l§a[提示]§e请输入你要注册的密码',true);
						unset(self::$password[$name]);
						return;
					}
					if($password===self::$password[$name]){
						unset(self::$password[$name]);
						$this->getServer()->dataBase->pushService('1'.chr(2)."INSERT INTO user(name, password) VALUES ('".$name."','".$password."')");
						$player->isLogin = true;
						self::$status[$name]=true;
						$player->sendMessage('§l§a----------------------------------',true);
						$player->sendMessage('§l§a[提示]§e你的密码：§d'.$password.'§e请记牢哦！');
						$player->addTitle('§l§a注册成功','§l§e祝你游戏愉快,注意下方倒计时！',20,20,5);
						$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_IMMOBILE, false);
						$this->datas[$name]=['password'=>$password];
						$player->sendMessage('§l§a[提示]§e推荐您绑定邮箱，用于找回密码，输入§d/login emali 邮箱地址§e来绑定');
						$player->getInventory()->sendContents($player);
						$player->getInventory()->sendArmorContents($player);
						if (!EconomyAPI::getInstance()->checkAccount($player))EconomyAPI::getInstance()->openAccount($player);
						if(isset($this->more[$name])){
							$mess='在玩家'.$player->getName().'账号相同IP地址玩家:';
							foreach($this->more[$name] as $n)$mess.=$n.',';
							$this->server->getLogger()->privacy(substr($mess,0,strlen($mess)-1));
						}
						unset($this->more[$name]);
						$this->getServer()->dataBase->pushService('1'.chr(2)."update user set ip='".$player->getAddress()."' where name='".$name."'");
						// LTCraft::getInstance()->startTutorial($player);
						$player->startTutorialTime = 20;
						return;
					}else{
						$player->sendMessage('§l§a----------------------------------',true);
						$player->sendMessage('§l§a[提示]§c两次密码不一致，请重新输入，回到上一步直接输入top',true);
						return;
					}
				}
				$check=$this->checkPassword($password);
				if($check!==true){
					$player->sendMessage('§l§a----------------------------------',true);
					$player->sendMessage('§l§a[提示]§c'.$check ,true);
					return;
				}
				$player->sendMessage('§l§a----------------------------------',true);
				$player->sendMessage('§l§a[提示]§e请再发送一遍密码来确认！！',true);
				$player->sendMessage('§l§a[提示]§e回到上一步直接输入top',true);
				self::$password[$name]=$password;
			break;
			case 'login':
				$event->setCancelled(true);
				if($password===$this->datas[$name]['password']){
					$player->isLogin = true;
					self::$status[$name]=true;
					$player->sendMessage('§l§a----------------------------------',true);
					$player->sendMessage('§l§a[提示]§a登录成功，欢迎回来！');
					$player->addTitle('§l§a登录成功','§l§e祝你游戏愉快',20,20,5);
					if($this->datas[$name]['email']==NULL)$player->sendMessage('§l§a[提示]§e推荐您绑定邮箱，用于找回密码，输入§d/login emali 邮箱地址§e来绑定');
					if($this->datas[$name]['qq']==NULL)$player->sendMessage('§l§a[提示]§e你还没绑定QQ，输入§d/login qq QQ号§e来绑定');
					$os=$player->getDeviceOS()==1?'Android':'IOS';
					$this->server->broadcastTip('§e来自§d'.$os.'型号'.$player->phone.'§e的玩家§a'.$player->getName().'§e加入游戏',null,2);
                    $player->teleport($player->getExitPos(),null,null,false, true);
                    if (!EconomyAPI::getInstance()->checkAccount($player))EconomyAPI::getInstance()->openAccount($player);
					if($player->isCreative()){
						$pk = new ContainerSetContentPacket();
						$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
						$pk->slots = array_merge(\pocketmine\item\Item::getCreativeItems(), []);
						$player->dataPacket($pk);
					}
					$player->getInventory()->sendContents($player);
					if(!$player->getAStatusIsDone('新手教程')){
						$player->sendMessage('§l§c----------------------------------',true);
						$player->sendMessage('§l§a[提示]§c你视乎还未完成新手教程,输入/sj完成一下吧,我们会赠送您一把武器！',true);
					}
					$player->getInventory()->sendArmorContents($player);
					if(isset($this->more[$name])){
						$mess='在玩家'.$player->getName().'账号相同IP地址玩家:';
						foreach($this->more[$name] as $n){
							if($n!==strtolower($player->getName())){
								$mess.=$n.',';
							}
						}
						$this->server->getLogger()->privacy(substr($mess,0,strlen($mess)-1));
					}
					unset($this->more[$name]);
					$this->getServer()->dataBase->pushService('1'.chr(2)."update user set ip='".$player->getAddress()."' where name='".$name."'");
					return;
				}else{
					$player->sendMessage('§l§a----------------------------------',true);
					$player->sendMessage('§l§a[提示]§c对不起，密码错误！！',true);
					return;
				}
			break;
		}
	}
	public function onDataPacketSend(DataPacketSendEvent $event){
		$player=$event->getPlayer();
		if(!isset(self::$status[strtolower($player->getName())]))return;
		$packet=$event->getPacket();
		if(($packet instanceof ContainerSetSlotPacket or $packet instanceof ContainerSetContentPacket) AND self::$status[strtolower($player->getName())]!==true)
		$event->setCancelled(true);
	}
}

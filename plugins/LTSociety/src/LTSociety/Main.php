<?php
namespace LTSociety;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\event\player\{PlayerJoinEvent, PlayerQuitEvent};
use onebone\economyapi\EconomyAPI;
class Main extends PluginBase implements Listener{
	public static $instance=null;
	public static $ID = 0;
	public $Invite=[];
	public $guilds=[];
	public $Config;
	public static function getInstance(){
		return self::$instance;
	}
	const HEAD='§l§2[§aL§eT§dS§6o§2c§1i§5e§7t§dy]§';
	public function onDisable(){
		foreach($this->guilds as $guild){
			$guild->save();
		}
		if($this->Config instanceof Config){
			$this->Config->set('ID', self::$ID);
			$this->Config->save();
		}
	}
	public function onEnable(){
		$this->eAPI=EconomyAPI::getInstance();
		self::$instance=$this;
		$this->server=$this->getServer();
		$this->server->getPluginManager()->registerEvents($this, $this);
		$this->Config=new Config($this->getDataFolder().'Config.yml',Config::YAML, [
			'ID' => 0
		]);
		self::$ID=$this->Config->get('ID', 0);
		@mkdir($this->getDataFolder().'Guilds/');
		$this->initGuilds($this->getDataFolder().'Guilds/');
	}
	public function checkName($name){
		if(strlen(preg_replace('#§.#','',$name))>30)
			return '公会名字不能大于30！';
		elseif($name=='无公会')
			return '非法名字！';
		elseif(preg_replace('#§.#','',$name)=='')
			return '公会名字不能为空！';
		elseif(isset($this->guilds[$name]))
			return '这个公会名字已经存在！';
		elseif(strpos($name, '/')!==false)
			return '这个公会名字不能包含/！';
		return true;
	}
	public function initGuilds($path){
		// $id=0;
		foreach(scandir($path) as $afile){
			$fname=explode('.',$afile);
			if($afile=='.' or $afile=='..' or is_dir($path.$afile) or end($fname)!=='yml')continue;
			$name = explode('.', $afile);
			unset($name[count($name)-1]);
			$name = implode('.', $name);
			$conf=new Config($path.$afile,Config::YAML,array());
			// $id++;
			if($conf->get('名字')==''){
				unlink($path.$afile);
				continue;
			}
			$this->addGuilds(new Society(new Config($path.$afile,Config::YAML,array())));
		}
		
		// $this->Config->set('ID', $id);
		// $this->Config->save();
		$this->updateGuildBalance();
		$this->updateGuildCount();
		$this->updateGuildIntegral();
	}
	public function addGuilds(Society $society){
		$this->guilds[$society->getName()] = $society;
	}
	public function delGuilds(Society $society){
		unset($this->guilds[$society->getName()]);
	}
	public static function help(Player $sender){
		$sender->sendMessage('§l§o§a创建新的公会§d/公会 创建 公会名字');
		$sender->sendMessage('§l§o§a修改公会的名字§d/公会 修改名字 新名字');
		$sender->sendMessage('§l§o§a邀请玩家加入至你的公会§d/公会 邀请 玩家ID');
		$sender->sendMessage('§l§o§a解散你的公会§d/公会 解散');
		$sender->sendMessage('§l§o§a踢人§d/公会 移出本公会 玩家ID');
		$sender->sendMessage('§l§o§a设置管理员§d/公会 设置管理员 玩家ID');
		$sender->sendMessage('§l§o§a转让公会§d/公会 转让 玩家ID');
		$sender->sendMessage('§l§o§a设置副会长§d/公会 设置副会长 玩家ID');
		$sender->sendMessage('§l§o§a公会列表§d/公会 列表');
		$sender->sendMessage('§l§o§a移除管理员§d/公会 移除管理员 玩家ID');
		$sender->sendMessage('§l§o§a删除副会长§d/公会 删除副会长');
		$sender->sendMessage('§l§o§a查看公会成员§d/公会 成员');
		$sender->sendMessage('§l§o§a全员集合§d/公会 集合');
		$sender->sendMessage('§l§o§a查看自己公会的管理员§d/公会 管理员列表');
        $sender->sendMessage('§l§o§a申请加入一个公会§d/公会 申请加入 公会ID');
		$sender->sendMessage('§l§o§a查看公会信息§d/公会 信息 [可选公会ID]');
		$sender->sendMessage('§l§o§a同意申请公会§d/公会 同意加入');
		$sender->sendMessage('§l§o§a拒绝申请公会§d/公会 拒绝加入');
		$sender->sendMessage('§l§o§a退出公会§d/公会 退出公会');
		$sender->sendMessage('§l§o§a捐赠排行§d/公会 捐赠排行');
		$sender->sendMessage('§l§o§a捐赠公会§d/公会 捐赠 捐赠橙币数');
		$sender->sendMessage('§l§o§a升级公会 一人10000橙币§d/公会 升级人数 附加人数');
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(!($sender instanceof Player))return $sender->sendMessage('请在游戏内执行');
		if(!isset($args[0]))return self::help($sender);
		switch(strtolower($args[0])){
		case '创建':
			$money=$this->eAPI->myMoney($sender);
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 创建 公会名字');
			}elseif(($guild=$this->getGuild($sender))!==false){
				$sender->sendMessage(self::HEAD.'c请先退出你当前公会！');
			}elseif($money<50000){
				$sender->sendMessage(self::HEAD.'c创建公会需要50000而你只有'.$money);
			}elseif($this->checkName($args[1])!==true){
				$sender->sendMessage(self::HEAD.'c'.$this->checkName($args[1]));
			}else{
				$this->addGuilds(new Society(new Config($this->getDataFolder().'Guilds/'.$args[1].'.yml',Config::YAML,[
					'ID'=>self::$ID++,
					'名字'=>$args[1],
					'会长'=>strtolower($sender->getName()),
					'副会长'=>null,
					'管理员'=>[],
					'成员'=>[],
					'申请名单'=>[],
					'最大人数'=>10,
					'成员捐赠'=>[
						strtolower($sender->getName()) => 0
					],
					'公会积分'=>0,
					'基地'=>false,
					'总捐赠'=>0
				])));
				$this->eAPI->reduceMoney($sender, 50000, '创建公会');
				$sender->sendMessage(self::HEAD.'a恭喜你创建成功！');
				$sender->setGuild($args[1]);
				$this->getServer()->broadcastMessage(('§a玩家'.$sender->getName().'创建了公会ID'.$args[1].'§r ID:'.(self::$ID-1) .'快去加入吧！'));
			}
		break;
		case '升级人数':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 升级人数 附加人数');
			}elseif(($guild=$this->getGuild($sender))===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会！');
			}elseif($guild->getVoicPresident()!=strtolower($sender->getName()) and $guild->getPresident()!=strtolower($sender->getName())){
				$sender->sendMessage(self::HEAD.'c你没有足够的权限，需要副会长或会长');
			}else{
				$money=(int)$args[1]*10000;
				if($money>$guild->getBalance())return $sender->sendMessage(self::HEAD.'c公会基金不够升级指定人数！');
				$guild->addBalance(0-$money);
				$guild->addMaxNumber((int)$args[1]);
				$sender->sendMessage(self::HEAD.'a成功升级'.(int)$args[1].'人数！');
			}
		break;
		case '捐赠排行':
			if(($guild=$this->getGuild($sender))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
			$p=$guild->getMemberDonations();
			arsort($p);
			$message='§a捐赠排行----------第'.(isset($args[1])?(int)$args[1]:1).'页，共'.ceil(count($p)/10).'----------§e'.PHP_EOL;
			if(isset($args[1]) AND (int)$args[1]>1 AND count($p)>10){
				$page=(int)$args[1]-1;
				$s=0;
				foreach($p as $name=>$count){
					$s++;
					if($s<=10*$page)continue;
					$message.='名字:§d'.$name.'  §a捐赠:§d'.$count.PHP_EOL;
					if($s==10*($page+1))break;
				}
			}else{
				$s=0;
				foreach($p as $name=>$count){
					$message.='名字:§d'.$name.'  §a捐赠:§d'.$count.PHP_EOL;
					if(++$s==10)break;
				}
			}
			$sender->sendMessage($message.'§a------------------------------');
		break;
		case '管理员列表':
			if(($guild=$this->getGuild($sender))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
			$p=$guild->getAdmins();
			$i=0;
			$result='§a管理员列表----------第'.(isset($args[1])?(int)$args[1]:1).'页，共'.ceil(count($p)/25).'----------§e'.PHP_EOL;
			$page=($args[1]??1)-1;
			$h=0;
			foreach($p as $name){
				if($i++<$page*25){
					continue;
				}
				if(($i-1)%5==0){
					$result.=$name;
				}else{
					$result.=','.$name;
				}
				if($i%5==0){
					$result.=PHP_EOL;
					if(++$h>5)break;
				}
			}
			$sender->sendMessage($result);
		break;
		case '信息':
			if(isset($args[1])){
				if(($guild=$this->getGuildById($args[1]))==false){
					return $sender->sendMessage(self::HEAD.'c不存在这个公会！');
				}
			}else{
				if(($guild=$this->getGuild($sender))===false){
					return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
				}
			}
			$mess='§a-----------'.$guild->getName().'------------'.PHP_EOL;
			$mess.='§l§6公会ID:'.$guild->getID().PHP_EOL;
			$mess.='§l§d会长:'.$guild->getPresident().PHP_EOL;
			if($guild->getVoicPresident()!==null)$mess.='§l§d副会长:'.$guild->getVoicPresident().PHP_EOL;
			$mess.='§l§a管理员数量:'.count($guild->getAdmins()).PHP_EOL;
			$mess.='§l§c成员数量:'.count($guild->getMembers()).'/'.$guild->getMaxNumber().PHP_EOL;
			$mess.='§l§2公会积分:'.$guild->getIntegral().PHP_EOL;
			$mess.='§l§2公会基金:'.$guild->getBalance().PHP_EOL;
			$sender->sendMessage($mess);
		break;
		case '修改名字':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 修改名字 新名字');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->isAdmin($sender)===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
			}elseif($guild->getBalance()<10000){
				$sender->sendMessage(self::HEAD.'c修改名字需要10000公会基金 你的公会只有'.$guild->getBalance());
			}elseif($this->checkName($args[1])!==true){
				$sender->sendMessage(self::HEAD.'c'.$this->checkName($args[1]));
			}else{
				$guild->addBalance(-10000);
				$this->delGuilds($guild);
				$guild->Rename($args[1]);
				$this->addGuilds($guild);
				$sender->sendMessage(self::HEAD.'a改名成功！！');
			}
		break;
		case '集合':
			if(($guild=$this->getGuild($sender))===false or !$guild->isAdmin($sender)){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
			}else{
				$guild->Collection($sender);
				$sender->sendMessage(self::HEAD.'a请求完毕');
			}
		break;
		case '基地':
			if(($guild=$this->getGuild($sender))===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会！');
			}elseif(($base=$guild->getBase())===null){
				$sender->sendMessage(self::HEAD.'c你的公会还没设置基地！');
			}else{
				$sender->teleport($base);
				$sender->sendMessage(self::HEAD.'a已将您传送之公会基地！');
			}
		break;
		case '设置基地':
			if(($guild=$this->getGuild($sender))===false or ($guild->getPresident()!=strtolower($sender->getName()) and $guild->getVoicPresident()!=strtolower($sender->getName()))){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长或者副会长！');
			}elseif(!in_array($sender->getLevel()->getName(), ['dp', 'land', 'jm'])){
				$sender->sendMessage(self::HEAD.'c您只能在居民区或者公会区设置公会的基地！！');
			}else{
				$sender->setBase($sender);
				$sender->sendMessage(self::HEAD.'a设置完成！');
			}
		break;
		case '捐赠':
			$money=$this->eAPI->myMoney($sender);
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 捐赠 橙币数量');
			}elseif(($guild=$this->getGuild($sender))===false){
				$sender->sendMessage(self::HEAD.'c你还没公会呢！');
			}elseif($sender->isOP()){
				$sender->sendMessage(self::HEAD.'cOP不能捐赠噢');
			}elseif($money<(int)$args[1]){
				$sender->sendMessage(self::HEAD.'c你的钱不够捐赠！');
			}else{
				$this->eAPI->reduceMoney($sender, (int)$args[1], '公会捐赠');
				$guild->addMemberDonation($sender, (int)$args[1]);
				$guild->sendAdminMessage(self::HEAD.'e玩家'.$sender->getName().'对公会捐赠了'.(int)$args[1].'橙币！');
				$sender->sendMessage(self::HEAD.'a捐赠成功 感谢您对公会的一份支持！');
			}
		break;
		case '同意集合':
			if(($guild=$this->getGuild($sender))===false){
				$sender->sendMessage(self::HEAD.'c你还没公会呢！');
			}elseif($guild->getCollectionPos()===null){
				$sender->sendMessage(self::HEAD.'c你的公会没邀请你们集合！');
			}else{
				$sender->teleport($guild->getCollectionPos());
				$sender->sendMessage(self::HEAD.'a传送成功！');
			}
		break;
		case '解散':
			if(!isset($args[1]) or $args[1]!=='confirm'){
				$sender->sendMessage(self::HEAD.'c确定解散输入/公会 解散 confirm');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->getPresident()!==strtolower($sender->getName())){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
			}else{
				$this->delGuilds($guild);
				$guild->Destruction();
				$sender->sendMessage(self::HEAD.'e解散成功！');
			}
		break;
		case '申请列表':
			if(($guild=$this->getGuild($sender))===false or $guild->isAdmin($sender)===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
			}else{
				$s='§l§d';
				foreach($guild->getApplys() as $name){
					$s.=$name.'、';
				}
				$sender->sendMessage(self::HEAD.'e当前有'.count($guild->getApplys()).'个玩家正在申请加入:'.PHP_EOL .$s);
			}
		break;
		case '同意加入':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 同意加入 申请者ID');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->isAdmin($sender->getName())===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
			}elseif(count($guild->getMembers())>=$guild->getMaxNumber()){
				$sender->sendMessage(self::HEAD.'a你的公会已经满了！');
			}elseif($this->getGuild($args[1])!==false){
				$sender->sendMessage(self::HEAD.'c玩家已经加过公会了！');
			}else{
				if($guild->isApplysPlayer($args[1])){
					if($this->getGuild($args[1])!==false){
						$sender->sendMessage(self::HEAD.'c对方已加入其他公会了！');
					}else{
						$guild->addMember($args[1]);
						if($target=$this->server->getPlayerExact($args[1]))
							$target->sendMessage(self::HEAD.'你申请的公会同意了你的申请！');
						$sender->sendMessage(self::HEAD.'a成功同意了'.$args[1].'的申请！');
					}
					$guild->removeApplysPlayer($args[1]);
				}else{
					$sender->sendMessage(self::HEAD.'c这个玩家没有申请加入你们的公会！');
				}
			}
		break;
		case '拒绝加入':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 同意加入 申请ID');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->isAdmin($guild,$sender->getName())===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
			}else{
				if($guild->isApplysPlayer($args[1])){
					$guild->removeApplysPlayer($args[1]);
					if($target=$this->server->getPlayerExact($args[1]))
					$target->sendMessage(self::HEAD.'c你申请的公会拒绝了你的申请！');
					$sender->sendMessage(self::HEAD.'a成功拒绝了'.$args[1].'的申请！');
				}else{
					$sender->sendMessage(self::HEAD.'c这个玩家没有申请加入你们的公会！');
				}
			}
		break;
		case '申请加入'://TODO
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 申请加入 公会ID');
			}elseif(($guild=$this->getGuildById($args[1]))===false){
				$sender->sendMessage(self::HEAD.'c不存在ID为:'.$args[1].'的公会，输入/公会 列表 查看全部公会！');
			}elseif($this->getGuild($sender)!==false){
				$sender->sendMessage(self::HEAD.'c你已经加过公会了！');
			}elseif($guild->isApplysPlayer($sender)){
				$sender->sendMessage(self::HEAD.'c你已经申请过了！');
			}elseif(count($guild->getMembers())>=$guild->getMaxNumber()){
				$sender->sendMessage(self::HEAD.'a目标公会已经满了！');
			}else{
				$guild->addApplysPlayer($sender);
				$sender->sendMessage(self::HEAD.'a申请成功，请等待结果！');
			}
		break;
		case '列表':
			$message='§a----------第'.(isset($args[1])?(int)$args[1]:1).'页，共'.ceil(count($this->guilds)/15).'----------§e'.PHP_EOL;
			if(isset($args[1]) AND (int)$args[1]>1 AND count($this->guilds)>15){
				$page=(int)$args[1]-1;
				$s=0;
				foreach($this->guilds as $guild){
					$s++;
					if($s<=15*$page)continue;
					$message.='§e公会名字:§d'.$guild->getName().'§r ID:'.$guild->getID().' §a公会成员数量:§d'.count($guild->getAllMember()).PHP_EOL;
					if($s==15*($page+1))break;
				}
			}else{
				$s=0;
				foreach($this->guilds as $name=>$guild){
					$message.='§e公会名字:§d'.$guild->getName().'§r ID:'.$guild->getID().' §a公会成员数量:§d'.count($guild->getAllMember()).PHP_EOL;
					if(++$s==15)break;
				}
			}
			$sender->sendMessage($message.'§a------------------------------');
		break;
		case '设置管理员':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 设置管理员 目标ID');
			}elseif(($guild=$this->getGuild($sender))===false or ($guild->getPresident()!=strtolower($sender->getName()) and $guild->getVoicPresident()!=strtolower($sender->getName()))){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长或者副会长！');
			}elseif($guild->hasAdmin($args[1])){
				$sender->sendMessage(self::HEAD.'c目标已经是管理员');
			}elseif(!$guild->isMembers($args[1])){
				$sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
			}else{
				$guild->addAdmin($args[1]);
				$sender->sendMessage(self::HEAD.'a设置成功');
				if($target=$this->getServer()->getPlayerExact($args[1]))$target->sendMessage(self::HEAD.'d恭喜你升级成你公会的管理员！');
			}
		break;
		case '设置副会长':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 设置副会长 目标ID');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->getPresident()!=strtolower($sender->getName())){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
			}elseif($guild->getVoicPresident()==strtolower($args[1])){
				$sender->sendMessage(self::HEAD.'c目标已经是副会长');
			}elseif(!$guild->isMembers($args[1])){
				$sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
			}else{
				$guild->setVoicPresident($args[1]);
				$sender->sendMessage(self::HEAD.'a设置成功');
				if($target=$this->getServer()->getPlayerExact($args[1]))$target->sendMessage(self::HEAD.'d恭喜你升级成你公会的副会长！');
				if($guild->hasAdmin($args[1])){
					$guild->removeAdmin($args[1]);
				}
			}
		break;
		case '删除副会长':
			if(($guild=$this->getGuild($sender))===false or $guild->getPresident()!=strtolower($sender->getName())){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
			}elseif($guild->getVoicPresident()==null){
				$sender->sendMessage(self::HEAD.'c你的公会没有副会长！');
			}else{
				if($target=$this->getServer()->getPlayerExact($guild->getVoicPresident()))$target->sendMessage(self::HEAD.'d会长已经取消了你的副会长职位！');
				$guild->setVoicPresident(null);
				$sender->sendMessage(self::HEAD.'a删除成功');
			}
		break;
		case '转让公会':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 转让 目标ID');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->getPresident()!=strtolower($sender->getName())){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
			}elseif($guild->getPresident()==strtolower($args[1])){
				$sender->sendMessage(self::HEAD.'c你不能转让给自己！');
			}elseif(!$guild->isMembers($args[1])){
				$sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
			}else{
				$guild->setPresident($args[1]);
				if($guild->hasAdmin($args[1])){
					$guild->removeAdmin($args[1]);
				}
				if($guild->getVoicPresident()==strtolower($args[1])){
					$guild->setVoicPresident(null);
				}
				$sender->sendMessage(self::HEAD.'a设置成功');
				if($target=$this->getServer()->getPlayerExact($args[1]))$target->sendMessage(self::HEAD.'d恭喜你升级成你公会的会长！');
				$guild->addMember($sender, true);
			}
		break;
		case '移除管理员':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 移除管理员 目标ID');
			}elseif(($guild=$this->getGuild($sender))===false or ($guild->getPresident()!=strtolower($sender->getName()) and $guild->getVoicPresident()!=strtolower($sender->getName()))){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或 你不是公会会长或者副会长！');
			}elseif(!$this->hasAdmin($args[1])){
				$sender->sendMessage(self::HEAD.'c目标不是管理员');
			}elseif(!$guild->isMembers($args[1])){
				$sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
			}else{
				$guild->removeAdmin($args[1]);
				$sender->sendMessage(self::HEAD.'a设置成功');
				if($target=$this->getServer()->getPlayerExact($args[1]))$target->sendMessage(self::HEAD.'c你的管理员已被撤销');
			}
		break;
		break;
		case '邀请':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 邀请 目标ID');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->isAdmin($sender)===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
			}elseif($guild->isMembers($args[1])){
				$sender->sendMessage(self::HEAD.'c目标已经是这个公会的成员了！');
			}elseif(count($guild->getMembers())>=$guild->getMaxNumber()){
				$sender->sendMessage(self::HEAD.'a你的公会已经满了！');
			}else{
				$target=$this->getServer()->getPlayerExact($args[1]);
				if(!$target){
					$sender->sendMessage(self::HEAD.'c目标不在线！');
				}else{
					$target->sendMessage(self::HEAD.'e玩家'.$sender->getName().'邀请你加入'.$guild->getName().'输入/公会 "同意 or 拒绝"');
					$this->Invite[$target->getName()]=[$guild,$sender];
					$sender->sendMessage(self::HEAD.'a成功邀请目标玩家'.$target->getName());
				}
			}
		break;
		case '同意':
			if(!isset($this->Invite[$sender->getName()])){
				$sender->sendMessage(self::HEAD.'c没有人邀请你加入工会！');
			}elseif($this->getGuild($sender)!==false){
				$sender->sendMessage(self::HEAD.'c你已经加入公会了！');
			}elseif(count($this->Invite[$sender->getName()][0]->getMembers())>=$this->Invite[$sender->getName()][0]->getMaxNumber()){
				$sender->sendMessage(self::HEAD.'a你的公会已经满了！');
			}else{
				$guild=$this->Invite[$sender->getName()][0];
				$guild->addMember($sender);
				$guild->removeApplysPlayer($sender);
				$sender->sendMessage(self::HEAD.'a成功同意');
				if($this->Invite[$sender->getName()][1]->isOnline()){
					$this->Invite[$sender->getName()][1]->sendMessage(self::HEAD.'a玩家'.$sender->getName().'同意了你的邀请');
				}
				unset($this->Invite[$sender->getName()]);
			}
		break;
		case '拒绝':
			if(!isset($this->Invite[$sender->getName()])){
				$sender->sendMessage(self::HEAD.'c没有人邀请你加入工会！');
			}elseif(($guild=$this->getGuild($sender))!==false){
				$sender->sendMessage(self::HEAD.'c你已经加入公会了！');
			}else{
				if($this->Invite[$sender->getName()][1]->isOnline()){
					$this->Invite[$sender->getName()][1]->sendMessage(self::HEAD.'a玩家'.$sender->getName().'拒绝了你的邀请');
				}
				$sender->sendMessage(self::HEAD.'a成功拒绝');
				unset($this->Invite[$sender->getName()]);
			}
			break;
		case '移出本公会':
			if(!isset($args[1])){
				$sender->sendMessage(self::HEAD.'c用法/公会 移出本公会 目标ID');
			}elseif(($guild=$this->getGuild($sender))===false or $guild->isAdmin($sender->getName())===false){
				$sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
			}elseif($guild->hasAdmin($sender) AND ($guild->getPresident()!=strtolower($sender->getName()) and $guild->getVoicPresident()!=strtolower($sender->getName()))){
				$sender->sendMessage(self::HEAD.'c你没权限踢管理员！');
			}elseif(!$guild->isMembers($args[1])){
				$sender->sendMessage(self::HEAD.'c公会没有这个成员！');
			}elseif($guild->getPresident()===strtolower($args[1]) or ($guild->hasAdmin($sender) and $guild->getVoicPresident()===strtolower($args[1]))){
				$sender->sendMessage(self::HEAD.'c不能踢会长或副会长！');
			}else{
				$guild->removeMember($args[1]);
				if($target=$this->server->getPlayerExact($args[1])){
					$target->sendMessage(self::HEAD.'c你已经被管理员移出当前公会！');
				}
				$sender->sendMessage(self::HEAD.'a移除工会成功！');
			}
		break;
		case '成员':
			if(($guild=$this->getGuild($sender))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
			$p=$guild->getAllMember();
			$i=0;
			$result='§a成员列表----------第'.(isset($args[1])?(int)$args[1]:1).'页，共'.ceil(count($p)/25).'----------§e'.PHP_EOL;
			$page=($args[1]??1)-1;
			$h=0;
			foreach($p as $name){
				if($i++<$page*25){
					continue;
				}
				if(($i-1)%5==0){
					$result.=$name;
				}else{
					$result.=','.$name;
				}
				if($i%5==0){
					$result.=PHP_EOL;
					if(++$h>5)break;
				}
			}
			$sender->sendMessage($result);
		break;
		case '退出公会':
			if(($guild=$this->getGuild($sender))===false){
				$sender->sendMessage(self::HEAD.'c你还没公会呢！');
				$sender->setGuild('无公会');
			}elseif($guild->getPresident()===strtolower($sender->getName())){
				$sender->sendMessage(self::HEAD.'c作为会长不能退出自己的公会，请执行/公会 解散');
			}else{
				$guild->sendAdminMessage(self::HEAD.'e玩家'.$sender->getName().'退出了你们的工会！');
				$guild->removeMember($sender);
				$sender->sendMessage(self::HEAD.'a退出公会成功！');
			}
		break;
		default:
			return self::help($sender);
		break;
		}
	}
	public function onJoinEvent(PlayerJoinEvent $event){
		$player=$event->getPlayer();
		if(($guild=$this->getGuild($player))===false)return;
		if($guild->isAdmin($player) and count($guild->getApplys())>0){
			$player->sendMessage(self::HEAD.'a当前有'.count($guild->getApplys()).'个玩家正在申请加入你的公会 输入§e/公会 申请列表§a查看详细', true);//参数2是强制
		}
	}
	public function onQuitEvent(PlayerQuitEvent $event){
		$name=$event->getPlayer()->getName();
		unset($this->Invite[$name]);
	}
	public function getGuild($player){
		if(!($player instanceof Player)){
			if(!($this->getServer()->getPlayerExact($player) instanceof Player)){//离线玩家
				$player=strtolower($player);
				if(file_exists(\pocketmine\DATA .'players/'.$player.'.dat')){
					$nbt=$this->server->getOfflinePlayerData($player);
					if(!($nbt instanceof CompoundTag))return false;
					$guild=$nbt['Guild'];
					return $this->guilds[$guild]??false;
				}
				return false;
			}else{
				$player = $this->getServer()->getPlayerExact($player);
			}
		}
		$guild=$player->getGuild();
		if($guild=='无公会')return false;
		return $this->guilds[$guild]??false;
	}
	public function getGuildById($id){
		foreach($this->guilds as $guild){
			if($guild->getId()==$id){
				return $guild;
			}
		}
		return false;
	}
	public function addHead(Player $player){
		$guild=$this->getGuild($player);
		if($guild!==false){
			$guild->addIntegral(1);
		}
	}
	public function updateGuildCount(){
		if(Society::$CountRanking instanceof \LTCraft\FloatingText){
			$arr=[];
			foreach($this->guilds as $guild)
				$arr[$guild->getID()]=count($guild->getAllMember());
			arsort($arr);
			$i=0;
			$text='§l§e公会人数排行榜'."\n";
			foreach($arr as $id=>$count){
				if(++$i>10)break;
				$text.='§a'.$i .'#工会名字'.$this->getGuildById($id)->getName() .' §r§l§d人数:'.$count .' ID:'.$id."\n";
			}
			Society::$CountRanking->updateAll($text);
		}
	}
	public function updateGuildBalance(){
		if(Society::$BalanceRanking instanceof \LTCraft\FloatingText){
			$arr=[];
			foreach($this->guilds as $guild)
				$arr[$guild->getID()]=$guild->getBalance();
			arsort($arr);
			$i=0;
			$text='§l§e公会捐赠排行榜'."\n";
			foreach($arr as $id=>$balance){
				if(++$i>10)break;
				$text.='§a'.$i .'#工会名字'.$this->getGuildById($id)->getName() .' §r§l§d捐赠:'.$balance .' ID:'.$id."\n";
			}
			Society::$BalanceRanking->updateAll($text);
		}
	}
	public function updateGuildIntegral(){
		if(Society::$IntegralRanking instanceof \LTCraft\FloatingText){
			$arr=[];
			foreach($this->guilds as $guild)
				$arr[$guild->getID()]=$guild->getIntegral();
			arsort($arr);
			$i=0;
			$text='§l§e公会积分排行榜'."\n";
			foreach($arr as $id=>$integral){
				if(++$i>10)break;
				$text.='§a'.$i .'#工会名字'.$this->getGuildById($id)->getName() .' §r§l§d积分:'.$integral .' ID:'.$id."\n";
			}
			Society::$IntegralRanking->updateAll($text);
		}
	}
}

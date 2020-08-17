<?php
namespace LTSociety;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerJoinEvent,PlayerQuitEvent};
use onebone\economyapi\EconomyAPI;
class Main extends PluginBase implements Listener{
	public static $instance=null;
	public $Invite=[];
	public $guilds=[];
	public $PlayersGuild=[];
	public $playerGuild=[];
	public $tp=[];
	public static function getInstance(){
		return self::$instance;
	}
	const HEAD='§l§2[§aL§eT§dS§6o§2c§1i§5e§7t§dy]§';
	public function onEnable(){
		$this->eAPI=EconomyAPI::getInstance();
		self::$instance=$this;
		$this->server=$this->getServer();
		@mkdir($this->getDataFolder().'Guilds/');
		$this->playerGuild=new Config($this->getDataFolder().'playerGuild.yml',Config::YAML,[]);
		$this->initGuilds($this->getDataFolder().'Guilds/');
	}
	public function initGuilds($path){
		foreach(scandir($path) as $afile){
			$fname=explode('.',$afile);
			if($afile=='.' or $afile=='..' or is_dir($path.'/'.$afile) or end($fname)!=='yml')continue;
			$name = explode('.', $afile);
			unset($name[count($name)-1]);
			$name = implode('.', $name);
			$this->guilds[strtolower($name)]=new Config($path.'/'.$afile,Config::YAML,array());
			continue;
			if($this->guilds[strtolower($name)]->get('最大人数',0)===0){
				$this->guilds[strtolower($name)]->set('最大人数',count($this->guilds[strtolower($name)]->get('成员'))+2);
				$this->guilds[strtolower($name)]->save();
			}
			if($this->guilds[strtolower($name)]->get('成员捐赠')==false){
				$arr=[];
				foreach($this->guilds[strtolower($name)]->get('成员') as $k=>$p)
					$arr[$p]=0;
				$this->guilds[strtolower($name)]->set('成员捐赠',$arr);
				$this->guilds[strtolower($name)]->set('总捐赠',0);
				$this->guilds[strtolower($name)]->save();
			}
		}
	}
	public function checkName($name){
		return strlen(preg_replace('#§.#','',$name))<30;
	}
	public static function help(Player $sender){
		$sender->sendMessage('§l§o§a创建新的公会§d/公会 创建 公会名字');
		$sender->sendMessage('§l§o§a修改公会的名字§d/公会 修改名字 新名字');
		$sender->sendMessage('§l§o§a邀请玩家加入至你的公会§d/公会 邀请 玩家ID');
		$sender->sendMessage('§l§o§a解散你的公会§d/公会 解散');
		$sender->sendMessage('§l§o§a踢人§d/公会 移除本公会 玩家ID');
		$sender->sendMessage('§l§o§a设置管理员§d/公会 设置管理员 玩家ID');
		$sender->sendMessage('§l§o§a转让公会§d/公会 转让 玩家ID');
		$sender->sendMessage('§l§o§a设置副会长§d/公会 设置副会长 玩家ID');
		$sender->sendMessage('§l§o§a公会列表§d/公会 列表');
		$sender->sendMessage('§l§o§a移除管理员§d/公会 移除管理员 玩家ID');
		$sender->sendMessage('§l§o§a删除副会长§d/公会 删除副会长 玩家ID');
		$sender->sendMessage('§l§o§a查看公会成员§d/公会 成员');
		$sender->sendMessage('§l§o§a查看公会信息§d/公会 我的公会');
		$sender->sendMessage('§l§o§a全员集合§d/公会 集合');
		$sender->sendMessage('§l§o§a查看自己公会的管理员§d/公会 管理员列表');
		$sender->sendMessage('§l§o§a退出公会§d/公会 退出公会');
		$sender->sendMessage('§l§o§a捐赠排行§d/公会 捐赠排行');
		$sender->sendMessage('§l§o§a捐赠公会§d/公会 捐赠 捐赠橙币数');
		$sender->sendMessage('§l§o§a升级公会 一人10000橙币§d/公会 升级人数 附加人数');
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
	if(!($sender instanceof Player))return $sender->sendMessage('请在游戏内执行');
	if(!isset($args[0]))return self::help($sender);
	$money=$this->eAPI->myMoney($sender);
	switch(strtolower($args[0])){
	case '创建':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 创建 公会名字');
		if($this->exists($args[1])!==false)return $sender->sendMessage(self::HEAD.'c这个公会已经存在了，换个名字吧！');
		if($money<50000)return $sender->sendMessage(self::HEAD.'c创建公会需要50000而你只有'.$money);
		if(!$this->checkName(trim($args[1])))return $sender->sendMessage(self::HEAD.'c名字不能超过30！');
		$this->guilds[trim(preg_replace('#§.#','',strtolower($args[1])))]=new Config($this->getDataFolder().'Guilds/'.trim(preg_replace('#§.#','',strtolower($args[1]))).'.yml',Config::YAML,[
			'名字'=>trim($args[1]),
			'会长'=>strtolower($sender->getName()),
			'副会长'=>null,
			'管理员'=>[],
			'成员'=>[],
			'申请名单'=>[],
			'最大人数'=>10,
			'成员捐赠'=>[],
			'公会积分'=>0,
			'总捐赠'=>0
		]);
		$this->eAPI->reduceMoney($sender, 50000, true, "test");
		$sender->sendMessage(self::HEAD.'a恭喜你创建成功！');
		$this->playerGuild->set(strtolower($sender->getName()),trim($args[1]));
		$this->playerGuild->save();
		$this->getServer()->broadcastMessage('§a玩家'.$sender->getName().'创建了'.$args[1].'快去加入吧！');
	break;
	case '升级人数':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 升级人数 附加人数');
		if(($guild=$this->getGuild($sender->getName()))===false)return $sender->sendMessage(self::HEAD.'c你还没加入公会！');
		if($guild->get('副会长')!=strtolower($sender->getName()) and $guild->get('会长')!=strtolower($sender->getName()))return $sender->sendMessage(self::HEAD.'c你没有足够的权限，需要副会长或会长');
		$money=(int)$args[1]*10000;
		if($money>$guild->get('总捐赠'))return $sender->sendMessage(self::HEAD.'c公会基金不够升级指定人数！');
		$guild->set('总捐赠',$guild->get('总捐赠')-$money);
		$guild->set('最大人数',$guild->get('最大人数')+(int)$args[1]);
		$guild->save();
		$sender->sendMessage(self::HEAD.'a成功升级'.(int)$args[1].'人数！');
	break;
	case '捐赠排行':
		if(($guild=$this->getGuild($sender->getName()))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
		$p=$guild->get('成员捐赠');
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
		if(($guild=$this->getGuild($sender->getName()))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
		$i=0;
		$color=['§a','§d','§e','§c','§1','§2','§3','§5','§6'];
		$result='§l§d全部管理员如下'.PHP_EOL.$color[mt_rand(0,8)];
		foreach($guild->get('管理员') as $name){
			if($i!==0)$result.=','.$name;
			else $result.=$name;
			if(++$i===5)$result.=PHP_EOL.$color[mt_rand(0,8)];
		}
		$sender->sendMessage($result);
	break;
	case '修改名字':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 修改名字 新名字');
		if(($guild=$this->getGuild($sender->getName()))===false or $this->isAdmin($guild,$sender->getName())===false)
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
		if($money<10000)return $sender->sendMessage(self::HEAD.'c修改名字需要10000而你只有'.$money);
		if(!$this->checkName(trim($args[1])))return $sender->sendMessage(self::HEAD.'c名字不能超过30！');
		if($this->exists($args[1])!==false)return $sender->sendMessage(self::HEAD.'c这个公会已经存在了，换个名字吧！');
		$this->eAPI->reduceMoney($sender, 10000, true, "test");
		$n=$guild->get('名字');
		unset($this->guilds[trim(preg_replace('#§.#','',strtolower($n)))]);
		$guild->set('名字',trim($args[1]));
		$guild->save();
		foreach($guild->get('成员') as $name){
			$this->playerGuild->set($name,trim($args[1]));
		}
		$this->playerGuild->set($guild->get('会长'),trim($args[1]));
		$this->playerGuild->save();
		unset($guild);
		rename($this->getDataFolder().'Guilds/'.trim(preg_replace('#§.#','',strtolower($n))).'.yml',$this->getDataFolder().'Guilds/'.trim(strtolower(preg_replace('#§.#','',$args[1]))).'.yml');
		$this->guilds[trim(strtolower(preg_replace('#§.#','',$args[1])))]=new Config($this->getDataFolder().'Guilds/'.trim(strtolower(preg_replace('#§.#','',$args[1]))).'.yml',Config::YAML,array());
		$sender->sendMessage(self::HEAD.'a改名成功！！');
	break;
	case '集合':
		if(($guild=$this->getGuild($sender->getName()))===false or $this->isAdmin($guild,$sender->getName())===false)
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
		foreach(array_merge([$guild->get('会长')],$guild->get('成员')) as $playerName){
			$target=$this->getPlayer($playerName);
			if($target AND $target!==$sender){
				$target->sendMessage(self::HEAD.'e你的公会管理员'.$sender->getName().'邀请你们集合到他的位置'.PHP_EOL.'输入/公会 同意集合 or 拒绝集合');
				$this->tp[$target->getName()]=[$guild,$sender];
			}
		}
		$sender->sendMessage(self::HEAD.'a请求完毕');
		break;
	case '捐赠':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 捐赠 橙币数量');
		if(($guild=$this->getGuild($sender->getName()))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
		if($sender->isOP())return $sender->sendMessage(self::HEAD.'cOP不能捐赠噢');
		if($money<(int)$args[1])return $sender->sendMessage(self::HEAD.'c你的钱不够捐赠！');
		$this->eAPI->reduceMoney($sender, (int)$args[1], true, "test");
		$guild->set('总捐赠',$guild->get('总捐赠')+(int)$args[1]);
		$v=$guild->get('成员捐赠');
		$v[strtolower($sender->getName())]+=(int)$args[1];
		$guild->set('成员捐赠',$v);
		$guild->save();
		return $sender->sendMessage(self::HEAD.'a成功捐赠了'.(int)$args[1].'橙币！');
	break;
	case '同意集合':
		if(isset($this->tp[$sender->getName()])){
			if($this->getGuild($sender->getName())!==$this->tp[$sender->getName()][0])return $sender->sendMessage(self::HEAD.'e邀请的那个公会已经不是你的公会了！');
			$sender->teleport($this->tp[$sender->getName()][1]);
			if($this->tp[$sender->getName()][1])$this->tp[$sender->getName()][1]->sendMessage(self::HEAD.'c'.$sender->getName().'同意了你的邀请');
			$sender->sendMessage(self::HEAD.'a传送成功！');
			unset($this->tp[$sender->getName()]);
		}else return $sender->sendMessage(self::HEAD.'c你的公会没邀请你们集合！');
	break;
	case '拒绝集合':
		if(isset($this->tp[$sender->getName()])){
			if($this->getGuild($sender->getName())!==$this->tp[$sender->getName()][0])return $sender->sendMessage(self::HEAD.'e邀请的那个公会已经不是你的公会了！');
			$sender->sendMessage(self::HEAD.'a你拒绝了');
			if($this->tp[$sender->getName()][1])$this->tp[$sender->getName()][1]->sendMessage(self::HEAD.'c'.$sender->getName().'拒绝了你的邀请');
			unset($this->tp[$sender->getName()]);
		}else return $sender->sendMessage(self::HEAD.'c你的公会没邀请你们集合！');
	break;
	case '解散':
		if(!isset($args[1]) or $args[1]!=='confirm')return $sender->sendMessage(self::HEAD.'c确定解散输入/公会 解散 confirm');
		if(($guild=$this->getGuild($sender->getName()))===false or $guild->get('会长')!==strtolower($sender->getName()))
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
		unset($this->guilds[strtolower(preg_replace('#§.#','',$guild->get('名字')))]);
		unlink($this->getDataFolder().'Guilds/'.strtolower(preg_replace('#§.#','',$guild->get('名字'))).'.yml');
		foreach($guild->get('成员') as $name){
			$this->playerGuild->remove($name);
		}
		$this->playerGuild->remove($guild->get('会长'));
		$this->playerGuild->save();
		$sender->sendMessage(self::HEAD.'e解散成功！');
	break;
	case '申请列表':
		if(($guild=$this->getGuild($sender->getName()))===false or $this->isAdmin($guild,$sender->getName())===false)
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
		$s='§l§d';
		foreach($guild->get('申请名单') as $name){
			$s.=$name.'、';
		}
		$sender->sendMessage(self::HEAD.'e当前有'.count($guild->get('申请名单')).'个人正在申请加入:');
		$sender->sendMessage($s);
	break;
	case '同意加入':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 同意加入 申请ID');
		if(($guild=$this->getGuild($sender->getName()))===false or $this->isAdmin($guild,$sender->getName())===false)
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
		$key=array_flip($guild->get('申请名单'));
		if(isset($key[strtolower($args[1])])){
			$list=$guild->get('申请名单');
			unset($list[$key[strtolower($args[1])]]);
			$guild->set('申请名单',$list);
			if($this->getGuild(strtolower($args[1]))!==false){
				$guild->save();
				return $sender->sendMessage(self::HEAD.'c对方已加入其他公会了！');
			}
			$v=$guild->get('成员');
			$v[]=strtolower($args[1]);
			$guild->set('成员',$v);
			$v=$guild->get('成员捐赠');
			$v[strtolower($args[1])]=0;
			$guild->set('成员捐赠',$v);
			$guild->save();
			if($target=$this->getPlayer($args[1]))
				$target->sendMessage(self::HEAD.'你申请的公会同意了你的申请！');
			$sendMessage->sendMessage(self::HEAD.'a成功同意了'.$args[1].'的申请！');
			$this->playerGuild->set(strtolower($target->getName()),$guild->get('名字'));
			$this->playerGuild->save();
		}else return $sender->sendMessage(self::HEAD.'c这个玩家没有申请加入你们的公会！');
	break;
	case '拒绝加入':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 同意加入 申请ID');
		if(($guild=$this->getGuild($sender->getName()))===false or $this->isAdmin($guild,$sender->getName())===false)
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
		$key=array_flip($guild->get('申请名单'));
		if(isset($key[strtolower($args[1])])){
			$list=$guild->get('申请名单');
			unset($list[$key[strtolower($sender->getName())]]);
			$guild->set('申请名单',$list);
			if($target=$this->getPlayer($args[1]))
				$target->sendMessage(self::HEAD.'c你申请的公会拒绝了你的申请！');
			$guild->save();
			$sendMessage->sendMessage(self::HEAD.'a成功拒绝了'.$args[1].'的申请！');
		}else return $sender->sendMessage(self::HEAD.'c这个玩家没有申请加入你们的公会！');
	break;
	case '申请加入':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 申请加入 公会名');
		if(($guild=$this->exists($args[1]))===false)return $sender->sendMessage(self::HEAD.'c不存在这个工会，输入/公会 列表 查看全部公会！');
		if($this->getGuild($sender->getName())!==false)return $sender->sendMessage(self::HEAD.'c你已经加过公会了！');
		if(count($guild->get('成员'))>=$guild->get('最大人数'))return $sender->sendMessage(self::HEAD.'a目标公会已经满了！');
		foreach(array_merge([$guild->get('会长'),$guild->get('副会长')],$guild->get('管理员')) as $op){
			if($op===null)continue;
			$target=$this->getPlayer($op);
			if(!$target)continue;
			$target->sendMessage(self::HEAD.'e玩家'.$sender->getName().'申请加入你们的公会,输入/公会 "同意加入 or 拒绝加入" '.$sender->getName().',同意该请求。');
		}
		$key=array_flip($guild->get('申请名单'));
		if(!isset($key[strtolower($sender->getName())])){
			$v=$guild->get('申请名单');
			$v[]=strtolower($sender->getName());
			$guild->set('申请名单',$v);
			$guild->save();
		}
		$sender->sendMessage(self::HEAD.'a发送成功，请等待结果！');
	break;
	case '我的公会':
		if(($guild=$this->getGuild($sender->getName()))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
		$sender->sendMessage('§d公会名字:§a'.$guild->get('名字'));
		$sender->sendMessage('§d会长:§a'.$guild->get('会长'));
		$sender->sendMessage('§d副会长:§a'.$guild->get('副会长'));
		$sender->sendMessage('§d捐赠钱数:§a'.$guild->get('总捐赠'));
		$sender->sendMessage('§d积分:§a'.$guild->get('公会积分'));
		$sender->sendMessage('§d管理员数量:§a'.count($guild->get('管理员')));
		$sender->sendMessage('§d成员数量:§a'.count($guild->get('成员')).'/'.$guild->get('最大人数'));
	break;
	case '列表':
		$message='§a----------第'.(isset($args[1])?(int)$args[1]:1).'页，共'.ceil(count($this->guilds)/5).'----------§e'.PHP_EOL;
		if(isset($args[1]) AND (int)$args[1]>1 AND count($this->guilds)>5){
			$page=(int)$args[1]-1;
			$s=0;
			foreach($this->guilds as $name=>$guild){
				$s++;
				if($s<=5*$page)continue;
				$message.='公会名字:§d'.$name.'  §a公会成员数量:§d'.count($guild->get('成员')).PHP_EOL;
				if($s==5*($page+1))break;
			}
		}else{
			$s=0;
			foreach($this->guilds as $name=>$guild){
				$message.='公会名字:§d'.$name.'  §a公会成员数量:§d'.count($guild->get('成员')).PHP_EOL;
				if(++$s==5)break;
			}
		}
		$sender->sendMessage($message.'§a------------------------------');
	break;
	case '设置管理员':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 设置管理员 目标ID');
		if(($guild=$this->getGuild($sender->getName()))===false or !($guild->get('会长')!=strtolower($sender->getName()) or $guild->get('副会长')!=strtolower($sender->getName())))
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长或者副会长！');
		if($this->isAdmin($guild,$args[1]))return $sender->sendMessage(self::HEAD.'c目标已经是管理员');
		if(in_array(strtolower($args[1]),$guild->get('成员'))){
		$list=$guild->get('管理员');
		$list[]=strtolower($args[1]);
			$guild->set('管理员',$list);
			$guild->save();
			$sender->sendMessage(self::HEAD.'a设置成功');
			if($target=$this->getServer()->getPlayer($args[0]))$target->sendMessage(self::HEAD.'d恭喜你升级成你公会的管理员！');
		}else return $sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
	break;
	case '设置副会长':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 设置副会长 目标ID');
		if(($guild=$this->getGuild($sender->getName()))===false or $guild->get('会长')!=strtolower($sender->getName()))
			return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
		if($guild->get('副会长')==strtolower($args[1]))return $sender->sendMessage(self::HEAD.'c目标已经是副会长');
		if(in_array(strtolower($args[1]),$guild->get('成员'))){
			$guild->set('副会长',strtolower($args[1]));
			$guild->save();
			$sender->sendMessage(self::HEAD.'a设置成功');
			if($target=$this->getServer()->getPlayer($args[0]))$target->sendMessage(self::HEAD.'d恭喜你升级成你公会的副会长！');
			if(in_array(strtolower($args[1]),$guild->get('管理员'))){
				$key=array_flip($guild->get('管理员'));
				$list=$guild->get('管理员');
				unset($list[$key[strtolower($args[1])]]);
				$guild->set('管理员',$list);
				$guild->save();
			}
		}else return $sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
	break;
	case '转让公会':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 转让 目标ID');
		if(($guild=$this->getGuild($sender->getName()))===false or $guild->get('会长')!=strtolower($sender->getName()))
			return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
		if($guild->get('会长')==strtolower($args[1]))return $sender->sendMessage(self::HEAD.'c你不能转让给自己！');
		if(in_array(strtolower($args[1]),$guild->get('成员'))){
			$guild->set('会长',strtolower($args[1]));
			$guild->save();
			$sender->sendMessage(self::HEAD.'a设置成功');
			if($target=$this->getServer()->getPlayer($args[0]))$target->sendMessage(self::HEAD.'d恭喜你升级成你公会的会长！');
			$this->playerGuild->remove(strtolower($sender->getName()));
			$this->playerGuild->save();
		}else return $sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
	break;
	case '移除管理员':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 移除管理员 目标ID');
		if(($guild=$this->getGuild($sender->getName()))===false or !($guild->get('会长')!=strtolower($sender->getName()) or $guild->get('副会长')!=strtolower($sender->getName())))
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长或者副会长！');
		if(!in_array(strtolower($args[0]),$guild->get('管理员')))return $sender->sendMessage(self::HEAD.'c目标不是管理员');
		if(in_array(strtolower($args[1]),$guild->get('成员'))){
			$key=array_flip($guild->get('管理员'));
			$list=$guild->get('管理员');
			unset($list[$key[strtolower($args[1])]]);
			$guild->set('管理员',$list);
			$sender->sendMessage(self::HEAD.'a设置成功');
			if($target=$this->getServer()->getPlayer($args[0]))$target->sendMessage(self::HEAD.'c你的管理员已被撤销');
		}else return $sender->sendMessage(self::HEAD.'c你的公会没有这个成员！');
	break;
	case '删除副会长':
		if(($guild=$this->getGuild($sender->getName()))===false or $guild->get('会长')!=strtolower($sender->getName()))
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会会长！');
			$guild->set('副会长',null);
			$guild->save();
			$sender->sendMessage(self::HEAD.'a设置成功');
			//if($target=$this->getServer()->getPlayer($args[0]))$target->sendMessage(self::HEAD.'d你的副会长已已经被撤销！');
	break;
	case '邀请':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 邀请 目标ID');
		if(($guild=$this->getGuild($sender->getName()))===false or $this->isAdmin($guild,$sender->getName())===false)
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
		if(count($guild->get('成员'))>=$guild->get('最大人数'))return $sender->sendMessage(self::HEAD.'a你的公会已经满了！');
		$target=$this->getServer()->getPlayer($args[1]);
		if(!$target)return $sender->sendMessage(self::HEAD.'c目标不在线！');
		$target->sendMessage(self::HEAD.'e玩家'.$sender->getName().'邀请你加入'.$guild->get('名字').'输入/公会 "同意 or 拒绝"');
		$this->Invite[$target->getName()]=[$guild,$sender];
		$sender->sendMessage(self::HEAD.'a成功邀请目标玩家'.$target->getName());
	break;
	case '同意':
		if(!isset($this->Invite[$sender->getName()]))return $sender->sendMessage(self::HEAD.'c没有人邀请你加入工会！');
		$guild=$this->Invite[$sender->getName()][0];
		$v=$guild->get('成员');
		$v[]=strtolower($sender->getName());
		$guild->set('成员',$v);
		$v=$guild->get('成员捐赠');
		$v[strtolower($sender->getName())]=0;
		$guild->set('成员捐赠',$v);
		$guild->save();
		$this->playerGuild->set(strtolower($sender->getName()),$guild->get('名字'));
		$this->PlayersGuild[$sender->getName()]=$guild->get('名字');
		$this->playerGuild->save();
		$sender->sendMessage(self::HEAD.'a成功同意');
		if($this->Invite[$sender->getName()][1])$this->Invite[$sender->getName()][1]->sendMessage(self::HEAD.'a玩家'.$sender->getName().'同意了你的邀请');
		unset($this->Invite[$sender->getName()]);
	break;
	case '拒绝':
		if(!isset($this->Invite[$sender->getName()]))return $sender->sendMessage(self::HEAD.'c没有人邀请你加入工会！');
		if($this->Invite[$sender->getName()][1])$this->Invite[$sender->getName()][1]->sendMessage(self::HEAD.'a玩家'.$sender->getName().'拒绝了你的邀请');
		$sender->sendMessage(self::HEAD.'a成功拒绝');
		unset($this->Invite[$sender->getName()]);
		break;
	case '移除本公会':
		if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/公会 移除公会 目标ID');
		if(($guild=$this->getGuild($sender->getName()))===false or $this->isAdmin($guild,$sender->getName())===false)
		return $sender->sendMessage(self::HEAD.'c你还没加入公会或者不是公会管理员！');
		if($this->isAdmin($guild,$args[1]) AND ($guild->get('会长')!=strtolower($sender->getName()) AND $guild->get('副会长')!=strtolower($sender->getName())))return $sender->sendMessage(self::HEAD.'c你没权限踢管理员！');
		if($guild->get('会长')===strtolower($args[1]))return $sender->sendMessage(self::HEAD.'c不能踢会长！');
		$key=array_flip($guild->get('成员'));
		if(isset($key[strtolower($args[1])])){
			$list=$guild->get('成员');
			unset($list[$key[strtolower($args[1])]]);
			$guild->set('成员',$list);
			$list=$guild->get('成员捐赠');
			unset($list[strtolower($args[1])]);
			$guild->set('成员捐赠',$list);
			if($target=$this->getPlayer($args[1])){
				$target->sendMessgae(self::HEAD.'c你已经被管理员移出当前公会！');
				$this->PlayersGuild[$target->getName()]=false;
			}
			$this->playerGuild->remove(strtolower($args[1]));
			$this->playerGuild->save();
			if($this->isAdmin($args[1])){
				if($guild->get('副会长')===strtolower($args[1]))$guild->set('副会长',null);
				if(in_array(strtolower($args[1]),$guild->get('管理员'))){
				$key=array_flip($guild->get('管理员'));
				$list=$guild->get('管理员');
				unset($list[$key[strtolower($sender->getName())]]);
				$guild->set('管理员',$list);
				}
			}
			$guild->save();
		}else return $sender->sendMessage(self::HEAD.'a工会里没有这个玩家！');
		$sender->sendMessage(self::HEAD.'a移除工会成功！');
	break;
	case '成员':
		if(($guild=$this->getGuild($sender->getName()))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
		$i=0;
		$color=['§a','§d','§e','§c','§1','§2','§3','§5','§6'];
		$result='§l§d全部成员如下'.PHP_EOL.$color[mt_rand(0,8)];
		foreach(array_merge([$guild->get('会长')],$guild->get('成员')) as $name){
			if($i!==0)$result.=','.$name;
else $result.=$name;
			if(++$i===5)$result.=PHP_EOL.$color[mt_rand(0,8)];
		}
		$sender->sendMessage($result);
	break;
	case '退出公会':
		if(($guild=$this->getGuild($sender->getName()))===false)return $sender->sendMessage(self::HEAD.'c你还没公会呢！');
		if($guild->get('会长')===strtolower($sender->getName()))return $sender->sendMessage(self::HEAD.'c作为会长不能退出自己的公会，请执行/公会 解散');
		foreach(array_merge([$guild->get('会长'),$guild->get('副会长')],$guild->get('管理员')) as $op){
			if($op===null)continue;
			$target=$this->getPlayer($op);
			if($target)$target->sendMessage(self::HEAD.'e玩家'.$sender->getName().'退出了你们的工会！');
		}
		if($this->isAdmin($guild,$sender->getName())){
			if($guild->get('副会长')===strtolower($sender->getName()))$guild->set('副会长',null);
			if(in_array(strtolower($sender->getName()),$guild->get('管理员'))){
				$key=array_flip($guild->get('管理员'));
				$list=$guild->get('管理员');
				unset($list[$key[strtolower($sender->getName())]]);
				$guild->set('管理员',$list);
				$guild->save();
			}
		}
		$key=array_flip($guild->get('成员'));
		$list=$guild->get('成员');
		unset($list[$key[strtolower($sender->getName())]]);
		$guild->set('成员',$list);
		$list=$guild->get('成员捐赠');
		unset($list[strtolower($sender->getName())]);
		$guild->set('成员捐赠',$list);
		$guild->save();
		$sender->sendMessage(self::HEAD.'a退出公会成功！');
		$this->playerGuild->remove(strtolower($sender->getName()));
		$this->PlayersGuild[$sender->getName()]=false;
		$this->playerGuild->save();
	break;
	default:
		return self::help($sender);
	break;
	}
	}
	public function onJoinEvent(PlayerJoinEvent $event){
		$name=$event->getPlayer()->getName();
		$this->PlayersGuild[$name]=$this->playerGuild->get(strtolower($name),false); 
		if($this->PlayersGuild[$name]===false)return;
		$g=strtolower(preg_replace('#§.#','',$this->PlayersGuild[$name]));
		if(!isset($this->guilds[$g])){
			$this->PlayersGuild[$name]=false;
			$this->playerGuild->remove(strtolower($name));
			$this->playerGuild->save();
		}
	}
	public function exists(String $name){
		$name=strtolower(trim(preg_replace('#§.#','',$name)));
		foreach($this->guilds as $n=>$conf)
			if($n===$name)return $conf;
		return false;
	}
	public function isAdmin($guild ,$playerName){
		foreach(array_merge([$guild->get('会长'),$guild->get('副会长')],$guild->get('管理员')) as $op)
			if($op===strtolower($playerName))return true;
		return false;
	}
	public function onQuitEvent(PlayerQuitEvent $event){
		$name=$event->getPlayer()->getName();
		unset($this->Invite[$name],$this->tp[$name]);
	}
	public function getGuild($name){
		return $this->PlayerGuilds[$name]??false;
	}
	public function getPlayer($name){
		foreach($this->getServer()->getOnlinePlayers() as $player)
			if(strtolower($player->getName())===strtolower($name))return $player;
		return null;
	}
	public function getAllGuildByCount(){
		$arr=[];
		foreach($this->guilds as $guild)
			$arr[$guild->get('名字')]=count($guild->get('成员'))+1;
		arsort($arr);
		return $arr;
	}
	public function getAllGuildByMoney(){
		$arr=[];
		foreach($this->guilds as $guild)
			$arr[$guild->get('名字')]=$guild->get('总捐赠');
		arsort($arr);
		return $arr;
	}
	public function getAllGuildByBranch(){
		$arr=[];
		foreach($this->guilds as $guild)
			$arr[$guild->get('名字')]=$guild->get('公会积分');
		arsort($arr);
		return $arr;
	}
}
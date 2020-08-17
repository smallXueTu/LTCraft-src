<?php
namespace LTSociety;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\Level\Position;
use pocketmine\utils\Config;

class Society{
	public static $IntegralRanking;
	public static $BalanceRanking;
	public static $CountRanking;
	// public static function getIntegralRanking(){
		// return self::$IntegralRanking;
	// }
	// public static function getBalanceRanking(){
		// return self::$BalanceRanking;
	// }
	// public static function getCountRanking(){
		// return self::$CountRanking;
	// }
	private $Server;
	private $Plugin;
	private $Config;
	/* var Position*/
	private $CollectionPos = null;
	private $Name;
	private $ID;
	private $Integral = 0;
	private $MaxNumber = 10;
	private $Balance = 10;
	private $President;
	private $VoicPresident;
	private $base = null;
	private $Members = [];
	private $Admins = [];
	private $Applys = [];
	private $MemberDonations = [];
	public function __construct($config){
		$this->Plugin = Main::getInstance();
		$this->Server = Server::getInstance();
		$this->Config = $config;
		$this->Name = $config->get('名字');
		$this->ID = $config->get('ID', Main::$ID++);
		$this->President = $config->get('会长');
		$this->VoicPresident = $config->get('副会长', null);
		$this->Admins = $config->get('管理员', []);
		$this->Members = $config->get('成员', []);
		if($config->get('基地')!==false){
			$baseInfo = explode(':', $config->get('基地'));
			if(($level=$this->Server->getLevelByName($baseInfo[0]))!==null){
				$this->base = new Position($baseInfo[1], $baseInfo[2], $baseInfo[1], $level);
			}
		}
		if(!is_array($this->Members) or $this->Name==''){
			var_dump($config->getFile());
			@unlink($config->getFile());
		}
		$this->Applys = $config->get('申请名单', []);
		$this->Integral = $config->get('公会积分', 0);
		$this->MaxNumber = $config->get('最大人数', 10);
		$this->MemberDonations = $config->get('成员捐赠', []);
		$this->Balance = $config->get('总捐赠', 0);
	}
	public function getName(){//公会名字
		return $this->Name;
	}
	public function getBase(){//公会基地
		return $this->base;
	}
	public function setBase(Position $pos){//公会基地
		$this->base = $pos;
	}
	public function getID(){//公会ID
		return $this->ID;
	}
	public function getServer(){//服务器..
		return $this->Server;
	}
	public function setName($name){//修改公会名字
		$this->Name = $name;
	}
	public function getBalance(){//获取总捐赠
		return $this->Balance;
	}
	public function getVoicPresident(){//获取副会长
		return $this->VoicPresident;
	}
	public function setVoicPresident($VoicPresident){//获取副会长
		if($VoicPresident==null){
			$this->VoicPresident = null;
		}else{
			$this->VoicPresident = self::getPlayerName($VoicPresident);
		}
	}
	public function getPresident(){//获取会长
		return $this->President;
	}
	public function setPresident($player){//设置会长
		$this->President = self::getPlayerName($player);
	}
	public function setBalance($Balance){//设置总捐赠
		$this->Balance = $Balance;
		$this->Plugin->updateGuildBalance();
	}
	public function addBalance($v){//增加总捐赠
		$this->Balance += $v;
		$this->Plugin->updateGuildBalance();
	}
	public function setMaxNumber($MaxNumber){//设置最大人数
		$this->MaxNumber = $MaxNumber;
	}
	public function addMaxNumber($Number){//增加最大人数
		$this->MaxNumber += $Number;
	}
	public function getMaxNumber(){//最大人数
		return $this->MaxNumber;
	}
	public function setIntegral($Integral){//设置积分
		$this->Integral = $Integral;
		$this->Plugin->updateGuildIntegral();
	}
	public function addIntegral($v){//增加积分
		$this->Integral += $v;
		$this->Plugin->updateGuildIntegral();
	}
	public function getIntegral(){//获取积分
		return $this->Integral;
	}
	public function getAdmins(){//获取所有管理员
		return $this->Admins;
	}
	public function hasAdmin($player){//玩家是不是管理员
		return array_search(self::getPlayerName($player), $this->Admins)!==false;
	}
	public function getAllAdmin(){//获取所有管理员
		return array_merge($this->Admins, [$this->getPresident(), (string)$this->getVoicPresident()]);
	}
	public function addAdmin($player){//增加管理员
		$this->Admins[]=self::getPlayerName($player);
	}
	public function removeAdmin($player){//删除管理员
		unset($this->Admins[array_search(self::getPlayerName($player), $this->Admins)]);
	}
	public function getMembers(){//获取所有成员
		return $this->Members;
	}
	public function isMembers($player){//是这个公会的成员？
		return array_search(self::getPlayerName($player), $this->Members)!==false;
	}
	public function getAllMember(){//获取所有成员
		return array_merge($this->Members, [$this->getPresident()]);
	}
	public function isAdmin($player){
		if(array_search(self::getPlayerName($player), $this->getAllAdmin())!==false){
			return true;
		}else{
			return false;
		}
	}
	public function addMember($player, $isPresident = false){//增加成员
		if($this->isMembers($player))return;
		$this->Members[]=self::getPlayerName($player);
		$this->Plugin->updateGuildCount();
		if($isPresident===false){
			$this->updateMemberGuildName(self::getPlayerName($player));
			$this->setMemberDonation(self::getPlayerName($player), 0);
		}
	}
	public function removeMember($player){//删除成员
		if(self::getPlayerName($player)===$this->getVoicPresident()){
			$this->setVoicPresident(null);
		}
		if($this->hasAdmin($player)){
			$this->removeAdmin($player);
		}
		unset($this->Members[array_search(self::getPlayerName($player), $this->Members)]);
		$this->Plugin->updateGuildCount();
		$this->updateMemberGuildName($player, '无公会');
		unset($this->MemberDonations[self::getPlayerName($player)]);
	}
	public function getMemberDonations(){//获取成员捐赠
		return $this->MemberDonations;
	}
	public function getApplys(){//获取申请名单
		return $this->Applys;
	}
	public function isApplysPlayer($player){//是不是正在申请的玩家
		if(array_search(self::getPlayerName($player), $this->getApplys())!==false){
			return true;
		}else{
			return false;
		}
	}
	public function sendAdminMessage($mess){
		foreach($this->getAllAdmin() as $admin){
			if(($p=$this->Server->getPlayerExact($admin)) instanceof Player){
				$p->sendMessage($mess);
			}
		}
	}
	public function addApplysPlayer($player){//添加正在申请的玩家
		$this->Applys[] = self::getPlayerName($player);
		$this->sendAdminMessage(Main::HEAD.'e玩家'.self::getPlayerName($player).'申请加入你的公会 输入/公会 同意加入 '.self::getPlayerName($player).'或/公会 拒绝加入  '.self::getPlayerName($player).'来处理这个请求。');
	}
	public function removeApplysPlayer($player){//删除正在申请的玩家
		unset($this->Applys[array_search(self::getPlayerName($player), $this->Applys)]);
	}
	public function setMemberDonation($player, $Donation){//设置成员总捐赠
		$this->MemberDonations[self::getPlayerName($player)] = $Donation;
	}
	public function addMemberDonation($player, $Donation){//增加员总捐赠
		if(!isset($this->MemberDonations[self::getPlayerName($player)])){
			$this->MemberDonations[self::getPlayerName($player)] = 0;
		}
		$this->MemberDonations[self::getPlayerName($player)] += $Donation;
		$this->addBalance($Donation);
	}
	public function getMemberDonation($player){//获取成员捐赠
		return $this->MemberDonations[self::getPlayerName($player)];
	}
	public function setMembersGuildName($name, $player=null){//设置所有成员的公会名字
		if($player===null){
			foreach($this->getAllMember() as $Member){
				if(($p=$this->Server->getPlayerExact($Member)) instanceof Player){
					$p->setGuild($name);
					if($name!=='无公会'){
						$p->sendMessage(Main::HEAD.'c你当前公会被改名为：'.$name);
					}else{
						$p->sendMessage(Main::HEAD.'c你的公会已被解散了');
					}
				}else{
					if(file_exists(\pocketmine\DATA .'players/'.$Member.'.dat')){
                        $nbt=$this->Server->getOfflinePlayerData($Member);
						if(!($nbt instanceof CompoundTag))continue;
						$nbt->Guild=new StringTag('Guild', $name);
						$this->Server->saveOfflinePlayerData($Member, $nbt, true);
					}
				}
			}
		}else{
			$player=self::getPlayerName($player);
			if(($p=$this->Server->getPlayerExact($player)) instanceof Player){
					$p->setGuild($name);
					if($name!=='无公会'){
						$p->sendMessage(Main::HEAD.'c你当前公会被改名为：'.$name);
					}else{
						$p->sendMessage(Main::HEAD.'c你已经被管理员移出当前公会！');
					}
				}else{
					if(file_exists(\pocketmine\DATA .'players/'.$player.'.dat')){
						$nbt=$this->Server->getOfflinePlayerData($player);
						if(!($nbt instanceof CompoundTag))return;
						$nbt=$this->Server->getOfflinePlayerData($player);
						$nbt->Guild=new StringTag('Guild', $name);
						$this->Server->saveOfflinePlayerData($player, $nbt, true);
					}
				}
		}
	}
	public function updateMemberGuildName($Member, $name=null){//设置成员的公会名字
		if($Member instanceof Player)
			$p=$Member;
		if(isset($p) or ($p=$this->Server->getPlayerExact($Member)) instanceof Player){
			if($name===null){
				$p->setGuild($this->getName());
			}else{
				$p->setGuild($name);
			}
		}else{
			if(file_exists(\pocketmine\DATA .'players/'.$Member.'.dat')){
				$nbt=$this->Server->getOfflinePlayerData($Member);
				if(!($nbt instanceof CompoundTag))return;
				$nbt=$this->Server->getOfflinePlayerData($Member);
				if($name===null){
					$nbt->Guild=new StringTag('Guild', $this->getName());
				}else{
					$nbt->Guild=new StringTag('Guild', $name);
				}
				$this->Server->saveOfflinePlayerData($Member, $nbt, true);
			}
		}
	}
	public function Collection(Player $player){
		$this->CollectionPos=$player->asPosition();
		foreach($this->getAllMember() as $Member){
			if(($p=$this->Server->getPlayerExact($Member)) instanceof Player and ($p->distance($player)>10 or $p->getLevel()!==$player->getLevel())){
				$p->sendMessage(Main::HEAD.'e你的公会管理员'.$player->getName().'邀请你们集合到他的位置'. PHP_EOL .'输入/公会 同意集合');
			}
		}
	}
	public function getCollectionPos(){//获取集合坐标 如果没管理员发出集合 则返回null
		return $this->CollectionPos;
	}
	public function save(){
		$this->Config->set('名字', $this->Name);
		$this->Config->set('ID', $this->ID);
		$this->Config->set('会长', $this->President);
		$this->Config->set('副会长', $this->VoicPresident);
		$this->Config->set('管理员', $this->Admins);
		$this->Config->set('成员', $this->Members);
		$this->Config->set('申请名单', $this->Applys);
		$this->Config->set('公会积分', $this->Integral);
		$this->Config->set('最大人数', $this->MaxNumber);
		$this->Config->set('成员捐赠', $this->MemberDonations);
		$this->Config->set('总捐赠', $this->Balance);
		if($this->base===null){
			$this->Config->set('基地', false);
		}else{
			$this->Config->set('基地', $this->base->getLevel()->getName().':'. $this->base->getX().':'. $this->base->getY().':'. $this->base->getZ());
		}
		$this->Config->save(false);
	}
	public function getConfig(){
		return $this->Config;
	}
	public function Rename($NewName){
		@unlink($this->Config->getFile());
		$this->setName($NewName);
		$this->setMembersGuildName($NewName);
		$this->Config = new Config($this->plugins->getDataFolder().'Guilds/'.$NewName.'.yml',Config::YAML,[]);
		$this->save();
	}
	public static function getPlayerName($player){
		if($player instanceof Player){
			return strtolower($player->getName());
		}else{
			return $player=strtolower($player);
		}
	}
	public function Destruction(){//销毁公会
		@unlink($this->Config->getFile());
		$this->setMembersGuildName('无公会');
	}
}
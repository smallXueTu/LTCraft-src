<?php
namespace LTPet\Commands;
class Commands{
	public $Commands=[];
	public function __construct($server ,$plugin){
		$this->server=$server;
		$this->config=$plugin;
		$this->Commands['回收']=new Recovery($plugin);
		$this->Commands['复活']=new Spawn($plugin);
		$this->Commands['赠送']=new Give($plugin);
		$this->Commands['召唤']=new Come($plugin);
		$this->Commands['召唤列表']=new CList($plugin);
		$this->Commands['列表']=new PetList($plugin);
		$this->Commands['帮助']=new Help($plugin);
		$this->Commands['回收全部']=new RecoveryAll($plugin);
		$this->Commands['管理']=new Admin($plugin);
		$this->Commands['皮肤']=new Skin($plugin);
		$this->Commands['改名']=new PetRename($plugin);
		$this->Commands['修复']=new FixPetConfig($plugin);
	}
	public function onCommand($sender,$cmd,$label,$args){
		if(isset($this->Commands[$args[0]]))
			$this->Commands[array_shift($args)]->run($args,$sender);
		else
			return $sender->sendMessage('§l§a[LT宠物系统]§c哎呀，您貌似输错了，输入§d/宠物 帮助§c查看帮助吧§r');
	}
}
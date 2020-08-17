<?php
namespace LTPopup;

use pocketmine\Player;
/*
聊天管理类
这个类是为了防止玩家刷屏！
每次聊天请调用chechCanChat，如果可以则可以继续聊天。
如果返回false请警告玩家发言过快，如果返回Int，请则玩家被禁言

Chat management class
This class is to prevent players from brushing the screen!
Call "chechCanChat" every time you chat, and if you can, you can continue chatting.
If you return false, please warn the player to speak too fast. If you return Int, the player will be forbidden to speak.
*/
class SayManager{
	public $player;
	public $chatTime=[0,0,0,0,0,0];
	public $status=true;
	public function __construct(Player $player){
		$this->player=$player;
	}
	public function checkCanChat(){
		if($this->status===true or $this->status<=time()){
			if($this->chatTime[3]!==0 and (microtime(true)-$this->chatTime[3]<2.5)){
				array_shift($this->chatTime);
				$this->chatTime[]=microtime(true);
				return false;
			}
			if($this->chatTime[0]!==0 and (microtime(true)-$this->chatTime[0]<5)){
				$this->status=time()+10;
				return 10;
			}
			array_shift($this->chatTime);
			$this->chatTime[]=microtime(true);
			$this->status=true;
			return true;
		}
		return $this->status-time();
	}
	public function setShield(int $v){//set shield 设置禁言
		$this->status=time()+$v;
	}
}
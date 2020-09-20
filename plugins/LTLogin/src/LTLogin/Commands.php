<?php
namespace LTLogin;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Server;

class Commands{
	// public static $time;
	public function  __construct($event){
		$this->event=$event;
		// $this->sql=$sql;
	}
	public function onCommand($Sender,$cmd,$label,$args){
		$name=strtolower($Sender->getName());
		if(!isset($args[0])){
			$Sender->sendMessage('§d------§a[登录系统帮助命令]§d-------');
			$Sender->sendMessage('§d/login email §e邮箱地址 §a绑定邮箱命令');
			$Sender->sendMessage('§d/login resetp §e新密码 §a修改密码命令');
			return;
		}
		switch(strtolower($args[0])){
			case 'email':
				if(isset($this->event->datas[$name]['email']) and $this->event->datas[$name]['email']!==NULL)return $Sender->sendMessage('§l§aLTCraft>>§c你已经绑过邮箱了');return;
				if(!isset($args[1]))return $Sender->sendMessage('§l§aLTCraft>>§c用法：§d/login email §e邮箱地址');
				if(preg_match('/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i',$args[1])){
					Server::getInstance()->dataBase->pushService('1'.chr(2)."update user set email='{$args[1]}' where name='{$name}'");
					$Sender->sendMessage('§l§aLTCraft>>§a绑定成功,你以后可以通过这个来找回密码　 &&　开发中．．');
				}else{
					$Sender->sendMessage('§l§aLTCraft>>§c邮箱地址不合法');
				}
			break;
			case 'qq':
				if(isset($this->event->datas[$name]['qq']) and $this->event->datas[$name]['qq']!==NULL)return $Sender->sendMessage('§l§aLTCraft>>§c你已经绑过QQ了');return;
				if(!isset($args[1]))return $Sender->sendMessage('§l§aLTCraft>>§c用法：§d/login qq QQ号');
				Server::getInstance()->dataBase->pushService('1'.chr(2)."update user set QQ='{$args[1]}' where name='{$name}'");
				$Sender->sendMessage('§l§aLTCraft>>§a绑定成功,你以后可以通过这个来找回密码 或查询');
			break;
			case 'resetp':
				if(!isset($args[1])){$Sender->sendMessage('§l§aTCraft>>§c用法：§d/login resetp §e新密码');return;}
				// $code=$this->sql->getCode($name);
				// if($code!=false){
					// if($code['code']==NULL){
						// $Sender->sendMessage('§l§a[提示]§c你还没发送过验证码，输入§d/login send  §c发送验证码');
					// }
					// if($code['outtime']>=time()){
						// if($code['code']==$args[1]){
							$check=$this->event->checkPassword($args[1]);
							if($check!==true){
								$Sender->sendMessage('§l§aLTCraft>>§c'.$check);
								return;
							}
							Server::getInstance()->dataBase->pushService('1'.chr(2)."update user set password='{$args[1]}' where name='{$name}'");
							$this->event->datas[$name]['password']=$args[1];
							$Sender->sendMessage('§l§aLTCraft>>§a修改成功！');
						// }else{
							// $Sender->sendMessage('§l§a[提示]§c验证码错误！');
						// }
					// }else{
						// $Sender->sendMessage('§l§a[提示]§c验证码已过期！');
					// }
				// }else{
					// $Sender->sendMessage('§l§a[提示]§c哎呀，出错了，请报告管理员！');
				// }
			break;
			// case 'send':
				// if(isset($this->email) && $this->email->isStarted()){
					// $Sender->sendMessage('§l§a[提示]§c有一个线程正在发送中，请等待一段时间。');
					// return; 
				// }
				// if($this->sql->getEmail($name)==NULL){$Sender->sendMessage('§l§a[提示]§c你还没绑过邮箱，输入d/login emali §e邮箱地址§c来绑定吧');return;}
				// $email=$this->sql->getEmail($name);
				// $code=$this->event->plugin->getCode($name);
				// if($email==false or $code==false){
					// $Sender->sendMessage('§l§a[提示]§c哎呀，出错了，请报告管理员！');
					// return;
				// }
				// $message=array(
					// 'from'=>'lt_craft@163.com',
					// 'to'=>$email,
					// 'subject'=>'您尝试修改你的服务器密码',
					// 'body'=>'您好,亲爱的：'.$name.'您申请修改密码，本次验证码为：'.$code.' 请记牢，五分钟内有效，输入/login resetp 验证码 新密码来修改！'
				// );
				// $this->email=new Mailer('smtp.163.com',25,'lt_craft@163.com','2665337794yan',false,$message);
				// $this->email->start();
				// $Sender->sendMessage('§l§a[提示]§a已加入副线程处理！请1分钟后去邮箱查看。');
			// break;
			case 'help':
				$Sender->sendMessage('§d------§a[登录系统帮助命令]§d-------');
				$Sender->sendMessage('§d/login emali §e邮箱地址 §a绑定邮箱命令');
				$Sender->sendMessage('§d/login resetp §e新密码 §a修改密码命令');
				// $Sender->sendMessage('§d/login send §a向你的邮箱发送验证码');
			break;
			default:
				$Sender->sendMessage('§l§aLTCraft>>§c未知命令 输入§a/login help §c获取帮助列表');
			break;
		}
	}
}

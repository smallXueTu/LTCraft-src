<?php
namespace LTCraft;

use pocketmine\scheduler\AsyncTask;

class RankingsReward extends AsyncTask{
	const PVP_HEADER = 1;
	const PVE_EXP = 2;
	public $keep;
	public $type;
	public function __construct($keep, $type = 1){
		$this->keep=$keep;
		$this->type=$type;
	}
	public function onRun(){
		$i=1;
		if($this->type==self::PVP_HEADER){
			$p='上周人头奖励已发放%20排行榜:';
			foreach($this->keep as $name=>$count){
				$p.='\n排名:'.$i++."%20ID:".$name.'%20人头数:'.$count;
				if($i==11)break;
			}
			file_get_contents('http://127.0.0.1/Mirai?mess='.$p);
		}else{
			$p='上周经验奖励已发放%20排行榜:';
			foreach($this->keep as $name=>$count){
				$p.='\n排名:'.$i."%20ID:".$name.'%20经验值:'.$count;
				if(++$i==11)break;
			}
			file_get_contents('http://127.0.0.1/Mirai?mess='.$p);
		}
	}
	
	public function sendItem($name, $item){
		$sql="INSERT INTO wed.items(username, type, name, count, status) VALUES ('".strtolower($name)."','".$item[0]."','".$item[1]."','".$item[2]."', 0)";
		$this->server->dataBase->pushService('1'.chr(2).$sql);
	}
}
<?php

namespace MUedsa;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\tile\Tile;
use pocketmine\tile\Sign;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class DontTapTheWhiteTile extends PluginBase implements Listener{

	private $SetStatus = array(),$pos1 = array(),$pos2 = array();
	private $line = array(),$color = array(),$StartSign = array(),$Top1 = array(),$Top2 = array(),$Top3 = array();
	private $Starttime,$EndTime,$GameStart;
	private $whiteblock,$blackblock,$greenblock,$redblock;
	public function onLoad(){
		// $this->getLogger()->info(TextFormat::GREEN . "§a小游戏 *别踩白块儿* 正在加载 !");
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder(), 0777, true);
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
		if($this->config->exists("line") AND $this->config->get("line") !== array()){
			$this->line =  $this->config->get("line");
			$this->StartSign =  $this->config->get("StartSign");
			$this->Top1 = $this->config->get("Top1");
			$this->Top2 = $this->config->get("Top2");
			$this->Top3 = $this->config->get("Top3");
		}
		$this->whiteblock = new Block(35,0);
		$this->blackblock = new Block(35,15);
		$this->greenblock = new Block(35,5);
		$this->redblock = new Block(35,14);
		// $this->getLogger()->info(TextFormat::DARK_GREEN . "§e小游戏 *别踩白块儿* 开启 !");
    }

	public function onDisable(){
		// $this->getLogger()->info(TextFormat::DARK_RED . "§d小游戏 *别踩白块儿* 关闭 !");
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case "dttt":
				switch ($args[0]) {
					case 'set':
						if($this->config->getAll() !==  array()){
							$sender->sendMessage("§4小游戏 *别踩白块儿* 已经设置,请使用命令 : /dttt del 来删除设置!");
						}else{
							$name = $sender->getName();
							$this->SetStatus[$name] = 0;
							$sender->sendMessage("§1你已经处于设置状态下,请先设置一个4(宽)*5(高)的方框\n然后点击四个牌子作为开始按钮和排行榜");
						}
						break;

					case 'del':
						$this->config->setAll(array());
						$this->config->save();
						unset($this->SetStatus);
						unset($this->pos1);
						unset($this->pos2);
						unset($this->line);
						unset($this->color);
						unset($this->StartSign);
						unset($this->Top1);
						unset($this->Top2);
						unset($this->Top3);
						$sender->sendMessage("§a清除了小游戏 *别踩白块儿* 的设置");
						break;

					default:
						$sender->sendMessage("命令 : /dttt <set/del>");
						break;
				}
				return true;
			default:
				return false;
		}
	}
	public function onJoin(PlayerJoinEvent $event){
		$username = $event->getPlayer()->getName();
		if(isset($this->GameStart[$username])){
			$this->GameTimeout();
		}

	}
	public function onQuit(PlayerQuitEvent $event){
		$username = $event->getPlayer()->getName();
		if(isset($this->GameStart[$username])){
			$this->GameTimeout();
		}
	}
	
	public function onPlace(BlockPlaceEvent $event){
		if(isset($this->line[0][0]["level"])){
			$block = $event->getBlock();
			$levelname = $event->getPlayer()->getLevel()->getFolderName();
			if($this->line[0][0]["level"] === $levelname AND $this->line[0][0]["x"]-1 <= $block->x AND $this->line[0][0]["y"] <= $block->y AND $this->line[0][0]["z"]-1 <= $block->z AND $this->line[4][3]["x"]+1 >= $block->x AND $this->line[4][3]["y"] >= $block->y AND $this->line[4][3]["z"]+1 >= $block->z){
				$event->setCancelled(true);
			}
			switch ($this->StartSign["face"]) {
				case 2:
					if($this->StartSign["level"] == $levelname AND $block->x == $this->StartSign["x"] AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"]-1){
						$event->setCancelled(true);
					}
					break;
				case 3:
					if($this->StartSign["level"] == $levelname AND $block->x == $this->StartSign["x"] AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"]+1){
						$event->setCancelled(true);
					}
					break;
				case 4:
					if($this->StartSign["level"] == $levelname AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"] AND $block->x == $this->StartSign["x"]-1){
						$event->setCancelled(true);
					}
					break;
				case 5:
					if($this->StartSign["level"] == $levelname AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"] AND $block->x == $this->StartSign["x"]+1){
						$event->setCancelled(true);
					}
					break;
			}
			switch ($this->Top1["face"]) {
				case 2:
					if($this->Top1["level"] == $levelname AND $block->x == $this->Top1["x"] AND $block->y == $this->Top1["y"] AND $block->z == $this->Top1["z"]-1){
						$event->setCancelled(true);
					}
					break;
				case 3:
					if($this->Top1["level"] == $levelname AND $block->x == $this->Top1["x"] AND $block->y == $this->Top1["y"] AND $block->z == $this->Top1["z"]+1){
						$event->setCancelled(true);
					}
					break;
				case 4:
					if($this->Top1["level"] == $levelname AND $block->y == $this->Top1["y"] AND $block->z == $this->Top1["z"] AND $block->x == $this->Top1["x"]-1){
						$event->setCancelled(true);
					}
					break;
				case 5:
					if($this->Top1["level"] == $levelname AND $block->y == $this->Top1["y"] AND $block->z == $this->Top1["z"] AND $block->x == $this->Top1["x"]+1){
						$event->setCancelled(true);
					}
					break;
			}
			switch ($this->Top2["face"]) {
				case 2:
					if($this->Top2["level"] == $levelname AND $block->x == $this->Top2["x"] AND $block->y == $this->Top2["y"] AND $block->z == $this->Top2["z"]-1){
						$event->setCancelled(true);
					}
					break;
				case 3:
					if($this->Top2["level"] == $levelname AND $block->x == $this->Top2["x"] AND $block->y == $this->Top2["y"] AND $block->z == $this->Top2["z"]+1){
						$event->setCancelled(true);
					}
					break;
				case 4:
					if($this->Top2["level"] == $levelname AND $block->y == $this->Top2["y"] AND $block->z == $this->Top2["z"] AND $block->x == $this->Top2["x"]-1){
						$event->setCancelled(true);
					}
					break;
				case 5:
					if($this->Top2["level"] == $levelname AND $block->y == $this->Top2["y"] AND $block->z == $this->Top2["z"] AND $block->x == $this->Top2["x"]+1){
						$event->setCancelled(true);
					}
					break;
			}
			switch ($this->Top3["face"]) {
				case 2:
					if($this->Top3["level"] == $levelname AND $block->x == $this->Top3["x"] AND $block->y == $this->Top3["y"] AND $block->z == $this->Top3["z"]-1){
						$event->setCancelled(true);
					}
					break;
				case 3:
					if($this->Top3["level"] == $levelname AND $block->x == $this->Top3["x"] AND $block->y == $this->Top3["y"] AND $block->z == $this->Top3["z"]+1){
						$event->setCancelled(true);
					}
					break;
				case 4:
					if($this->Top3["level"] == $levelname AND $block->y == $this->Top3["y"] AND $block->z == $this->Top3["z"] AND $block->x == $this->Top3["x"]-1){
						$event->setCancelled(true);
					}
					break;
				case 5:
					if($this->Top3["level"] == $levelname AND $block->y == $this->Top3["y"] AND $block->z == $this->Top3["z"] AND $block->x == $this->Top3["x"]+1){
						$event->setCancelled(true);
					}
					break;
			}
		}
	}

	public function onBreak(BlockBreakEvent $event){
		if(isset($this->line[0][0]["level"])){
			$block = $event->getBlock();
			$levelname = $event->getPlayer()->getLevel()->getFolderName();
			//打破方框
			if($this->line[0][0]["level"] === $levelname AND $this->line[0][0]["x"] <= $block->x AND $this->line[0][0]["y"] <= $block->y AND $this->line[0][0]["z"] <= $block->z AND $this->line[4][3]["x"] >= $block->x AND $this->line[4][3]["y"] >= $block->y AND $this->line[4][3]["z"] >= $block->z){
				$event->setCancelled(true);
			}
			if($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323){
				if(
					($block->x == $this->StartSign["x"] AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"] AND $levelname === $this->StartSign["level"]) OR
					($block->x == $this->Top1["x"] AND $block->y == $this->Top1["y"] AND $block->z == $this->Top1["z"] AND $levelname === $this->Top1["level"]) OR
					($block->x == $this->Top2["x"] AND $block->y == $this->Top2["y"] AND $block->z == $this->Top2["z"] AND $levelname === $this->Top2["level"]) OR
					($block->x == $this->Top3["x"] AND $block->y == $this->Top3["y"] AND $block->z == $this->Top3["z"] AND $levelname === $this->Top3["level"])
				){
					$event->setCancelled(true);
				}
			}
			//打破木牌
			if($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323){
				if(
					($block->x == $this->StartSign["x"] AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"] AND $levelname === $this->StartSign["level"]) OR
					($block->x == $this->Top1["x"] AND $block->y == $this->Top1["y"] AND $block->z == $this->Top1["z"] AND $levelname === $this->Top1["level"]) OR
					($block->x == $this->Top2["x"] AND $block->y == $this->Top2["y"] AND $block->z == $this->Top2["z"] AND $levelname === $this->Top2["level"]) OR
					($block->x == $this->Top3["x"] AND $block->y == $this->Top3["y"] AND $block->z == $this->Top3["z"] AND $levelname === $this->Top3["level"])
				){
					$event->setCancelled(true);
				}
			}
		}
	}

	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$username = $player->getName();
		$block = $event->getBlock();
		$levelname = $player->getLevel()->getFolderName();
		if(isset($this->SetStatus[$username])){
			switch ($this->SetStatus[$username]) {
				case 0:
					$this->pos1 = array(
								"x" =>$block->x,
								"y" =>$block->y,
								"z" =>$block->z,
								"level" =>$levelname,
							);
					$this->SetStatus[$username]++;
					$player->sendMessage(TextFormat::GREEN." §e* 方框的第一点设置 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
					$player->sendMessage(TextFormat::GREEN." §e* 请点击方块设置方框的第二点");
					break;
				case 1:
					if($this->pos1["level"] === $levelname AND $this->pos1["x"] == $block->x){
						if(abs($this->pos1["y"] - $block->y) == 4 AND abs($this->pos1["z"] - $block->z) == 3){
							$this->pos2 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$x1 = $this->pos1["x"];
							if($this->pos1["y"] > $this->pos2["y"]){
								$y1 = $this->pos2["y"];
							}else{
								$y1 = $this->pos1["y"];
							}
							if($this->pos1["z"] > $this->pos2["z"]){
								$z1 = $this->pos2["z"];
							}else{
								$z1 = $this->pos1["z"];
							}
							for($i=0;$i<5;$i++){
								for($n=0;$n<4;$n++){
									$this->line[$i][$n] = array(
												"x" => $x1,
												"y" => $y1 + $i,
												"z" => $z1 + $n,
												"level" => $levelname,
												);
								}
							}
							$this->config->set("line",$this->line);
							$this->config->save();
							$this->SetStatus[$username]++;
							$c = 1;
							for($i=0;$i<5;$i++){
								for($n=0;$n<4;$n++){
									$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
									if($c % 2 == 0){
										$player->getLevel()->setBlock($pos,$this->whiteblock);
									}else{
										$player->getLevel()->setBlock($pos,$this->blackblock);
									}
									$c++;
								}
								$c++;
							}
							$player->sendMessage(TextFormat::GREEN."§e* 方框的第二点设置 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
							$player->sendMessage(TextFormat::GREEN."§e* 请点击木牌作为游戏开始按钮");
						}else{
							$this->SetStatus[$username] = 0;
							$player->sendMessage(TextFormat::GREEN."§e * 方框的第二点设置 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
							$player->sendMessage(TextFormat::RED." §e* 请确认设置是在同一地图的 4(宽) * 5(高) 的方框\n * 请重新设置一二点");
						}
					}elseif($this->pos1["level"] === $levelname AND $this->pos1["z"] == $block->z){
						if(abs($this->pos1["y"] - $block->y) == 4 AND abs($this->pos1["x"] - $block->x) == 3){
							$this->pos2 = array(
										"x" =>$block->x,
										"y" =>$block->y,
										"z" =>$block->z,
										"level" =>$levelname,
									);
							$z1 = $this->pos1["z"];
							if($this->pos1["y"] > $this->pos2["y"]){
								$y1 = $this->pos2["y"];
							}else{
								$y1 = $this->pos1["y"];
							}
							if($this->pos1["x"] > $this->pos2["x"]){
								$x1 = $this->pos2["x"];
							}else{
								$x1 = $this->pos1["x"];
							}
							for($i=0;$i<5;$i++){
								for($n=0;$n<4;$n++){
									$this->line[$i][$n] = array(
															"x" => $x1 + $n,
															"y" => $y1 + $i,
															"z" => $z1,
															"level" => $levelname,
															);
								}
							}
							$this->config->set("line",$this->line);
							$this->config->save();
							$this->SetStatus[$username]++;
							$c = 1;
							for($i=0;$i<5;$i++){
								for($n=0;$n<4;$n++){
									$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
									if($c % 2 == 0){
										$player->getLevel()->setBlock($pos,$this->whiteblock);
									}else{
										$player->getLevel()->setBlock($pos,$this->blackblock);
									}
									$c++;
								}
								$c++;
							}
							$player->sendMessage(TextFormat::GREEN."§e * 方框的第二点设置 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
							$player->sendMessage(TextFormat::GREEN." §e* 请点击木牌作为游戏开始按钮");
						}else{
							$this->SetStatus[$username] = 0;
							$player->sendMessage(TextFormat::GREEN."§e * 方框的第二点设置 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
							$player->sendMessage(TextFormat::RED." §e* 请确认设置是在同一地图的 4(宽) * 5(高) 的方框\n * 请重新设置一二点");
						}
					}else{
						$this->SetStatus[$username] = 0;
						$player->sendMessage(TextFormat::GREEN."§e * 方框的第二点设置 x=".$block->x." y=".$block->y." z=".$block->z." level=".$levelname);
						$player->sendMessage(TextFormat::RED." §e* 请确认设置是在同一地图的 4(宽) * 5(高) 的方框\n * 请重新设置一二点");
					}
					break;
				case 2:
					if($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323){
						$this->StartSign = array(
								"x" =>$block->x,
								"y" =>$block->y,
								"z" =>$block->z,
								"face" => $event->getFace(),
								"level" =>$levelname,
							);
						$this->config->set("StartSign",$this->StartSign);
						$this->config->save();
						$StartSignVector3 = new Vector3($block->x,$block->y,$block->z);
						$player->getLevel()->getTile($StartSignVector3)->setText("§e别踩白块儿","§a游戏状态:点击开始","§e当前玩家:无","");
						$this->SetStatus[$username]++;
						$player->sendMessage(TextFormat::GREEN." §e* 设置开始木牌成功");
						$player->sendMessage(TextFormat::RED." §e* 请点击木牌作为排行榜木牌 1");
					}else{
						$player->sendMessage(TextFormat::RED." §e* 请点击木牌作为游戏开始按钮");
					}
					break;
				case 3:
					if($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323){
						$this->Top1 = array(
								"x" =>$block->x,
								"y" =>$block->y,
								"z" =>$block->z,
								"face" => $event->getFace(),
								"level" =>$levelname,
							);
						$this->config->set("Top1",$this->Top1);
						$this->config->save();
						$top1Vector3 = new Vector3($block->x,$block->y,$block->z);
						$player->getLevel()->getTile($top1Vector3)->setText("§e第一名","","999","");
						$this->SetStatus[$username]++;
						$player->sendMessage(TextFormat::GREEN." §e* 设置排行榜木牌 1");
						$player->sendMessage(TextFormat::RED." §e* 请点击木牌作为排行榜木牌 2");
					}else{
						$player->sendMessage(TextFormat::RED." §e* 请点击木牌作为排行榜木牌 1");
					}
					break;
				case 4:
					if($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323){
						$this->Top2 = array(
								"x" =>$block->x,
								"y" =>$block->y,
								"z" =>$block->z,
								"face" => $event->getFace(),
								"level" =>$levelname,
							);
						$this->config->set("Top2",$this->Top2);
						$this->config->save();
						$top2Vector3 = new Vector3($block->x,$block->y,$block->z);
						$player->getLevel()->getTile($top2Vector3)->setText("§e第二名","","999","");
						$this->SetStatus[$username]++;
						$player->sendMessage(TextFormat::GREEN." §e* 设置排行榜木牌 2");
						$player->sendMessage(TextFormat::RED."§e * 请点击木牌作为排行榜木牌 3");
					}else{
						$player->sendMessage(TextFormat::RED." §e* 请点击木牌作为排行榜木牌 2");
					}
					break;
				case 5:
					if($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323){
						$this->Top3 = array(
								"x" =>$block->x,
								"y" =>$block->y,
								"z" =>$block->z,
								"face" => $event->getFace(),
								"level" =>$levelname,
							);
						$this->config->set("Top3",$this->Top3);
						$this->config->save();
						$top3Vector3 = new Vector3($block->x,$block->y,$block->z);
						$player->getLevel()->getTile($top3Vector3)->setText("§e第三名","","999","");
						unset($this->SetStatus[$username]);
						$player->sendMessage(TextFormat::GREEN." §e* 设置排行榜木牌 3");
						Server::getInstance()->broadcastMessage(TextFormat::YELLOW." §e* 别踩白块儿 全部设置完成 , 可以进行游戏了!");
					}else{
						$player->sendMessage(TextFormat::RED." §e* 请点击木牌作为排行榜木牌 3");
					}
					break;
				default:
					$player->sendMessage(TextFormat::RED." §e* 发生了未知错误");
					break;
			}
		}elseif(isset($this->GameStart[$username])){
			if($this->line[0][0]["level"] === $levelname AND $this->line[0][0]["x"] <= $block->x AND $this->line[0][0]["y"] <= $block->y AND $this->line[0][0]["z"] <= $block->z AND $this->line[4][3]["x"] >= $block->x AND $this->line[4][3]["y"] >= $block->y AND $this->line[4][3]["z"] >= $block->z){
				if($block->y == $this->line[0][0]["y"]){
					if($block->getId() == 35 AND $block->getDamage() == 0){
						//踩到白块
						for($i=0;$i<5;$i++){
							for($n=0;$n<4;$n++){
								$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
								$player->getLevel()->setBlock($pos,$this->redblock);
							}
						}
						$player->sendMessage(TextFormat::RED." §4* 你失败了 , 游戏结束 !");
						$this->getServer()->getScheduler()->cancelTasks($this);
						$StartSignVector3 = new Vector3($this->StartSign["x"],$this->StartSign["y"],$this->StartSign["z"]);
						$player->getLevel()->getTile($StartSignVector3)->setText("§e别踩白块儿","§d游戏状态:点击开始","§4当前玩家:无","");
						unset($this->GameStart);
					}elseif($block->getId() == 35 AND $block->getDamage() == 15){
						//踩到黑块
						if($this->GameStart[$username] == 50){
							$this->EndTime = microtime(true);
							$gametime = round($this->EndTime-$this->Starttime,2);
							$player->sendMessage(TextFormat::GREEN." §e* 恭喜你成功过关 , 游戏结束 ,成绩 : ".$gametime." §4秒!");
							$top1Vector3 = new Vector3($this->Top1["x"],$this->Top1["y"],$this->Top1["z"]);
							$top1sign = $player->getLevel()->getTile($top1Vector3);
							$top1Text = $top1sign->getText();
							if($top1Text[2] > $gametime){
								$top1sign->setText("§e第一名",$username,$gametime,"");
								Server::getInstance()->broadcastMessage(TextFormat::YELLOW." * ".$username."§e赢得了 * 别踩白块儿小游戏第一名 , 用时 : ".$gametime."§4秒 !");
							}else{
								$top2Vector3 = new Vector3($this->Top2["x"],$this->Top2["y"],$this->Top2["z"]);
								$top2sign = $player->getLevel()->getTile($top2Vector3);
								$top2Text = $top2sign->getText();
								if($top2Text[2] > $gametime){
									$top2sign->setText("§e第二名",$username,$gametime,"");
									Server::getInstance()->broadcastMessage(TextFormat::YELLOW." * ".$username."§e赢得了 * 别踩白块儿小游戏第二名 , 用时 : ".$gametime."§4秒 !");
								}else{
									$top3Vector3 = new Vector3($this->Top3["x"],$this->Top3["y"],$this->Top3["z"]);
									$top3sign = $player->getLevel()->getTile($top3Vector3);
									$top3Text = $top3sign->getText();
									if($top3Text[2] > $gametime){
										$top3sign->setText("§e第三名",$username,$gametime,"");
										Server::getInstance()->broadcastMessage(TextFormat::YELLOW." * ".$username."§e赢得了 * 别踩白块儿小游戏第三名 , 用时 : ".$gametime."§4秒 !");
									}else{
										Server::getInstance()->broadcastMessage(TextFormat::YELLOW." * ".$username."§e完成了 * 别踩白块儿小游戏 , 用时 : ".$gametime."§4秒 !");
									}
								}
							}
							$this->getServer()->getScheduler()->cancelTasks($this);
							$StartSignVector3 = new Vector3($this->StartSign["x"],$this->StartSign["y"],$this->StartSign["z"]);
							$player->getLevel()->getTile($StartSignVector3)->setText("§e别踩白块儿","§d游戏状态:点击开始","§4当前玩家:无","");
							$this->ChangeBlock($this->GameStart[$username],$player->getLevel());
							unset($this->GameStart);
						}else{
							$this->getServer()->getScheduler()->cancelTasks($this);
							$this->getServer()->getScheduler()->scheduleDelayedTask(new CheckGameTimeout($this), 200);
							$this->ChangeBlock($this->GameStart[$username],$player->getLevel());
							$this->GameStart[$username]++;
						}
					}
				}
			}else{
				//开始游戏,未点击到方框
				if(($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323) AND $block->x == $this->StartSign["x"] AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"] AND $levelname === $this->StartSign["level"]){
					if(isset($this->GameStart) AND !isset($this->GameStart[$username])){
						$player->sendMessage(TextFormat::RED." §a* 已经有人在游戏中 , 你无法开始 !");
					}elseif(isset($this->GameStart[$username])){
						unset($this->GameStart);
						$c = 1;
						for($i=0;$i<5;$i++){
							for($n=0;$n<4;$n++){
								$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
								if($c % 2 == 0){
									$player->getLevel()->setBlock($pos,$this->whiteblock);
								}else{
									$player->getLevel()->setBlock($pos,$this->blackblock);
								}
								$c++;
							}
							$c++;
						}
						$StartSignVector3 = new Vector3($this->StartSign["x"],$this->StartSign["y"],$this->StartSign["z"]);
						$player->getLevel()->getTile($StartSignVector3)->setText("§e别踩白块儿","§d游戏状态:点击开始","§4当前玩家:无","");
						$player->sendMessage(TextFormat::RED." §4* 游戏取消 !");
					}
				}
			}
		}else{
			if(isset($this->line[0][0]["level"])){
				if(($block->getId() == 63 OR $block->getId() == 68 OR $block->getId() == 323) AND $block->x == $this->StartSign["x"] AND $block->y == $this->StartSign["y"] AND $block->z == $this->StartSign["z"] AND $levelname === $this->StartSign["level"]){
					if(isset($this->GameStart)){
						$player->sendMessage(TextFormat::RED." §e* 已经有人在游戏中 , 你无法开始 !");
					}else{
						$this->getServer()->getScheduler()->scheduleDelayedTask(new CheckGameTimeout($this), 200);
						$this->GameStart[$username] = 0;
						$this->ChangeBlock($this->GameStart[$username],$player->getLevel());
						$this->GameStart[$username]++;
						$StartSignVector3 = new Vector3($this->StartSign["x"],$this->StartSign["y"],$this->StartSign["z"]);
						$player->getLevel()->getTile($StartSignVector3)->setText("§e别踩白块儿","§d游戏状态:正在进行","§4当前玩家:".$username,"");
						$player->sendMessage(TextFormat::GREEN." §e* 游戏开始 , 请点击黑色方块 !");
					}
				}
			}
		}
	}

	public function ChangeBlock($int,$level){
		if($int == 0){
			$this->Starttime = microtime(true);
			for($i=0;$i<5;$i++){
				for($n=0;$n<4;$n++){
					$this->color[$i][$n] = $this->whiteblock;
				}
				// $this->color[$i][mt_rand(0,3)] = $this->blackblock;
				if(isset($this->GameStart['Angel_XX'])){
					$this->color[$i][0] = $this->blackblock;
				}else{
					$this->color[$i][mt_rand(0,3)] = $this->blackblock;
				}
			}
			for($i=0;$i<5;$i++){
				for($n=0;$n<4;$n++){
					$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
					$level->setBlock($pos,$this->color[$i][$n]);
				}
			}					
		}elseif($int != 0 AND $int <= 45){
			for($i=0;$i<4;$i++){
				$this->color[$i] = $this->color[$i+1];
			}
			$this->color[4][0] = $this->whiteblock;
			$this->color[4][1] = $this->whiteblock;
			$this->color[4][2] = $this->whiteblock;
			$this->color[4][3] = $this->whiteblock;
			if(isset($this->GameStart['Angel_XX'])){
				$this->color[4][0] = $this->blackblock;
			}else{
				$this->color[4][mt_rand(0,3)] = $this->blackblock;
			}
			for($i=0;$i<5;$i++){
				for($n=0;$n<4;$n++){
					$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
					$level->setBlock($pos,$this->color[$i][$n]);
				}
			}
		}else{
			for($i=0;$i<50-$int;$i++){
				$this->color[$i] = $this->color[$i+1];
			}
			for($i=4;$i>49-$int;$i--){
				$this->color[$i][0] = $this->greenblock;
				$this->color[$i][1] = $this->greenblock;
				$this->color[$i][2] = $this->greenblock;
				$this->color[$i][3] = $this->greenblock;
			}
			for($i=0;$i<5;$i++){
				for($n=0;$n<4;$n++){
					$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
					$level->setBlock($pos,$this->color[$i][$n]);
				}
			}
		}
	}

	public function GameTimeout(){
		unset($this->GameStart);
		Server::getInstance()->broadcastMessage(TextFormat::YELLOW." §4* 别踩白块儿 游戏超时!");
		$c = 1;
		$level = $this->getServer()->getLevelByName($this->line[0][0]["level"]);
		$StartSignVector3 = new Vector3($this->StartSign["x"],$this->StartSign["y"],$this->StartSign["z"]);
		$level->getTile($StartSignVector3)->setText("§e别踩白块儿","§d游戏状态:点击开始","§4当前玩家:无","");
		for($i=0;$i<5;$i++){
			for($n=0;$n<4;$n++){
				$pos = new Vector3($this->line[$i][$n]["x"],$this->line[$i][$n]["y"],$this->line[$i][$n]["z"]);
				if($c % 2 == 0){
					$level->setBlock($pos,$this->whiteblock);
				}else{
					$level->setBlock($pos,$this->blackblock);
				}
				$c++;
			}
		$c++;
		}
	}

}

class CheckGameTimeout extends PluginTask{

	public function onRun($currentTick){
		$this->getOwner()->GameTimeout();
	}
}
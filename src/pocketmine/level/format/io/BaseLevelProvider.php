<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\level\format\io;

use pocketmine\level\format\Chunk;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\LevelException;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\LongTag;

abstract class BaseLevelProvider implements LevelProvider {
	/** @var Level */
	protected $level;
	/** @var string */
	protected $path;
	/** @var CompoundTag */
	protected $levelData;
	protected $homeDatas=[];
	protected $WarpList=[];
	/** @var bool */
	protected $asyncChunkRequest = false;

	/**
	 * BaseLevelProvider constructor.
	 *
	 * @param Level  $level
	 * @param string $path
	 */
	public function __construct(Level $level, string $path){
		$this->level = $level;
		$this->path = $path;
		if(!file_exists($this->path)){
			mkdir($this->path, 0777, true);
		}
		/*
		 * 玩家的家保存在地图的playerHomes目录下
		 */
		if(!file_exists($this->path.'playerHomes/')){
			mkdir($this->path.'playerHomes/', 0777, true);
		}
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->readCompressed(file_get_contents($this->getPath() . "level.dat"));
		$levelData = $nbt->getData();
		if($levelData->Data instanceof CompoundTag){
			$this->levelData = $levelData->Data;
			//初始化地标
			$this->initWarps($levelData->WarpList??new CompoundTag);
		}else{
			throw new LevelException("Invalid level.dat");
		}

		if(!isset($this->levelData->generatorName)){
			$this->levelData->generatorName = new StringTag("generatorName", Generator::getGenerator("DEFAULT"));
		}

		if(!isset($this->levelData->generatorOptions)){
			$this->levelData->generatorOptions = new StringTag("generatorOptions", "");
		}
		// $this->asyncChunkRequest = (bool) $this->level->getServer()->getProperty("chunk-sending.async-chunk-request", false);
		$this->asyncChunkRequest = true;//异步请求生成块.
		$this->initPlayerHomes();
		//搞不懂 这是啥？
		if(!file_exists($this->path.'warp.dat')){
			$nbt = new NBT(NBT::BIG_ENDIAN);
			$nbt->setData(new CompoundTag("", []));
			$buffer = $nbt->writeCompressed();
			file_put_contents($this->getPath() . 'warp.dat', $buffer);
		}
	}

    /**
     * 删除全部地标和 home
     */
	public function clearAllData(){
		$this->homeDatas=[];
		$this->WarpList=[];
	}

    /**
     * 初始化世界地标
     * @param CompoundTag $list
     */
	public function initWarps(CompoundTag $list){
		foreach($list as $pos){
			$this->WarpList[$pos->name->getValue()]=new Position($pos->x->getValue(),$pos->y->getValue(),$pos->z->getValue(),$this->level);
		}
	}

    /**
     * 获取全部地标
     * @return array
     */
	public function getWarps(){
		return $this->WarpList;
	}

    /**
     * 增加一个地标
     * @param $name
     * @param Position $pos
     * @return bool
     */
	public function addWarp($name,Position $pos){
		if(isset($this->WarpList[$name]))return false;
		$this->WarpList[$name]=$pos;
		return true;
	}

    /**
     * 删除一个地标 -.-
     * @param $name
     * @return bool
     */
	public function delWarp($name){
		if(!isset($this->WarpList[$name]))return false;
		unset($this->WarpList[$name]);
		return true;
	}

    /**
     * 初始化玩家的Home
     */
	public function initPlayerHomes(){
		$all='qwertyuiopasdfghjklzxcvbnm1234567890_';
		$len=strlen($all);
		for($i=0;$i<$len;$i++){
			$nameHead=$all[$i];
			$thereAre=false;
			foreach(scandir($this->path.'playerHomes/') as $afile){
				$fname=explode('.',$afile);
				if($afile=='.' or $afile=='..' or is_dir($this->path.'playerHomes/'.$afile) or end($fname)!=='dat')continue;
				$name = explode('.', $afile);
				unset($name[count($name)-1]);
				if(implode('.', $name)==$nameHead){
					$thereAre=true;
					break;
				}
			}
			if(!$thereAre){
				$nbt = new NBT(NBT::BIG_ENDIAN);
				$nbt->setData(new CompoundTag("", []));
				$buffer = $nbt->writeCompressed();
				file_put_contents($this->getPath() . 'playerHomes/'.$nameHead.'.dat', $buffer);
			}
			$nbt = new NBT(NBT::BIG_ENDIAN);
			$nbt->readCompressed(file_get_contents($this->getPath() . 'playerHomes/'.$nameHead.'.dat'));
			$this->homeDatas[$nameHead]=$nbt->getData();
		}
	}
	/**
	 * @return string
	 */
	public function getPath() : string{
		return $this->path;
	}

	/**
	 * @return \pocketmine\Server
	 */
	public function getServer(){
		return $this->level->getServer();
	}

	public function setPlayerHomes($player, ListTag $nbt){
		if($player instanceof Player)
			$name=strtolower($player->getName());
		else
			$name=strtolower($player);
		$this->homeDatas[$name[0]][$name]=$nbt;
	}
	public function getPlayerHomes(Player $player){
		$name=strtolower($player->getName());
		if(isset($this->homeDatas[$name[0]][$name])){
			$homeList=$this->homeDatas[$name[0]][$name];
			if($homeList instanceof ListTag)
				return $homeList;
			else{
				Server::getInstance()->getLogger()->error('发现玩家'.$player->getName().',家数据损坏！');
				$homes=new ListTag($name, []);
				$homes->setTagType(NBT::TAG_Compound);
				return $homes;
			}
		}else{
			$homes=new ListTag($name, []);
			$homes->setTagType(NBT::TAG_Compound);
			return $homes;
		}
	}
	public function savePlayerHomes(){
		$all='qwertyuiopasdfghjklzxcvbnm1234567890_';
		$len=strlen($all);
		for($i=0;$i<$len;$i++){
			$nameHead=$all[$i];
			$nbt = new NBT(NBT::BIG_ENDIAN);
			$nbt->setData($this->homeDatas[$nameHead]);
			$buffer = $nbt->writeCompressed();
			file_put_contents($this->getPath() . 'playerHomes/'.$nameHead.'.dat', $buffer);
		}
	}
	public function getLevel(){
		return $this->level;
	}

	/**
	 * @return Level
	 */
	public function getName() : string{
		return (string) $this->levelData["LevelName"];
	}
  
	/**
	 * @param string
	 */
	public function setName($name){
		$this->levelData->LevelName=new StringTag('LevelName',$name);
	}
	/**
	 * @return mixed|null
	 */
	public function getTime(){
		return $this->levelData["Time"];
	}

	/**
	 * @param int|string $value
	 */
	public function setTime($value){
		$this->levelData->Time = new LongTag("Time", $value);
	}

	/**
	 * @return mixed|null
	 */
	public function getSeed(){
		return $this->levelData["RandomSeed"];
	}

	/**
	 * @param int|string $value
	 */
	public function setSeed($value){
		$this->levelData->RandomSeed = new LongTag("RandomSeed", (int) $value);
	}

	/**
	 * @return Vector3
	 */
	public function getSpawn() : Vector3{
		return new Vector3((float) $this->levelData["SpawnX"], (float) $this->levelData["SpawnY"], (float) $this->levelData["SpawnZ"]);
	}

	/**
	 * @param Vector3 $pos
	 */
	public function setSpawn(Vector3 $pos){
		$this->levelData->SpawnX = new IntTag("SpawnX", (int) $pos->x);
		$this->levelData->SpawnY = new IntTag("SpawnY", (int) $pos->y);
		$this->levelData->SpawnZ = new IntTag("SpawnZ", (int) $pos->z);
	}

	public function doGarbageCollection(){

	}

	/**
	 * @return CompoundTag
	 */
	public function getLevelData() : CompoundTag{
		return $this->levelData;
	}

	public function saveLevelData(){
		$WarpList=new CompoundTag('WarpList', []);
		foreach($this->WarpList as $name=>$pos){
			$WarpList[$name]=new CompoundTag($name, [
				'x' => new FloatTag('x', $pos->x),
				'y' => new FloatTag('y', $pos->y),
				'z' => new FloatTag('z', $pos->z),
				'name' => new StringTag('name', $name)
			]);
		}
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->setData(new CompoundTag("", [
			"Data" => $this->levelData,
			"WarpList" => $WarpList
		]));
		$buffer = $nbt->writeCompressed();
		file_put_contents($this->getPath() . "level.dat", $buffer);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return null|ChunkRequestTask
	 */
	public function requestChunkTask(int $x, int $z){
		$chunk = $this->getChunk($x, $z, false);
		if(!($chunk instanceof Chunk)){
			throw new ChunkException("Invalid Chunk sent");
		}

		if($this->asyncChunkRequest){
			return new ChunkRequestTask($this->level, $chunk);
		}

		//non-async, call the callback directly with serialized data
		$this->getLevel()->chunkRequestCallback($x, $z, $chunk->networkSerialize());

		return null;
	}
}

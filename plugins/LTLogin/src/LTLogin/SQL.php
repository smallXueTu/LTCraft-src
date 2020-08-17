<?php
namespace LTLogin;
use pocketmine\Server;
use pocketmine\plugins\PluginBase;
class SQL{
	public static $instance=null;
	public static function getInstance(){
		return self::$instance;
	}
	public function  __construct($server){
		self::$instance=$this;
		$this->server=$server;
		// $this->conn=mysqli_connect('116.31.123.90','root','mlgbmgtb2333','server');
		$this->conn=&$this->server->conn;
	}
	public function add($name,$password){
		$sql="INSERT INTO user(name, password) VALUES ('".$name."','".$password."')";
		if(!$this->conn->exec($sql)){
			$this->server->fixConn();
			$this->conn->exec($sql);
		}
		return true;
	}
	public function ThereAre($name){
		$sql="SELECT * FROM user WHERE name='{$name}' LIMIT 1";
		$c=$this->conn->query($sql);
		if(!$c){
			$this->server->fixConn();
			$c=$this->conn->query($sql);
		}
		if(count($c->fetchAll())>=1){
			return true;
		}else{
			return false;
		}
	}
	public function getPassword($name){
		$sql="select password from user where name='{$name}' LIMIT 1";
		$result=$this->conn->query($sql);
		if(!$result){
			$this->server->fixConn();
		$result=$this->conn->query($sql);
		}
		$row=$result->fetch();
		return $row['password'];
	}
	public function getEmail($name){
		$sql="select email from user where name='{$name}'";
		$run=$this->conn->query($sql);
		if(!$run){
			$this->server->fixConn();
			$run=$this->conn->query($sql);
		}
		$row=$run->fetch();
		return $row['email'];
	}
	public function addEmail($name,$email){
		if($this->getEmail($name)==NULL){
			$sql="update user set email='{$email}' where name='{$name}'";
			$run=$this->conn->exec($sql);
			if(!is_numeric($run)){
				$this->server->fixConn();
				$this->conn->exec($sql);
			}
			return true;
		}
		return false;
	}
	public function addCode($name,$code){
		return true;
		if(!$this->ThereAre($name)){
			return false;
		}
		$time=time()+300;
		$sql="update user set code='{$code}',outtime='{$time}' where name='{$name}'";
		$run=mysqli_query($this->conn,$sql);
		if(!$run){
			$this->server->fixConn();
			mysqli_query($this->conn,$sql);
		}
		return true;
	}
	public function getBack($name){
		$sql="select back from user where name='{$name}' LIMIT 1";
		$run=$this->conn->query($sql);
		if(!$run){
			$this->server->fixConn();
			$run=$this->conn->query($sql);
		}
		$row=$run->fetch();
		return $row['back']==NULL?false:$row['back'];
	}
	public function addBack($name,$back){
		$sql="update user set back='{$back}' where name='{$name}'";
		$run=$this->conn->exec($sql);
		if(!is_numeric($run)){
			$this->server->fixConn();
			$this->conn->exec($sql);
		}
		return true;
	}
	public function checkMatchingAddress($ip){
		$sql="select name from user where ip='{$ip}'";
		$run=$this->conn->query($sql);
		if(!$run){
			$this->server->fixConn();
			$run=$this->conn->query($sql);
		}
		$row=$run->fetchAll();
		if(count($row)<=1)return false;
		return $row;
	}
	public function updateIp($name,$ip){
		$sql="update user set ip='{$ip}' where name='{$name}'";
		$run=$this->conn->exec($sql);
		if(!is_numeric($run)){
			$this->server->fixConn();
			$this->conn->exec($sql);
		}
		return true;
	}
	public function updatePassword($name,$password){
		$sql="update user set password='{$password}' where name='{$name}'";
		$run=$this->conn->exec($sql);
		if(!is_numeric($run)){
			$this->server->fixConn();
			$this->conn->exec($sql);
		}
		return true;
	}
	public function getCode($name){
			return true;
		if(!$this->ThereAre($name)){
			return false;
		}
		$sql="select * from user where name='{$name}'";
		$run=mysqli_query($this->conn,$sql);
		if(!$run){
			$this->server->fixConn();
			$run=mysqli_query($this->conn,$sql);
		}
		$row=mysqli_fetch_assoc($run);
		return $row;
	}
	public function getMoveChechStatus($name){
		$sql="select moveCheck from user where name='{$name}' LIMIT 1";
		$run=$this->conn->query($sql);
		if(!$run){
			$this->server->fixConn();
			$run=$this->conn->query($sql);
		}
		$row=$run->fetch();
		return $row['moveCheck']==1?true:false;
	}
}
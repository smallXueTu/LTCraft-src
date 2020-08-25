<?php
namespace LTLogin;

class Mailer  extends \Thread{
  private $host;
  private $port = 25;
  private $user;
  private $pass;
  private $debug = false;
  private $sock;
  private $message;
 
	public function run(){
		$this->sendMail();
	}
  public function __construct($host,$port,$user,$pass,$debug = false,$array){
    $this->host = $host;
    $this->port = $port;
    $this->user = base64_encode($user); //用户名密码一定要使用base64编码才行
    $this->pass = base64_encode($pass);
    $this->debug = $debug;
	$this->message=$array;
  // socket连接
  }
//发送SMTP指令，不同指令的返回码可能不同
  public function execCommand($cmd,$return_code){
    fwrite($this->sock,$cmd);

    $response = fgets($this->sock);
    $this->debug('cmd:'.$cmd .';response:'.$response);
    if(strstr($response,$return_code) === false){
      return false;
    }
    return true;
  }
 
  public function sendMail(){
	// echo '正在连接至163服务器'.PHP_EOL;
    $this->sock = fsockopen($this->host,$this->port);
    if(!$this->sock){
      exit('出错啦');
    }
	// echo '连接成功'.PHP_EOL;
	// echo '读取数据'.PHP_EOL;
  // 读取smtp服务返回给我们的数据
    $response = fgets($this->sock);
	// echo '读取完成'.PHP_EOL;
    $this->debug($response);
        // 如果响应中有220返回码，说明我们连接成功了
    if(strstr($response,'220') === false){
		exit('出错啦');
	}
//detail是邮件的内容，一定要严格按照下面的格式，这是协议规定的
    $detail = 'From:'.$this->message['from']."\r\n";
    $detail .= 'To:'.$this->message['to']."\r\n";
    $detail .= 'Subject:'.$this->message['subject']."\r\n";
    $detail .= 'Content-Type: Text/html;'."\r\n";
    $detail .= 'charset=gb2312'."\r\n\r\n";
    $detail .= $this->message['body'];
    $this->execCommand("HELO ".$this->host."\r\n",250);
    $this->execCommand("AUTH LOGIN\r\n",334);
    $this->execCommand($this->user."\r\n",334);
    $this->execCommand($this->pass."\r\n",235);
    $this->execCommand("MAIL FROM:<".$this->message['from'].">\r\n",250);
    $this->execCommand("RCPT TO:<".$this->message['to'].">\r\n",250);
    $this->execCommand("DATA\r\n",354);
    $this->execCommand($detail."\r\n.\r\n",250);
    $this->execCommand("QUIT\r\n",221);
	// if($this->player){
		// $this->player->sendMessage('§l§a[提示]§a验证码已发送，请查收，五分钟内有效！');
	// }
	// echo '发送完成！';
  }
  public function debug($message){
    if($this->debug){
      echo 'Debug:'.$message . PHP_EOL .'';
    }
  }
 
  public function __destruct()
  {
    fclose($this->sock);
  }
 
}
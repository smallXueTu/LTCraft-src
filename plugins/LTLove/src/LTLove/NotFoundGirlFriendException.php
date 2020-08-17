<?php
namespace LTLove;
class NotFoundGirlFriendException extends \RuntimeException{
    public function __construct(){
        parent::__construct('无法找到女朋友');
    }
}
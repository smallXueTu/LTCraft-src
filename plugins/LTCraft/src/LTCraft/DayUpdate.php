<?php


namespace LTCraft;


use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\Task;

class DayUpdate extends PluginTask
{
    private int $lastDay;
    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin);
        $this->lastDay = intval(date("d"));
    }

    public function onRun($currentTick)
    {
        if ($this->lastDay !== intval(date("d"))){
            $this->lastDay = intval(date("d"));
            Main::getInstance()->updateHeadCountConfig();
            \LTGrade\Main::getInstance()->updateTaskConfig();
            \LTEntity\Main::getInstance()->updateWeeksExpConfig();
            // $this->number->setAll([]);
//            foreach(Main::getInstance()->server->getOnlinePlayers() as $player){
//                Main::getInstance()->onlineTime[$player->getName()] = time();
//            }
        }
    }
}
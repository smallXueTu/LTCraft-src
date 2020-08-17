<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\generator\biome\Biome;

class BiomeSubCommand extends SubCommand
{
    private $biomes = [
        "平原" => Biome::PLAINS,//平原
        "沙漠" => Biome::DESERT,//沙漠
        "森林" => Biome::FOREST,//森林
        "热带" => Biome::TAIGA,//热带
        "沼泽" => Biome::SWAMP,//沼泽
        "雪地" => Biome::ICE_PLAINS,//雪地
    ];

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.biome");
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) === 0) {
            $biomes = TextFormat::WHITE . implode(", ", array_keys($this->biomes));
            $sender->sendMessage($this->translateString("biome.possible", [$biomes]));
            return true;
        } elseif (count($args) !== 1) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $biome = strtoupper($args[0]);
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . "你没有站在地皮上");
            return true;
        }
        if ($plot->owner !== strtolower($sender->getName()) and !$sender->isOp()) {
            $sender->sendMessage(TextFormat::RED . "你不是这个领地的领主！");
            return true;
        }
        if (!isset($this->biomes[$biome])) {
            $sender->sendMessage(TextFormat::RED . "/p biome [生态系名称]");
            $biomes = implode(", ", array_keys($this->biomes));
            $sender->sendMessage(TextFormat::RED . "可以使用的生态系为:".$biomes);
            return true;
        }
        $biome = Biome::getBiome($this->biomes[$biome]);
        if ($this->getPlugin()->setPlotBiome($plot, $biome)) {
            $sender->sendMessage(TextFormat::GREEN . "修改生态成功！");
        } else {
            $sender->sendMessage(TextFormat::RED . "修改生态失败！");
        }
        return true;
    }
}

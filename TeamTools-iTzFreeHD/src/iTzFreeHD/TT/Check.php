<?php
namespace iTzFreeHD\TT;


use pocketmine\scheduler\Task;
use DateTime;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Check extends Task {


    public $plugin;

    public function __construct(TeamTools $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick)
    {
        $cfg = new Config($this->plugin->getDataFolder().'/config.yml', Config::YAML);
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $cfg->reload();
            $check = $cfg->get($player->getName());
            if ($check == null) {

            } else {
                $excheck = explode(',', $check);
                if(new DateTime("now") < new DateTime($excheck[2])){
                    //$this->plugin->getServer()->broadcastMessage("Test");
                } else {
                    $cfg->set($player->getName(), null);
                    $cfg->save();
                    $player->sendMessage(TextFormat::GREEN."Du wurdest entmutet");
                }
            }

        }
    }
}
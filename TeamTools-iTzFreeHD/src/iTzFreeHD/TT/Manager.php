<?php


namespace iTzFreeHD\TT;


use pocketmine\utils\Config;

class Manager
{
    public $plugin;
    private $cfg;

    public function __construct(TeamTools $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getReportIDs()
    {
        $cfg = $this->plugin->cfg;
        if ($cfg instanceof Config) {
            $ids = $cfg->get('Report-Reasons');
            $array = [];
            $count = 1;
            foreach ($ids as $id) {
                $array[$count] = $id;
                $count++;
            }
            return $array;
        }
    }

    public function isPlayerReported($name)
    {

    }

    public function isGlobal()
    {
        $this->cfg = new Config($this->plugin->getDataFolder().'/config.yml', Config::YAML);
        if (!file_exists('/TT/') and $this->cfg->get('Global-Mode') == true) {
            $this->plugin->getServer()->shutdown();
        }
        return $this->cfg->get('Global-Mode');
    }
}
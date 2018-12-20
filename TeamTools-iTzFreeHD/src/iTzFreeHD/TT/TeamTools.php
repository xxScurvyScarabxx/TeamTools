<?php
namespace iTzFreeHD\TT;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as c;
use pocketmine\plugin\PluginBase;
use DateTime;

class TeamTools extends PluginBase implements Listener {

    public $p = c::GRAY.'['.c::RED.'TeamTools'.c::GRAY.'] ';
    public $cfg;
    public $vanish = [];
    public $Manager;
    public $mutes = array(
        1 => "Spamming",
        2 => "Beleidigung",
        3 => "Provokation",
        4 => "Drohung",
        5 => "Rassismus",
        6 => "Werbung",
        7 => "Sonstiges"
    );
    public $bans = array(
        1 => ['Grund' => 'Hacking', 'Dauer' => '0:5:D'],
        2 => ['Grund' => 'Teaming', 'Dauer' => '0:2:D'],
        3 => ['Grund' => 'Bugusing', 'Dauer' => 'T:10:H'],
        4 => ['Grund' => 'Rechte ausnutzen', 'Dauer' => 'T:5:H'],
        5 => ['Grund' => 'Provokation', 'Dauer' => 'T:1:H'],
        6 => ['Grund' => 'Rasismus', 'Dauer' => '0:5:D'],
        7 => ['Grund' => 'Ban-Umgehung', 'Dauer' => 'Permanent'],
        8 => ['Grund' => 'Extremes-Hacking', 'Dauer' => '0:1:M'],
        9 => ['Grund' => 'Extrem', 'Dauer' => 'Permanent']
    );


    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder().'/Bans');
        $this->getLogger()->info(c::GREEN.'Enabled');
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new Check($this), 20);
        $vtask = new VanishTask($this);
        $this->cfg = new Config($this->getDataFolder().'/config.yml', Config::YAML);
        $this->Manager = new Manager($this);

        /**if (empty($config->get('Report-Reasons'))) {
            $config->set('Report-Reasons', [
                'Hacking',
                'Bugusing',
                'Chat-verhalten'
            ]);
            $config->save();
        }**/

        if (empty($this->cfg->get('Global-Mode'))) {
            $this->cfg->set('Global-Mode', false);
            $this->cfg->save();
        }

        if ($this->Manager->isGlobal()) {
            if (!file_exists('/TT/')) {
                mkdir('/TT/');
                mkdir('/TT/Bans');
            }
        }

    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
    {
#########################################
        $Manager = new Manager($this);
        if ($Manager->isGlobal()) {
            $config = new Config('/TT/data.yml', Config::YAML);
        } else {
            $config = new Config($this->getDataFolder().'/data.yml', Config::YAML);
        }


        if ($command->getName() == 'reports') {
            if ($sender->hasPermission('tt.report')) {
                if ($sender instanceof Player) {

                }
            }
        }


        if ($command->getName() == 'report') {
            if ($sender instanceof Player) {
                if (isset($args[0]) and isset($args[1])) {
                    $rplayer = $this->getServer()->getPlayerExact($args[0]);
                    if ($rplayer != null) {
                        if (is_numeric($args[1])) {
                            $ids = $Manager->getReportIDs();
                            $count = count($ids);
                            if ($args[1] > 0 and $args[1] <= $count) {

                            }
                        }
                    }
                }

            }
        }



        if ($command->getName() == 'block') {
            if ($sender->hasPermission('tt.block')) {
                if (isset($args[0])) {
                    if (isset($args[1])) {
                        $sender2 = $this->getServer()->getPlayer($args[0]);
                        if ($sender2 instanceof Player) {
                            if ($sender2->isOnline()) {
                                if ($Manager->isGlobal()) {
                                    $pcfg = new Config('/TT/Bans/'.$sender2->getName().".yml", Config::YAML);
                                } else {
                                    $pcfg = new Config($this->getDataFolder().'/Bans/'.$sender2->getName().".yml", Config::YAML);
                                }
                                if (empty($pcfg->get('points'))) {
                                    $pcfg->set('points', 1);
                                    $pcfg->save();
                                }

                                if (array_key_exists($args[1], $this->bans)){

                                    $id = $this->bans[$args[1]];
                                    if ($id['Dauer'] == 'Permanent') {
                                        $pcfg->set('Info', $args[1].','.$sender->getName().','.'Permanent');
                                        $sender->sendMessage($this->p.c::GREEN.'Der Spieler '.$sender2->getName().' wurde mit dem Grund '.c::DARK_RED.$id['Grund'].c::AQUA.' Permanet '.c::GREEN.'vom Netzwerk ausgeschlossen');
                                        $sender2->kick(c::RED.'Du wurdest gebannt für mehr Infos rejoine!', false);
                                        $pcfg->save();
                                    } else {
                                        $exid = explode(':', $id['Dauer']);
                                        $time = new \DateTime('now');

                                        $points = $pcfg->get('points');
                                        if ($exid[0] == 'T') {
                                            $time->add(new \DateInterval('PT'.$exid[1]*$points.$exid[2]));
                                        } else {
                                            $time->add(new \DateInterval('P'.$exid[1]*$points.$exid[2]));

                                        }
                                        if ($points == 7) {
                                            $pcfg->set('Info', $args[1].','.$sender->getName().','.'perma');
                                            $sender->sendMessage($this->p.c::GREEN.'Der Spieler '.$sender2->getName().' wurde mit dem Grund '.c::DARK_RED.$id['Grund'].c::AQUA.' Permanet'.c::GREEN.'vom Netzwerk ausgeschlossen');
                                            $sender2->kick(c::RED.'Du wurdest gebannt für mehr Infos rejoine!', false);
                                        } else {

                                            $pcfg->set('Info', $args[1].','.$sender->getName().','.$time->format('Y-m-d H:i:s'));
                                            $sender->sendMessage($this->p.c::GREEN.'Der Spieler '.$sender2->getName().' wurde mit dem Grund '.c::DARK_RED.$id['Grund'].c::GREEN.' für '.c::AQUA.$exid[1]*$points.$exid[2].c::GREEN.' vom Netzwerk ausgeschlossen');
                                            $sender2->kick(c::RED.'Du wurdest gebannt für mehr Infos rejoine!', false);
                                        }
                                        $points++;
                                        if ($pcfg->get('points') <=7) {
                                            $pcfg->set('points', $points);
                                        }
                                        $pcfg->save();
                                    }
                                }  elseif ($args[1] = 'setpoints') {
                                    if (isset($args[2])) {
                                        if (is_numeric($args[2])) {
                                            if ($args[2] <= 7 and $args >= 1) {
                                                $config->reload();
                                                $pcfg->set('points', $args[2]);
                                                $sender->sendMessage(c::GREEN."Du hast die Ban-Punkte von ".$sender2->getName()." erfolgreich auf ".$args[2]." gesetzt");
                                                $pcfg->save();

                                            } else {
                                                $sender->sendMessage($this->p.c::RED.'Bitte wähle eine Zahl zwisch 0-3');
                                            }
                                        }
                                    }
                                } else {
                                    $this->sendMessage($sender, 'ban');
                                }
                            } else {
                                $sender->sendMessage($this->p.c::RED.'Der Spieler ist nicht Online');
                            }

                        } else {
                            $sender->sendMessage($this->p.c::RED.'Der Spieler ist nicht Online');
                        }
                    } else {
                        $this->sendMessage($sender, 'block');
                    }
                } else {
                    $this->sendMessage($sender, 'block');
                }
            } else {
                $this->sendMessage($sender, 'perms');
            }
        }

        if ($command->getName() == 'blockinfo') {
            if ($sender->hasPermission('tt.block')) {
                if (isset($args[0])) {
                    if ($Manager->isGlobal()) {
                        $pcfg = new Config('/TT/Bans/'.$args[0].".yml", Config::YAML);
                    } else {
                        $pcfg = new Config($this->getDataFolder().'/Bans/'.$args[0].".yml", Config::YAML);
                    }
                    $info = $pcfg->get('Info');

                    if ($info == null) {
                        $sender->sendMessage(c::RED.'Dieser Spieler ist nicht gebannt er hat '.c::AQUA.$pcfg->get('points').c::RED.' Bann punkte');
                    } else {
                        $exinfo = explode(',',$info);
                        $id = $this->bans[$exinfo[0]];
                        $bantime = new DateTime($exinfo[2]);
                        $time = new DateTime("now");

                        $ct = explode(":", $time->format('Y:m:d:H:i:s'));
                        $bantime->sub(new \DateInterval("P".$ct[0]."Y".$ct[1]."M".$ct[2]."DT".$ct[3]."H".$ct[4]."M".$ct[5]."S"));
                        $extime = explode(":", $bantime->format('m:d:H:i:s'));

                        if ($extime[0] == 12) {
                            if ($extime[1] == 30) {
                                $bt = c::RESET.$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden.";
                            } else {
                                $bt = c::RESET.$extime[1]." Tage, ".$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden.";
                            }

                        } else {
                            $bt = c::RESET.$extime[0]." Monate, ".$extime[1]." Tage, ".$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden.";
                        }

                        $ban = array(
                            c::GRAY.'------------------------------------',
                            c::GOLD.'  Name: '.c::RESET.$args[0],
                            //c::GOLD.'Status: ',
                            c::GOLD.'  Grund: '.c::RESET.$id['Grund'],
                            c::GOLD.'  Mute-Points: '.c::RESET.$pcfg->get('points').' (Standart = 1)',
                            c::GOLD.'  Vebleibendezeit: '.$bt,
                            c::GOLD.'  Geblockt von: '.c::RESET.$exinfo[1]
                        );
                        $sender->sendMessage($ban[0]."\n".$ban[1]."\n".c::GOLD.'  Status: '.c::RESET.'gebannt'."\n".$ban[2]."\n".$ban[3]."\n".$ban[4]."\n".$ban[5]."\n".$ban[0]);
                    }
                }  else {
                    $this->sendMessage($sender, 'blockinfo');
                }
            } else {
                $this->sendMessage($sender, 'perms');
            }
        }

        if ($command->getName() == 'blockid') {
            if ($sender->hasPermission('tt.block')) {
                $id = 0;
                $sender->sendMessage(c::GRAY.'---------------'.c::GOLD."BlockIDs".c::GRAY.'---------------');
                foreach ($this->bans as $banid) {

                    $id++;
                    $sender->sendMessage(c::GRAY."  ".$id.' -> '.c::GOLD.$banid['Grund']);
                }

                $sender->sendMessage(c::GRAY.'------------------------------------');
            } else {
                $this->sendMessage($sender, 'perms');
            }
        }

        if ($command->getName() == 'unblock') {
            if ($sender->hasPermission('tt.block')) {
                if (isset($args[0])) {
                    if ($Manager->isGlobal()) {
                        $pcfg = new Config('/TT/Bans/'.$args[0].".yml", Config::YAML);
                    } else {
                        $pcfg = new Config($this->getDataFolder().'/Bans/'.$args[0].".yml", Config::YAML);
                    }

                    if (!empty($pcfg->get('Info'))) {
                        $pcfg->set('Info', null);
                        $pcfg->save();
                        $sender->sendMessage($this->p.c::GREEN.'Der Spieler '.$args[0].' wurde erfolgreich entbannt');
                    } else {
                        $sender->sendMessage($this->p.c::RED.'Der Spieler ist nicht gebannt');
                    }
                } else {
                    $this->sendMessage($sender, 'block');
                }
            } else {
                $this->sendMessage($sender, 'perms');
            }
        }



        ################################################
        if ($command->getName() == 'mute') {
            if ($sender->hasPermission('tt.mute')) {
                $oneHtime = new \DateTime('now');
                $oneHtime->add(new \DateInterval("PT1H"));

                $twoHtime = new \DateTime('now');
                $twoHtime->add(new \DateInterval("PT2H"));

                $threeHtime = new \DateTime('now');
                $threeHtime->add(new \DateInterval("PT3H"));

                $daytime = new \DateTime('now');
                $daytime->add(new \DateInterval("P1D"));
                if (isset($args[0])) {
                    if (isset($args[1])) {
                        $sender2 = $this->getServer()->getPlayer($args[0]);
                        if ($sender2->isOnline()) {
                            if (array_key_exists($args[1], $this->mutes)) {
                                if ($config instanceof Config) {
                                    $config->reload();
                                    $strafen = $config->get($sender2->getName()."_strafen");
                                    if (empty($strafen)) {
                                        $config->set($sender2->getName(), $args[1].",".$sender->getName().",".$oneHtime->format('Y-m-d H:i:s'));
                                        $config->set($sender2->getName()."_strafen", 1);
                                        $sender->sendMessage($this->p.c::GRAY.'Du hast '.$sender2->getName().' für 1h vom Chat verbannt');
                                        $sender2->sendMessage(c::GRAY.'Du wurdest für 1h vom Chat verbannt');
                                    } else {
                                        if ($strafen == 1) {
                                            $config->set($sender2->getName(), $args[1].",".$sender->getName().",".$twoHtime->format('Y-m-d H:i:s'));
                                            $config->set($sender2->getName()."_strafen", 2);

                                            $sender->sendMessage($this->p.c::GRAY.'Du hast '.$sender2->getName().' für 2h vom Chat verbannt');
                                            $sender2->sendMessage(c::GRAY.'Du wurdest für 2h vom Chat verbannt');
                                        } elseif ($strafen == 2) {
                                            $config->set($sender2->getName(), $args[1].",".$sender->getName().",".$threeHtime->format('Y-m-d H:i:s'));
                                            $config->set($sender2->getName()."_strafen", 3);

                                            $sender->sendMessage($this->p.c::GRAY.'Du hast '.$sender2->getName().' für 3h vom Chat verbannt');
                                            $sender2->sendMessage(c::GRAY.'Du wurdest für 3h vom Chat verbannt');
                                        } elseif ($strafen == 3) {
                                            $config->set($sender2->getName(), $args[1].",".$sender->getName().",".$daytime->format('Y-m-d H:i:s'));

                                            $sender->sendMessage($this->p.c::GRAY.'Du hast '.$sender2->getName().' für 1 Tag vom Chat verbannt');
                                            $sender2->sendMessage(c::GRAY.'Du wurdest für 1 Tag vom Chat verbannt');
                                        }
                                    }
                                    $config->save();
                                }
                            } elseif ($args[1] = 'setpoints') {
                                if (isset($args[2])) {
                                    if (is_numeric($args[2])) {
                                        if ($args[2] <= 3 and $args >= 1) {
                                            if ($config instanceof Config) {
                                                $config->reload();
                                                $config->set($sender2->getName()."_strafen", $args[2]);
                                                //$config->remove($sender2->getName()."_strafen");
                                                $sender->sendMessage(c::GREEN."Du hast die Mute-Punkte von ".$sender2->getName()." erfolgreich auf ".$args[2]." gesetzt");
                                                $config->save();
                                            }
                                        } elseif ($args[2] == 0) {
                                            if ($config instanceof Config) {
                                                $config->reload();
                                                $config->remove($sender2->getName()."_strafen");
                                                $config->save();
                                                $sender->sendMessage(c::GREEN."Du hast die Mute-Punkte von ".$sender2->getName()." erfolgreich auf ".$args[2]." gesetzt");
                                            }

                                        }
                                    } else {
                                        $sender->sendMessage($this->p.c::RED.'Bitte wähle eine Zahl zwisch 0-3');
                                    }
                                }
                            } else {
                                $this->sendMessage($sender, 'mute');
                            }
                        } else {
                            $sender->sendMessage($this->p.c::RED.'Der Spieler ist nicht Online!');
                        }
                    } else {
                        $this->sendMessage($sender, 'mute');
                    }
                } else {
                    $this->sendMessage($sender, 'mute');
                }
            } else {
                $this->sendMessage($sender, 'perms');
            }

        }

        if ($command->getName() == 'muteid') {
            if ($sender->hasPermission('tt.mute')) {
                $id = 0;
                $sender->sendMessage(c::GRAY.'---------------'.c::GOLD."MuteIDs".c::GRAY.'---------------');
                foreach ($this->mutes as $muteid) {

                    $id++;
                    $sender->sendMessage(c::GRAY."  ".$id.' -> '.c::GOLD.$muteid);
                }

                $sender->sendMessage(c::GRAY.'------------------------------------');
            } else {
                $this->sendMessage($sender, 'perms');
            }
        }

        if ($command->getName() == 'muteinfo') {
            if (isset($args[0])) {
                if ($config instanceof Config) {
                    $config->reload();
                    if ($config->exists($args[0])) {
                        $check = $config->get($args[0]);
                        $excheck = explode(",", $check);

                        #####
                        $ct1 = new DateTime("now");
                        $time = new DateTime($excheck[2]);
                        $ct = explode(":", $ct1->format('Y:m:d:H:i:s'));
                        $time->sub(new \DateInterval("P".$ct[0]."Y".$ct[1]."M".$ct[2]."DT".$ct[3]."H".$ct[4]."M".$ct[5]."S"));
                        $extime = explode(":", $time->format('d:H:i:s'));
                        #####
                        $nmute = array(
                            c::GRAY.'------------------------------------',
                            c::GOLD.'  Name: '.c::RESET.$args[0],
                            //c::GOLD.'Status: ',
                            c::GOLD.'  Grund: '.c::RESET.$this->mutes[$excheck[0]],
                            c::GOLD.'  Mute-Points: '.c::RESET.$config->get($args[0].'_strafen'),
                            c::GOLD.'  Vebleibendezeit: '.c::RESET.$extime[1]." Stunden, ".$extime[2]." Minuten, ".$extime[3]." Sekunden.",
                            c::GOLD.'  Gemutet von: '.c::RESET.$excheck[1]
                        );
                        if ($check == null) {
                            $sender->sendMessage($nmute[0]."\n".$nmute[1]."\n".c::GOLD.'  Status: '.c::RESET.'Nicht gemutet'.$nmute[1]);
                        } else {
                            $sender->sendMessage($nmute[0]."\n".$nmute[1]."\n".c::GOLD.'  Status: '.c::RESET.'gemutet'."\n".$nmute[2]."\n".$nmute[3]."\n".$nmute[4]."\n".$nmute[5]."\n".$nmute[0]);
                        }
                    } else {
                        $sender->sendMessage($this->p.c::RED.'Der Spieler wurde noch nie gemutet');
                    }
                }
            } else {
                $this->sendMessage($sender, 'muteinfo');
            }
        }

        if ($command->getName() == 'unmute') {
            if ($sender->hasPermission('tt.mute')) {
                if (isset($args[0])) {
                    if ($config instanceof Config) {
                        $config->reload();
                        if (!empty($config->get($args[0]))) {
                            $config->set($args[0], null);
                            $config->save();
                            $sender->sendMessage($this->p.c::GREEN.'Der Spieler '.$args[0].' wurde erfolgreich entmutet');
                        } else {
                            $sender->sendMessage($this->p.c::RED.'Der Spieler ist nicht gemutet');
                        }
                    }
                }
            } else {
                $this->sendMessage($sender, 'perms');
            }

        }



        if ($command->getName() == 'v') {
            if (isset($args[0])) {
                $this->getServer()->dispatchCommand($sender, 'staff vanish ' . $args[0]);
            } else {
                $this->getServer()->dispatchCommand($sender, 'staff vanish');
            }
        }

        if ($command->getName() == 'fly') {
            if ($sender instanceof Player) {
                if ($sender->hasPermission("tt.fly") or $sender->isOp()) {
                    if (!$sender->getAllowFlight()) {
                        $sender->setAllowFlight(true);
                        $sender->sendMessage($this->p . c::GREEN . "Du kannst jetzt Fliegen.");
                        return true;
                    } else {
                        if ($sender->getAllowFlight()) {
                            $sender->setAllowFlight(false);
                            $sender->sendMessage($this->p . c::RED . "Du kannst jetzt nicht mehr fliegen.");
                            return true;
                        }
                    }
                } else {
                    $sender->sendMessage($this->p . c::RED . "Du musst einen Rang besitzen");
                }
            } else {
                $sender->sendMessage($this->p . c::DARK_RED . "This command is only available in-game!");

            }
        }


        if ($command->getName() === "staff") {
            if ($sender instanceof Player) {
                if ($sender->hasPermission("tt.staff")) {
                    if (isset($args[0])) {
                        if ($args[0] === "gm") {
                            if (isset($args[1])) {
                                switch ($args[1]) {
                                    case 0:
                                        if ($sender->hasPermission("tt.staff.gm0")) {
                                            if (isset($args[2])) {
                                                $sender2 = $this->getServer()->getPlayer($args[2]);
                                                if ($sender2 instanceof Player) {
                                                    if ($sender2->isOnline()) {
                                                        $sender2->setGamemode(0);
                                                    } else {
                                                        $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                                    }
                                                } else {
                                                    $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                                }
                                            } else {
                                                $sender->setGamemode(0);
                                                $sender->sendMessage($this->p . c::GRAY . "Dein Gamemode wurde geändert");
                                            }

                                        } else {
                                            $sender->sendMessage($this->p . c::GRAY . "Du hast keine Rechte!");
                                        }
                                        break;
                                    case 1:
                                        if ($sender->hasPermission("tt.staff.gm1")) {

                                            if (isset($args[2])) {
                                                $sender2 = $this->getServer()->getPlayer($args[2]);
                                                if ($sender2 instanceof Player) {
                                                    if ($sender2->isOnline()) {
                                                        $sender2->setGamemode(1);
                                                    } else {
                                                        $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                                    }
                                                } else {
                                                    $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                                }
                                            } else {
                                                $sender->setGamemode(1);
                                                $sender->sendMessage($this->p . c::GRAY . "Dein Gamemode wurde geändert");
                                            }

                                        } else {
                                            $sender->sendMessage($this->p . c::GRAY . "Du hast keine Rechte!");
                                        }
                                        break;
                                    case 2:
                                        if ($sender->hasPermission("tt.staff.gm2")) {
                                            if (isset($args[2])) {
                                                $sender2 = $this->getServer()->getPlayer($args[2]);
                                                if ($sender2 instanceof Player) {
                                                    if ($sender2->isOnline()) {
                                                        $sender2->setGamemode(2);
                                                    } else {
                                                        $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                                    }
                                                } else {
                                                    $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                                }
                                            } else {
                                                $sender->setGamemode(2);
                                                $sender->sendMessage($this->p . c::GRAY . "Dein Gamemode wurde geändert");
                                            }

                                        } else {
                                            $sender->sendMessage($this->p . c::GRAY . "Du hast keine Rechte!");
                                        }
                                        break;
                                    case 3:
                                        if ($sender->hasPermission("tt.staff.gm3")) {
                                            if (isset($args[2])) {
                                                $sender2 = $this->getServer()->getPlayer($args[2]);
                                                if ($sender2 instanceof Player) {
                                                    if ($sender2->isOnline()) {
                                                        $sender2->setGamemode(3);
                                                    } else {
                                                        $sender->sendMessage($this->p . c::GRAY . "Spieler ist nich Online");
                                                    }
                                                } else {
                                                    $sender->sendMessage($this->p . c::GRAY . "Spieler ist nich Online");
                                                }
                                            } else {
                                                $sender->setGamemode(3);
                                                $sender->sendMessage($this->p . c::GRAY . "Dein Gamemode wurde geändert");
                                            }

                                        } else {
                                            $sender->sendMessage($this->p . c::GRAY . "Du hast keine Rechte!");
                                        }
                                        break;
                                }
                            }
                        } elseif ($args[0] == 'vanish') {
                            if ($sender->hasPermission('tt.vanish')) {
                                if (isset($args[1])) {
                                    $sender2 = $this->getServer()->getPlayer($args[1]);
                                    if ($sender2 instanceof Player) {
                                        if ($sender2->isOnline()) {
                                            $name2 = $sender2->getName();
                                            if (!in_array($name2, $this->vanish)) {
                                                $this->vanish[] = $name2;
                                                $sender2->sendMessage($this->p . c::GRAY . 'Du bist nun im Vanish');
                                                $sender->sendMessage($this->p . c::GRAY . $sender2->getName() . ' ist nun im vanish');
                                            } else {
                                                $sender2->sendMessage($this->p . c::GRAY . 'Du hast den Vanishmodus verlassen');
                                                $sender->sendMessage($this->p . c::GRAY . $sender2->getName() . ' hat den Vanish verlassen');
                                                unset($this->vanish[array_search($name2, $this->vanish)]);
                                                foreach ($this->getServer()->getOnlinePlayers() as $player) {
                                                    $player->showPlayer($sender2);
                                                }
                                            }
                                        } else {
                                            $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                        }
                                    } else {
                                        $sender->sendMessage($this->p . c::GRAY . "Spieler ist nicht Online");
                                    }
                                } else {
                                    $name = $sender->getName();
                                    if (!in_array($name, $this->vanish)) {
                                        $this->vanish[] = $name;
                                        $sender->sendMessage($this->p . c::GRAY . 'Du bist nun im Vanish');
                                    } else {
                                        $sender->sendMessage($this->p . c::GRAY . 'Du hast den Vanishmodus verlassen');
                                        unset($this->vanish[array_search($name, $this->vanish)]);
                                        foreach ($this->getServer()->getOnlinePlayers() as $player) {
                                            $player->showPlayer($sender);
                                        }
                                    }
                                }

                            } else {
                                $sender->sendMessage($this->p . c::GRAY . "Du hast keine Rechte!");
                            }

                        } else {
                            $this->sendMessage($sender, 'help');
                        }
                    } else {
                        $this->sendMessage($sender, 'help');
                    }
                } else {
                    $sender->sendMessage($this->p . c::GRAY . "Du hast keine Rechte!");
                }
            }
        }
        return false;
    }

    public function sendMessage($player, $type)
    {
        if ($type == 'help') {
            $player->sendMessage($this->p.c::GRAY.'------------------------------------');
            $player->sendMessage($this->p.c::GOLD.'/staff <gm> <1|2|3> <player>');
            $player->sendMessage($this->p.c::GOLD.'/staff vanish <player> oder /v');
            $player->sendMessage($this->p.c::GOLD.'@t nachricht');
            $player->sendMessage($this->p.c::GOLD.'/fly');
            $player->sendMessage($this->p.c::GRAY.'------------------------------------');
        }
        
        if ($type == 'block') {
            $player->sendMessage($this->p.c::GRAY.'/block <player> <ban-id>');
            $player->sendMessage($this->p.c::GRAY.'/block <player> setpoints <1-7>');
            $player->sendMessage($this->p.c::GRAY.'/unblock <player>');
            $player->sendMessage($this->p.c::GRAY.'/blockid or /bid');
        }
        
        if ($type == 'blockinfo') {
            $player->sendMessage($this->p.c::GRAY.'/blockinfo <player> - Zeitg dir informationen über den Bans eines Spielers');
        }
        
        if ($type == 'perms') {
            $player->sendMessage($this->p.c::RED.'You do not have Permissions');
        }
        
        if ($type == 'muteinfo') {
            $player->sendMessage($this->p.c::GRAY.'/muteinfo <player> - Zeitg dir informationen über den mute eines Spilers');
        }


        if ($type == 'mute') {
            $player->sendMessage($this->p . c::GRAY . '/mute <player> <mute-id>');
            $player->sendMessage($this->p . c::GRAY . '/mute <player> setpoints <0-3>');
            $player->sendMessage($this->p . c::GRAY . '/unmute <player>');
            $player->sendMessage($this->p . c::GRAY . '/muteid or /mid');
        }
    }

    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $msg = $event->getMessage();
        $words = explode(' ', $msg);
        if ($this->Manager->isGlobal()) {
            $config = new Config('/TT/data.yml', Config::YAML);
        } else {
            $config = new Config($this->getDataFolder().'/data.yml', Config::YAML);
        }
        if ($event->getPlayer()->hasPermission('tt.team')) {
            if ($words[0] === '@t' or $words[0] === '@team') {
                array_shift($words);
                $msg = implode(" ", $words);
                $event->setCancelled();
                foreach ($this->getServer()->getOnlinePlayers() as $pn) {

                    if ($pn->hasPermission('tt.team')) {
                        $pn->sendMessage(c::GRAY . "[" . c::RED . "TeamChat" . c::GRAY . "] " . $player->getNameTag() . c::GRAY . " >> " . c::RESET . $msg);
                    }
                }
            }
        }
        if ($config instanceof Config) {
            $config->reload();
            $check = $config->get($player->getName());
            $excheck = explode(",", $check);
            if (!empty($check)) {
                $event->setCancelled(true);
                $ct1 = new DateTime("now");
                $time = new DateTime($excheck[2]);
                $ct = explode(":", $ct1->format('Y:m:d:H:i:s'));
                $time->sub(new \DateInterval("P".$ct[0]."Y".$ct[1]."M".$ct[2]."DT".$ct[3]."H".$ct[4]."M".$ct[5]."S"));
                $extime = explode(":", $time->format('d:H:i:s'));
                $player->sendMessage(c::RED."Du bist für den Grund ".c::ITALIC. $this->mutes[$excheck[0]].c::RESET.c::RED." gemutet"."\n".c::GREEN."Verbleibende Zeit: ".$extime[1]." Stunden, ".$extime[2]." Minuten, ".$extime[3]." Sekunden."."\n".c::RESET.c::GRAY."Für mehr info mache /muteinfo!");


            }
        }

    }

    public function onJoin(PlayerJoinEvent $event)
    {
        if (!file_exists($this->getDataFolder().'/Bans/'.$event->getPlayer()->getName().".yml")) {
            if ($this->Manager->isGlobal()) {
                $pcfg = new Config('/TT/Bans/'.$event->getPlayer()->getName().".yml", Config::YAML);
            } else {
                $pcfg = new Config($this->getDataFolder().'/Bans/'.$event->getPlayer()->getName().".yml", Config::YAML);
            }

            $pcfg->set('points', 1);
            $pcfg->save();
        } else {
            if ($this->Manager->isGlobal()) {
                $pcfg = new Config('/TT/Bans/'.$event->getPlayer()->getName().".yml", Config::YAML);
            } else {
                $pcfg = new Config($this->getDataFolder().'/Bans/'.$event->getPlayer()->getName().".yml", Config::YAML);
            }
            if ($pcfg->exists('Info')) {
                $info = $pcfg->get('Info');
                $exinfo = explode(',',$info);
                $id = $exinfo[0];
                $bannedby = $exinfo[1];
                $banid = $this->bans[$id];
                $event->setJoinMessage('');
                if ($exinfo[2] == 'Permanent') {
                    $event->getPlayer()->kick(c::GRAY.'>>   '.c::RED.'Du wurdest vom Netztwerk ausgeschlossen!'.c::GRAY.'   <<'."\n".c::DARK_RED.'Grund: '.c::GREEN.$banid['Grund'].c::GRAY." >> ".c::DARK_RED."Gebannt von: ".c::GREEN.$bannedby."\n".c::DARK_RED.'Zeitraum: '.c::GREEN.'Permanent', false);
                } else {
                    $bantime = new DateTime($exinfo[2]);

                    if (new DateTime("now") < $bantime) {

                        ####
                        $time = new DateTime("now");

                        $ct = explode(":", $time->format('Y:m:d:H:i:s'));
                        $bantime->sub(new \DateInterval("P".$ct[0]."Y".$ct[1]."M".$ct[2]."DT".$ct[3]."H".$ct[4]."M".$ct[5]."S"));
                        $extime = explode(":", $bantime->format('m:d:H:i:s'));
                        ####.c::GREEN.$extime[0]." Monate, ".$extime[1]." Tage, ".$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden."
                        if ($extime[0] == 12) {
                            if ($extime[1] == 30) {
                                $bt = c::GREEN.$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden.";
                            } else {
                                $bt = c::GREEN.$extime[1]." Tage, ".$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden.";
                            }

                        } else {
                            if ($extime[1] == 30) {
                                $bt = c::GREEN.$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden.";
                            } else {
                                $bt = c::GREEN.$extime[0]." Monate, ".$extime[1]." Tage, ".$extime[2]." Stunden, ".$extime[3]." Minuten, ".$extime[4]." Sekunden.";
                            }

                        }

                        $event->getPlayer()->kick(c::GRAY.'>>   '.c::RED.'Du wurdest vom Netztwerk ausgeschlossen!'.c::GRAY.'   <<'."\n".c::DARK_RED.'Grund: '.c::GREEN.$banid['Grund'].c::GRAY." >> ".c::DARK_RED."Gebannt von: ".c::GREEN.$bannedby."\n".c::DARK_RED.'Zeitraum: '.$bt, false);
                    } elseif ($info != null) {
                        $pcfg->set('Info', null);
                        $pcfg->save();
                        $event->getPlayer()->sendMessage(c::GREEN."Du wurdest entbannt");
                    }
                }

            }
        }
    }


    public function onQuit(PlayerQuitEvent $event)
    {
        $name = $event->getPlayer()->getName();
        if (in_array($name, $this->vanish)) {
            unset($this->vanish[array_search($name, $this->vanish)]);
        }
        $player = $event->getPlayer();


        $pcfg = new Config($this->getDataFolder().'/Bans/'.$event->getPlayer()->getName().".yml", Config::YAML);
        if ($pcfg->exists('Info')) {

            $event->setQuitMessage('');
        }

    }

}
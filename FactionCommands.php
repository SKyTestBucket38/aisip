<?php

namespace FactionsPro;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\level\Position;

class FactionCommands {

    public $plugin;

    public function __construct(FactionMain $pg) {
        $this->plugin = $pg;
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        $this->economyAPI = \onebone\economyapi\EconomyAPI::getInstance ();
        if($sender instanceof Player) {
            $player = $sender->getPlayer()->getName();
            if(strtolower($command->getName('f'))) {
                if(empty($args)) {
                    $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Информация о кланах§8:§f\n§8-§e /clan create§8:§f создать клан.\n§8-§e /clan invite§8:§f пригласить в клан.\n§8-§e /clan leader§8:§f передать руководство над кланом.\n§8-§e /clan promote§8:§f выдать оффицера клана.\n§8-§e /clan demote§8:§f понизить до члена клана.\n§8-§e /clan kick§8:§f выгнать игрока из клана.\n§8-§e /clan accept§8:§f принять приглашение в клан.\n§8-§e /clan deny§8:§f отклонить приглашение в клан.\n§8-§e /clan delete§8:§f удалить клан.\n§8-§e /clan leave§8:§f покинуть клан.\n§8- §fСоздание клана §a1 000 $\n§r"));
                    return true;
                }
                if(count($args == 2)) {
                    if($args[0] == "create") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan create <Название клана>\n§r"));
                            return true;
                        }
                        if(!(ctype_alnum($args[1]))) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Используйте только цифры и английские буквы.\n§r"));
                            return true;
                        }
						$args[1] = $args[1];
                        if($this->plugin->isNameBanned($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Такое название клана запрещено.\n§r"));
                            return true;
                        }
                        if($this->plugin->factionExists($args[1]) == true ) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Такой клан уже существует.\n§r"));
                            return true;
                        }
                        if(strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Название клана слишком длинное.\n§r"));
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Для начала выйдите из клана.\n§r"));
                            return true;
                        } else {
							$factionName = $args[1];
							if(file_exists('clans/'.strtolower($factionName)) && file_get_contents('clans/'.strtolower($factionName)) == "-1"){
								$sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Такой клан уже существует.\n§r"));
								return true;
							}
                            if($this->economyAPI->myMoney($sender) < 999){
                                $sender->sendMessage("§r\n§9§lКланы§r §a×§f Недостаточно денег для создания клана! §c1 000 $");
                                return false;
                            }
                            $money = $this->economyAPI->myMoney($sender);
                            $m = $money-1000;
                            $this->economyAPI->setMoney($sender,$m);
                            $player = strtolower($player);
                            $rank = "Leader";
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", $player);
                            $stmt->bindValue(":faction", $factionName);
                            $stmt->bindValue(":rank", $rank);
                            $result = $stmt->execute();
                            if($this->plugin->prefs->get("FactionNametags")) {
                                $this->plugin->updateTag($player);
                            }
							@file_put_contents('clans/'.strtolower($factionName), 0);
                            $sender->getServer()->broadcastMessage("§r\n§9§lКланы§r §a×§f Игрок §a".$sender->getName()."§f создал клан §a".$factionName."\n§r");
                            $sender->sendMessage($this->plugin->formatMessage("§9§lКланы§r §a×§f Клан был создан. §c-1 000 $", true));
                            return true;
                        }
                    }

                    /////////////////////////////// INVITE ///////////////////////////////

                    if($args[0] == "invite") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan invite <Ник игрока>\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isInFaction($player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане, чтобы использовать эту команду\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "invite")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вам не разрешено приглашать в клан.\n§r"));
                            return true;
                        }
                        if( $this->plugin->isFactionFull($this->plugin->getPlayerFaction($player)) ) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Клан заполнен! Пожалуйста, кикните одного из игроков.\n§r"));
                            return true;
                        }
                        $invited = $this->plugin->getServer()->getPlayerExact($args[1]);
                        if($this->plugin->isInFaction($invited) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f У данного игрока уже есть клан!\n§r"));
                            return true;
                        }
                        if(!$invited instanceof Player) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрока нет в сети!\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $invitedName = $invited->getName();
                        $rank = "Member";

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
                        $stmt->bindValue(":player", strtolower($invitedName));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":invitedby", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();

                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f ".$invited->getName() . " был приглашён в клан!", true));
                        $invited->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы были приглашены в клан: " . $factionName . "\n§8- §fВступить в клан §a/clan accept\n§8-§f Отклонить это предложение §a/clan deny", true));
                    }

                    /////////////////////////////// LEADER ///////////////////////////////

                    if($args[0] == "leader") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan leader <Ник игрока>\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны находиться в клане.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером клана.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->getServer()->getPlayerExact($args[1]) instanceof Player) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не на сервере.\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $factionName = $this->plugin->getPlayerFaction($player);

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $player);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", strtolower($args[1]));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Leader");
                        $result = $stmt->execute();


                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы больше не лидер клана.", true));
                    }

                    /////////////////////////////// PROMOTE ///////////////////////////////

                    if($args[0] == "promote") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan promote <Ник игрока>\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане для этого.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "promote")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером для этого.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        if($this->plugin->isOfficer($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок уже оффицер клана.\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", strtolower($args[1]));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Officer");
                        $result = $stmt->execute();
                        $player = $args[1];
                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f " . $args[1] . " стал оффицером клана.", true));
                        if($player = $this->plugin->getServer()->getOfflinePlayer($args[1])) {
                            $player->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы стали оффицером клана.", true));
                        }
                        if($this->plugin->prefs->get("FactionNametags")) {
                            $this->plugin->updateTag($player->getName());
                        }
                    }

                    /////////////////////////////// DEMOTE ///////////////////////////////

                    if($args[0] == "demote") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan demote <Ник игрока>\n§r"));
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName()) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане для этого.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "demote")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером для этого.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isOfficer($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок уже участник клана.\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", strtolower($args[1]));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();
                        $player = $args[1];
                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f " . $player . " был понижен до участника.", true));

                        if($player = $this->plugin->getServer()->getOfflinePlayer($args[1])) {
                            $player->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы были понижены до участника клана.", true));
                        }
                        if($this->plugin->prefs->get("FactionNametags")) {
                            $this->plugin->updateTag($player->getName());
                        }
                    }

                    /////////////////////////////// KICK ///////////////////////////////

                    if($args[0] == "kick") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan kick <Ник игрока>\n§r"));
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName()) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане для этого.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "kick")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером для этого.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        $kicked = $this->plugin->getServer()->getOfflinePlayer($args[1]);
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $this->plugin->db->query("DELETE FROM master WHERE player='$args[1]';");
                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно выгнали игрока $args[1].", true));
                        $players[] = $this->plugin->getServer()->getOnlinePlayers();
                        if(in_array($args[1], $players) == true) {
                            $this->plugin->getServer()->getOfflinePlayer($args[1])->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вас выгнали из клана.", true));
                            if($this->plugin->prefs->get("FactionNametags")) {
                                $this->plugin->updateTag($args[1]);
                            }
                            return true;
                        }
                    }

                    if(count($args == 1)) {

                        /////////////////////////////// ACCEPT ///////////////////////////////

                        if(strtolower($args[0]) == "accept") {
                            $player = $sender->getName();
                            $lowercaseName = strtolower($player);
                            $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                            $array = $result->fetchArray(SQLITE3_ASSOC);
                            if(empty($array) == true) {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вас некуда не пригласили.\n§r"));
                                return true;
                            }
                            $invitedTime = $array["timestamp"];
                            $currentTime = time();
                            if(($currentTime - $invitedTime) <= 60) { //This should be configurable
                                $faction = $array["faction"];
                                $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                                $stmt->bindValue(":player", strtolower($player));
                                $stmt->bindValue(":faction", $faction);
                                $stmt->bindValue(":rank", "Member");
                                $result = $stmt->execute();
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно вступили в клан $faction!", true));
                                if($this->plugin->getServer()->getOfflinePlayer($array["invitedby"])) {
                                    if($this->plugin->getServer()->getOfflinePlayer($array["invitedby"])) {
                                        $this->plugin->getServer()->getOfflinePlayer($array["invitedby"])->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f ".$sender->getPlayer()->getName() . " принял ваше приглашение в клан.", true));
                                    }
                                }
                                if($this->plugin->prefs->get("FactionNametags")) {
                                    $this->plugin->updateTag($sender->getName());
                                }
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Время приглашения в клан истекло.\n§r"));
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            }
                        }

                        /////////////////////////////// DENY ///////////////////////////////

                        if(strtolower($args[0]) == "deny") {
                            $player = $sender->getName();
                            $lowercaseName = strtolower($player);
                            $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                            $array = $result->fetchArray(SQLITE3_ASSOC);
                            if(empty($array) == true) {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вас некуда не пригласили.\n§r"));
                                return true;
                            }
                            $invitedTime = $array["timestamp"];
                            $currentTime = time();
                            if( ($currentTime - $invitedTime) <= 60 ) { //This should be configurable
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно отклонили приглашение в клан.", true));
                                $this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f " . $sender->getPlayer()->getName() . " отклонил ваше приглашение в клан.\n§r"));
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Время приглашения в клан истекло.\n§r"));
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            }
                        }

                        /////////////////////////////// DELETE ///////////////////////////////

                        if(strtolower($args[0]) == "delete") {
                            if($this->plugin->isInFaction($player) == true) {
                                if($this->plugin->isLeader($player)) {
                                    $faction = $this->plugin->getPlayerFaction($player);
                                    $this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
                                    $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Клан был успешно удалён.", true));
                                    if($this->plugin->prefs->get("FactionNametags")) {
                                        $this->plugin->updateTag($sender->getName());
                                    }
                                    if(file_exists('clans/'.strtolower($faction))){
                                        @file_put_contents('clans/'.strtolower($faction), "-1");
                                    }
                                } else {
                                    $sender->sendMessage($this->plugin->formatMessage("§r\n§8(§9Кланы§8) §cВы не лидер клана.\n§r"));
                                }
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§8(§9Кланы§8) §cВы не находитесь в клане.\n§r"));
                            }
                        }

                        /////////////////////////////// LEAVE ///////////////////////////////

                        if(strtolower($args[0] == "leave")) {
                            if($this->plugin->isLeader($player) == false) {
                                $remove = $sender->getPlayer()->getNameTag();
                                $faction = $this->plugin->getPlayerFaction($player);
                                $name = $sender->getName();
                                $this->plugin->db->query("DELETE FROM master WHERE player='$name';");
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно вышли с клана.", true));
                                if($this->plugin->prefs->get("FactionNametags")) {
                                    $this->plugin->updateTag($sender->getName());
                                }
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны удалить свой клан.\n§r"));
                            }
                        }
                    }
                }
            } else {
                $this->plugin->getServer()->getLogger()->info($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Войдите в игру что бы использовать команды.\n§r"));
            }
            if(strtolower($command->getName('clan'))) {
                if(empty($args)) {
                    $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Информация о кланах§8:§f\n§8-§e /clan create§8:§f создать клан.\n§8-§e /clan invite§8:§f пригласить в клан.\n§8-§e /clan leader§8:§f передать руководство над кланом.\n§8-§e /clan promote§8:§f выдать оффицера клана.\n§8-§e /clan demote§8:§f понизить до члена клана.\n§8-§e /clan kick§8:§f выгнать игрока из клана.\n§8-§e /clan accept§8:§f принять приглашение в клан.\n§8-§e /clan deny§8:§f отклонить приглашение в клан.\n§8-§e /clan delete§8:§f удалить клан.\n§8-§e /clan leave§8:§f покинуть клан.\n§8- §fСоздание клана §a1 000 $\n§r"));
                    return true;
                }
                if(count($args == 2)) {

                    /////////////////////////////// CREATE ///////////////////////////////

                    if($args[0] == "create") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan create <Название клана>\n§r"));
                            return true;
                        }
                        if(!(ctype_alnum($args[1]))) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Используйте только цифры и английские буквы.\n§r"));
                            return true;
                        }
                        if($this->plugin->isNameBanned($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Такое название клана запрещено.\n§r"));
                            return true;
                        }
                        if($this->plugin->factionExists($args[1]) == true ) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Такой клан уже существует.\n§r"));
                            return true;
                        }
                        if(strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Название клана слишком длинное.\n§r"));
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Для начала выйдите из клана.\n§r"));
                            return true;
                        } else {
							if(file_exists('clans/'.strtolower($factionName)) && file_get_contents('clans/'.strtolower($factionName)) == "-1"){
								$sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Такой клан уже существует.\n§r"));
								return true;
							}
                            if($this->economyAPI->myMoney($sender) < 999){
                                $sender->sendMessage("§r\n§9§lКланы§r §a×§f Недостаточно денег для создания клана! §c1 000 $");
                                return false;
                            }
                            $money = $this->economyAPI->myMoney($sender);
                            $m = $money-1000;
                            $this->economyAPI->setMoney($sender,$m);
                            $factionName = $args[1];
                            $player = strtolower($player);
                            $rank = "Leader";
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", $player);
                            $stmt->bindValue(":faction", $factionName);
                            $stmt->bindValue(":rank", $rank);
                            $result = $stmt->execute();
                            if($this->plugin->prefs->get("FactionNametags")) {
                                $this->plugin->updateTag($player);
                            }
                            @file_put_contents('clans/'.strtolower($factionName), 0);
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Клан был создан. §c-1 000 $", true));
                            return true;
                        }
                    }

                    /////////////////////////////// INVITE ///////////////////////////////

                    if($args[0] == "invite") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan invite <Ник игрока>\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isInFaction($player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане, чтобы использовать эту команду\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "invite")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вам не разрешено приглашать в клан.\n§r"));
                            return true;
                        }
                        if( $this->plugin->isFactionFull($this->plugin->getPlayerFaction($player)) ) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Клан заполнен! Пожалуйста, кикните одного из игроков.\n§r"));
                            return true;
                        }
                        $invited = $this->plugin->getServer()->getPlayerExact($args[1]);
                        if($this->plugin->isInFaction($invited) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f У данного игрока уже есть клан!\n§r"));
                            return true;
                        }
                        if(!$invited instanceof Player) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрока нет в сети!\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $invitedName = $invited->getName();
                        $rank = "Member";

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
                        $stmt->bindValue(":player", strtolower($invitedName));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":invitedby", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();

                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f ".$invited->getName() . " был приглашён в клан!", true));
                        $invited->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы были приглашены в клан: " . $factionName . "\n§8- §fВступить в клан §a/clan accept\n§8-§f Отклонить это предложение §a/clan deny", true));
                    }

                    /////////////////////////////// LEADER ///////////////////////////////

                    if($args[0] == "leader") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan leader <Ник игрока>\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны находиться в клане.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером клана.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->getServer()->getPlayerExact($args[1]) instanceof Player) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не на сервере.\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $factionName = $this->plugin->getPlayerFaction($player);

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $player);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();

                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", strtolower($args[1]));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Leader");
                        $result = $stmt->execute();


                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы больше не лидер клана.", true));
                    }

                    /////////////////////////////// PROMOTE ///////////////////////////////

                    if($args[0] == "promote") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan promote <Ник игрока>\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане для этого.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "promote")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером для этого.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        if($this->plugin->isOfficer($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок уже оффицер клана.\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", strtolower($args[1]));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Officer");
                        $result = $stmt->execute();
                        $player = $args[1];
                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f " . $promoted->getPlayer()->getName() . " стал оффицером клана.", true));
                        if($player = $this->plugin->getServer()->getOfflinePlayer($args[1])) {
                            $player->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы стали оффицером клана.", true));
                        }
                        if($this->plugin->prefs->get("FactionNametags")) {
                            $this->plugin->updateTag($player->getName());
                        }
                    }

                    /////////////////////////////// DEMOTE ///////////////////////////////

                    if($args[0] == "demote") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan demote <Ник игрока>\n§r"));
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName()) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане для этого.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "demote")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером для этого.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isOfficer($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок уже участник клана.\n§r"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", strtolower($args[1]));
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();
                        $player = $args[1];
                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f " . $demoted->getPlayer()->getName() . " был понижен до участника.", true));

                        if($player = $this->plugin->getServer()->getOfflinePlayer($args[1])) {
                            $player->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы были понижены до участника клана.", true));
                        }
                        if($this->plugin->prefs->get("FactionNametags")) {
                            $this->plugin->updateTag($player->getName());
                        }
                    }

                    /////////////////////////////// KICK ///////////////////////////////

                    if($args[0] == "kick") {
                        if(!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Использование:§f /clan kick <Ник игрока>\n§r"));
                            return true;
                        }
                        if($this->plugin->isInFaction($sender->getName()) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть в клане для этого.\n§r"));
                            return true;
                        }
                        if(!$this->plugin->isLeader($player) && !$this->plugin->hasPermission($player, "kick")) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны быть лидером для этого.\n§r"));
                            return true;
                        }
                        if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Игрок не находиться в вашем клане.\n§r"));
                            return true;
                        }
                        $kicked = $this->plugin->getServer()->getOfflinePlayer($args[1]);
                        $factionName = $this->plugin->getPlayerFaction($player);
                        $this->plugin->db->query("DELETE FROM master WHERE player='$args[1]';");
                        $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно выгнали игрока $args[1].", true));
                        $players[] = $this->plugin->getServer()->getOnlinePlayers();
                        if(in_array($args[1], $players) == true) {
                            $this->plugin->getServer()->getOfflinePlayer($args[1])->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вас выгнали из клана.", true));
                            if($this->plugin->prefs->get("FactionNametags")) {
                                $this->plugin->updateTag($args[1]);
                            }
                            return true;
                        }
                    }

                    if(count($args == 1)) {

                        /////////////////////////////// ACCEPT ///////////////////////////////

                        if(strtolower($args[0]) == "accept") {
                            $player = $sender->getName();
                            $lowercaseName = strtolower($player);
                            $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                            $array = $result->fetchArray(SQLITE3_ASSOC);
                            if(empty($array) == true) {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вас некуда не пригласили.\n§r"));
                                return true;
                            }
                            $invitedTime = $array["timestamp"];
                            $currentTime = time();
                            if(($currentTime - $invitedTime) <= 60) { //This should be configurable
                                $faction = $array["faction"];
                                $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                                $stmt->bindValue(":player", strtolower($player));
                                $stmt->bindValue(":faction", $faction);
                                $stmt->bindValue(":rank", "Member");
                                $result = $stmt->execute();
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно вступили в клан $faction!", true));
                                if($this->plugin->getServer()->getOfflinePlayer($array["invitedby"])) {
                                    if($this->plugin->getServer()->getOfflinePlayer($array["invitedby"])) {
                                        $this->plugin->getServer()->getOfflinePlayer($array["invitedby"])->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f ".$sender->getPlayer()->getName() . " принял ваше приглашение в клан.", true));
                                    }
                                }
                                if($this->plugin->prefs->get("FactionNametags")) {
                                    $this->plugin->updateTag($sender->getName());
                                }
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Время приглашения в клан истекло.\n§r"));
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            }
                        }

                        /////////////////////////////// DENY ///////////////////////////////

                        if(strtolower($args[0]) == "deny") {
                            $player = $sender->getName();
                            $lowercaseName = strtolower($player);
                            $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                            $array = $result->fetchArray(SQLITE3_ASSOC);
                            if(empty($array) == true) {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вас некуда не пригласили.\n§r"));
                                return true;
                            }
                            $invitedTime = $array["timestamp"];
                            $currentTime = time();
                            if( ($currentTime - $invitedTime) <= 60 ) { //This should be configurable
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно отклонили приглашение в клан.", true));
                                $this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f " . $sender->getPlayer()->getName() . " отклонил ваше приглашение в клан.\n§r"));
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Время приглашения в клан истекло.\n§r"));
                                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            }
                        }

                        /////////////////////////////// DELETE ///////////////////////////////

                        if(strtolower($args[0]) == "delete") {
                            if($this->plugin->isInFaction($player) == true) {
                                if($this->plugin->isLeader($player)) {
                                    $faction = $this->plugin->getPlayerFaction($player);
                                    $this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
                                    $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Клан был успешно удалён.", true));
                                    if(file_exists('clans/'.strtolower($faction))){
                                        @file_put_contents('clans/'.strtolower($faction), "-1");
                                    }
                                    return true;
                                } else {
                                    $sender->sendMessage($this->plugin->formatMessage("§r\n§8(§9Кланы§8) §cВы не лидер клана.\n§r"));
                                    return true;
                                }
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§8(§9Кланы§8) §cВы не находитесь в клане.\n§r"));
                                return true;
                            }
                        }

                        /////////////////////////////// LEAVE ///////////////////////////////

                        if(strtolower($args[0] == "leave")) {
                            if($this->plugin->isLeader($player) == false) {
                                $remove = $sender->getPlayer()->getNameTag();
                                $faction = $this->plugin->getPlayerFaction($player);
                                $name = $sender->getName();
                                $this->plugin->db->query("DELETE FROM master WHERE player='$name';");
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы успешно вышли с клана.", true));
                                if($this->plugin->prefs->get("FactionNametags")) {
                                    $this->plugin->updateTag($sender->getName());
                                }
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Вы должны удалить свой клан.\n§r"));
                            }
                        }
                    }
                }
            } else {
                $this->plugin->getServer()->getLogger()->info($this->plugin->formatMessage("§r\n§9§lКланы§r §a×§f Войдите в игру что бы использовать команды.\n§r"));
            }
        }
    }
}

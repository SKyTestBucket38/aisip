<?php

namespace Auth;

use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\Player;

use pocketmine\utils\Config;

use pocketmine\Server;
use pocketmine\entity\Effect as E;

class Main extends PluginBase {

    public $db;
    
    public $login_attempts = [];
    public $need_to_auth = [];
    public $write_reg_pass_again = [];
    
    public function onEnable() {
$folder = $this->getServer()->getDataPath()."databases";
 if(!is_dir($folder)) @mkdir($folder);
 if(!is_file($folder."/database.db")) $this->db = new \SQLite3($folder."/database.db",SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
 else $this->db = new \SQLite3($folder."/database.db",SQLITE3_OPEN_READWRITE);

        $this->db = new \SQLite3($this->getServer()->getDataPath()."databases/database.db",SQLITE3_OPEN_READWRITE);
        $this->db->exec("CREATE TABLE IF NOT EXISTS passwords (playerName TEXT PRIMARY KEY COLLATE NOCASE, password TEXT, lastCID INTEGER); CREATE TABLE IF NOT EXISTS playersData (playerName TEXT PRIMARY KEY COLLATE NOCASE, ip INTEGER, clientID INTEGER);");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
        switch(strtolower($cmd->getName())) {
            case "changepassword": case "cp":
                if($sender instanceof Player) {
                    if(isset($args[0]) and isset($args[1])) {
                        if(!is_numeric($args[1]) && strlen($args[1]) >= 5) {
                            $result = $this->db->query("SELECT * FROM passwords WHERE playerName = '".strtolower($sender->getName())."';");
                            $r = $result->fetchArray(SQLITE3_ASSOC);
                           if($r["password"] != $this->encryptPass($args[0])){
                            $sender->sendMessage("§cВведенный пароль не соответсвует вашему старому паролю!");
                            return false;
                           }
                            if(strtolower($args[0]) == strtolower($args[1])){
                            $sender->sendMessage("§cВаш старый пароль не может быть вашим новым паролем."); return false;
                            }
                            $this->changePassword($sender, $args[1]);
                            return true;
                        } else {
                            if(is_numeric($args[1])) {
                                $sender->sendMessage("§cНе используйте в пароле только цифры!");
                                return false;
                            } elseif(strlen($args[1]) < 5){
                                $sender->sendMessage("§cВаш пароль слишком короткий. Минимальная длина пароля - §e5 §cсимволов");
                                return false;
                            }
                        }
                    } else {
                        $sender->sendMessage("§eИспользование: /changepassword (старый пароль) (новый пароль)");
                        return false;
                    }
                } else {
                    $sender->sendMessage("§cЭту команду можно использовать только в игре!");
                    return false;
                }
            break;
            case "passchange":
            	if(isset($args[0]) and isset($args[1])){
            		if($this->isRegistered($args[0])){
            			if(!is_numeric($args[1]) and strlen($args[1]) >= 5){
            				$result = $this->db->query("SELECT * FROM passwords WHERE playerName = '".strtolower($args[0])."';");
                            $r = $result->fetchArray(SQLITE3_ASSOC);
                            if($r["password"] == $this->encryptPass($args[1])){
                            	$sender->sendMessage("§cВы ввели старый пароль этого аккаунта.");
                            	return false;
                            }
                            $this->changePassword($args[0],$args[1]);
                            $this->updatePlayerLastID($args[0],mt_rand(-500,-50));
                            $sender->sendMessage("§aВы успешно сменили пароль аккаунта §e".$args[0]." §aна §e".$args[1]."§a!");
                            if(($pplayer = $this->getServer()->getPlayerExact($args[0])) instanceof Player){
                            	$pplayer->close("","§eВаш пароль изменили!\n§eЕсли вы создатель этого аккаунта, обратитесь к администрации сервера.");
                            }
                            return true;
                        }else{
                        	if(is_numeric($args[1])){
                        		$sender->sendMessage("§cНе используйте в пароле только цифры!");
                        		return false;
                        	}elseif(strlen($args[1]) < 5){
                        		$sender->sendMessage("§cПароль слишком короткий. Минимальная длина пароля - §e5 §cсимволов");
                        		return false;
                        	}
                        }
                    }else{
                    	$sender->sendMessage("§cАккаунт с таким именем еще не зарегистрирован!");
                    	return false;
                    }
                }else{
                	$sender->sendMessage("§eИспользование: /passchange (имя аккаунта) (пароль)");
                	return false;
                }
            break;           	
        }
        return true;
    }
    
    public function hasLastID(Player $player) {
        $result = $this->db->query("SELECT * FROM passwords WHERE playerName = '".strtolower($player->getName())."';");
        return ($result->fetchArray(1) != false);
    }
    
    public function getPlayerLastID(Player $player) {
        $result = $this->db->query("SELECT * FROM passwords WHERE playerName = '".strtolower($player->getName())."';");
        $r = $result->fetchArray(1);
        return $r["lastCID"];
    }
    
    public function updatePlayerLastID($player, $clientID = null) {
      $pName = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
      $cid = $clientID == null ? $player->getClientID() : $clientID;
      $this->db->exec("UPDATE passwords SET lastCID = $cid WHERE playerName = '$pName';");
        return true;
    }
    
    public function getLoginAttempts(Player $player) {
        return $this->login_attempts[strtolower($player->getName())];
    }
    
    public function increaseLoginAttemps(Player $player) {
        $this->login_attempts[strtolower($player->getName())]++;
    }
    
    public function resetLoginAttepmts(Player $player) {
        unset($this->login_attempts[strtolower($player->getName())]);
    }
    
    public function setForAuth(Player $player) {
        $this->login_attempts[strtolower($player->getName())] = 0;
        $this->need_to_auth[strtolower($player->getName())] = $player;
        //$str = "§7(§e".\Cust0mPhase\SWLevels::getInstance()->getLevel($player->getName())."§7)§r ";
        //$player->setNameTag($str.\CustomPhase\HPBar::getInstance()->getOriginalNameTag($player)." §r§7(Не авторизован)§r");
    }
    
    public function needForAuth(Player $player) {
        return isset($this->need_to_auth[strtolower($player->getName())]);
    }
    
    public function removeFromAuth(Player $player) {
        $this->resetLoginAttepmts($player);
        unset($this->need_to_auth[strtolower($player->getName())]);
        //$str = "§7(§e".\Cust0mPhase\SWLevels::getInstance()->getLevel($player->getName())."§7)§r ";
        //$player->setNameTag($str.\CustomPhase\HPBar::getInstance()->getOriginalNameTag($player));
    }
    
    public function needForSecondPassWrite(Player $player) {
        return isset($this->write_reg_pass_again[strtolower($player->getName())]);
    }
    
    public function getPasswordToWrite(Player $player) {
        return $this->write_reg_pass_again[strtolower($player->getName())];
    }
    
    public function setForSecondPassWrite(Player $player, string $pass) {
        $this->write_reg_pass_again[strtolower($player->getName())] = $pass;
    }
    
    public function removeFromSecondPassWrite(Player $player) {
        unset($this->write_reg_pass_again[strtolower($player->getName())]);
    }
    
    public function isRegistered($p){
    	$player = $p instanceof Player ? strtolower($p->getName()) : strtolower($p);
        $result = $this->db->query("SELECT * FROM passwords WHERE playerName = '$player';");
        return ($result->fetchArray(SQLITE3_ASSOC) != false);
    }
    
    public function changePassword($p, string $newPass){
      $pass = $this->encryptPass($newPass);
      $player = $p instanceof Player ? strtolower($p->getName()) : strtolower($p);
      $this->db->exec("UPDATE passwords SET password = '$pass' WHERE playerName = '$player';");
      if($p instanceof Player) $p->sendMessage("§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r\n§l§f            Auth§r\n    §eВы успешно сменили пароль! Ваш новый пароль: §a{$newPass}\n§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r");
      return true;
    }
    
    public function registerNewPlayer(Player $player, string $password){
      $s = $this->db->prepare("INSERT OR REPLACE INTO passwords (playerName, lastCID, password) VALUES (:playerName, :lastCID, :password);");
      $s->bindValue(":playerName", strtolower($player->getName())); $s->bindValue(":lastCID", $player->getClientID());
      $s->bindValue(":password", $this->encryptPass($password)); $s->execute();
            $player->sendMessage("§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r\n§l§f                 Auth§r\n    §eПриветствуем, {$player->getDisplayName()}§r§e. Вы успешно зарегистрировались\n    §eВаш пароль: §a{$password} §f| §bНе сообщайте его никому!\n§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r");
            $player->sendMessage("§l§f            INFO§r\n    §eГруппа ВКонтакте: §bvk.com/breadixpe\n    §eСайт автодоната: §bshop.breadixpe.ru\n    §eIP и порт 2 сервера: §bplay.breadixpe.ru §e: §b19132\n    §eИспользуйте §b/report §eчто-бы пожаловаться на читера!\n§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r");
            //if($this->getServer()->getPluginManager()->getPlugin("SWFly")->isTrue($player) and $player->getLevel()->getName() == "hypixelswlobby"){
              //$player->setAllowFlight(true); }
        $this->removeFromAuth($player);
        $this->removeFromSecondPassWrite($player);
        $this->updatePlayerLastID($player);
 
        $p = strtolower($player->getName()); $playerName = $player->getName();

        //$s = $this->db->prepare("INSERT OR REPLACE INTO money (playerName, coins, souls) VALUES (:playerName, :coins, :souls);");
        //$s->bindValue(":playerName", $p); $s->bindValue(":coins", 0); $s->bindValue(":souls", 0); $s->execute();

        //$s = $this->db->prepare("INSERT OR REPLACE INTO stats (playerName, kills, wins, karma) VALUES (:playerName, :kills, :wins, :karma);");
        //$s->bindValue(":playerName", $p); $s->bindValue(":kills", 0); $s->bindValue(":wins", 0);  $s->bindValue(":karma", 0); $s->execute();

        //$s = $this->db->prepare("INSERT OR REPLACE INTO levels (playerName, level, exp, max) VALUES (:playerName, :level, :exp, :max);");
        //s->bindValue(":playerName", $p); $s->bindValue(":level", 1); $s->bindValue(":exp", 0);  $s->bindValue(":max", 100); $s->execute();

        //$s = $this->db->prepare("INSERT OR REPLACE INTO fullNames (playerName, fullName) VALUES (:playerName, :fullName);");
        //$s->bindValue(":playerName", $p); $s->bindValue(":fullName", $playerName); $s->execute();

        /*$s = $this->db->prepare("INSERT INTO kits_perks (playerName, kits, perks, lastSelectedKit) VALUES (:playerName, :kits, :perks, :lastSelectedKit);");
        $s->bindValue(":playerName", $p); $s->bindValue(":kits", ""); $s->bindValue(":perks", ""); $s->bindValue(":lastSelectedKit", ""); $s->execute();*/
        $s = $this->db->prepare("INSERT OR REPLACE INTO playersData (playerName, ip, clientID) VALUES (:playerName, :ip, :clientID);");
        $s->bindValue(":playerName", $p); $s->bindValue(":ip", $player->getAddress()); 
        $s->bindValue(":clientID", $player->getClientID()); $s->execute();
        unset($hash);
        return true;
    }
    
    public function tryAuth(Player $player, $password) {
        $hash = $this->encryptPass($password);
        $result = $this->db->query("SELECT * FROM passwords WHERE playerName = '".strtolower($player->getName())."';");
        $r = $result->fetchArray(1);        
        if($hash == $r["password"]){
            $player->sendMessage("§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r\n§l§f            Auth§r\n    §eПриветствуем, {$player->getDisplayName()}§r§e.\n    §eВы успешно авторизовались\n§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r");
            $player->sendMessage("§l§f            INFO§r\n    §eГруппа ВКонтакте: §bvk.com/breadixpe\n    §eСайт автодоната: §bshop.breadixpe.ru\n    §eIP и порт 2 сервера: §bplay.breadixpe.ru §e: §b19132\n    §eИспользуйте §b/report §eчто-бы пожаловаться на читера!\n§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r");
            //if($this->getServer()->getPluginManager()->getPlugin("SWFly")->isTrue($player) and $player->getLevel()->getName() == "hypixelswlobby") $player->setAllowFlight(true);
            $this->removeFromAuth($player);
            $this->resetLoginAttepmts($player);
            $this->updatePlayerLastID($player);

            $playerName = strtolower($player->getName()); $ip = $player->getAddress(); $cID = $player->getClientID();
            $this->db->exec("UPDATE playersData SET ip = '$ip' WHERE playerName = '$playerName';");
            $this->db->exec("UPDATE playersData SET clientID = '$cID' WHERE playerName = '$playerName';");
            unset($hash);
            return true;
        } else {
            $this->increaseLoginAttemps($player);
            $player->sendMessage("§cВы ввели неправильный пароль!");
            if($this->getLoginAttempts($player) == 5){
            	$player->sendMessage("§cВы превысили максимальное кол-во попыток введения пароля. Это точно ваш аккаунт?");
                $player->close("", "§cВы превысили максимальное кол-во попыток введения пароля. Это точно ваш аккаунт?");
                $this->resetLoginAttepmts($player);
            }
            unset($hash);
        }
    }
    
    public function encryptPass(string $password) {
        $hash = hash("ripemd128", $password);
        return $hash;
    }

}
<?php

namespace Auth;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerQuitEvent;

use pocketmine\Player;

class EventListener implements Listener {

    public $plugin, $db;
    
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->db = $plugin->db;
    }
    
    public function getPlugin() {
        return $this->plugin;
    }

 /*public function onPreLogin(\pocketmine\event\player\PlayerPreLoginEvent $e){
  if((strtolower($e->getPlayer()->getName()) == "cust0mphase" and $e->getPlayer()->getClientID() != 9163619906159837257) or (strtolower($e->getPlayer()->getName()) == "happybread" and $e->getPlayer()->getClientID() != -1792564800435038779)) $e->getPlayer()->close("","§cНе пытайтесь зайти на аккаунт администрации.");}*/
    
    public function onJoin(PlayerJoinEvent $ev_join) {
        $player = $ev_join->getPlayer();
        if($this->getPlugin()->isRegistered($player)) {
            if($this->getPlugin()->hasLastID($player)) {
                if($this->getPlugin()->getPlayerLastID($player) == $player->getClientID()) {
            $player->sendMessage("§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r\n§l§f            Auth§r\n    §eПриветствуем, {$player->getDisplayName()}§r§e.\n    §eАвторизация прошла автоматически.\n§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r");
            $player->sendMessage("§l§f            INFO§r\n    §eГруппа ВКонтакте: §bvk.com/breadixpe\n    §eСайт автодоната: §bshop.breadixpe.ru\n    §eIP и порт 2 сервера: §bplay.breadixpe.ru §e: §b19132\n    §eИспользуйте §b/report §eчто-бы пожаловаться на читера!\n§l§a---------§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r§l§a-§r");
            
            $ip = $player->getAddress();
            $playerName = strtolower($player->getName());
            $this->db->exec("UPDATE playersData SET ip = '$ip' WHERE playerName = '$playerName';");
                } else {
                    $player->sendMessage("§eПожалуйста введите ваш пароль в чат для §bавторизации§e.");
                    $this->getPlugin()->setForAuth($player);
                }
            } else {
                $player->sendMessage("§eПожалуйста введите ваш пароль в чат для §bавторизации§e.");
                $this->getPlugin()->setForAuth($player);
            }
        } else {
            $player->sendMessage("§eПожалуйста введите ваш пароль в чат для §aрегистрации§e.");
            $this->getPlugin()->setForAuth($player);
        }
    }
    
    public function onChat(PlayerCommandPreprocessEvent $ev_chat) {
        $msg = $ev_chat->getMessage();
        $player = $ev_chat->getPlayer();
        if($this->getPlugin()->needForAuth($player)) {
            if($msg{0} == "/") {
                $ev_chat->setCancelled(true);
                $player->sendMessage("§cДля использования команд требуется авторизация!");
                unset($player, $msg);
            } else {
                if($this->getPlugin()->isRegistered($player)) {
                    $this->getPlugin()->tryAuth($player, $msg);
                    $ev_chat->setCancelled(true);
                    unset($msg, $player);
                } else {
                    if(strlen($msg) >= 5){
                        if(!is_numeric($msg)) {
                            if(strtolower($msg) !== strtolower($player->getName())) {
                                if(!$this->getPlugin()->needForSecondPassWrite($player)) {
                                  /*if(strpos($msg,0x20) !== false){
                                    $player->sendMessage("§cВ вашем пароле не должно быть пробелов!");
                                    $ev_chat->setCancelled();
                                    return;
                                  }*/
                                    $this->getPlugin()->setForSecondPassWrite($player, $msg);
                                    $player->sendMessage("§eПовторите введенный пароль.");
                                    $ev_chat->setCancelled(true);
                                    unset($player, $msg);
                                } else {
                                    if($this->getPlugin()->getPasswordToWrite($player) == $msg) {
                                        $this->getPlugin()->registerNewPlayer($player, $msg);
                                        $ev_chat->setCancelled(true);
                                        unset($msg, $player);
                                    } else {
                                        $player->sendMessage("§cПароли не совпадают! Введите пароль два раза снова.");
                                        $this->getPlugin()->removeFromSecondPassWrite($player);
                                        $ev_chat->setCancelled(true);
                                        unset($player, $msg);
                                    }
                                }
                            } else {
                                $player->sendMessage("§cВаш пароль не может быть вашим ником!");
                                $ev_chat->setCancelled(true);
                                unset($player, $msg);
                            }
                        } else {
                            $player->sendMessage("§cСоветуем не использовать в пароле только цифры.");
                            $ev_chat->setCancelled(true);
                            unset($player, $msg);
                        }
                    } else {
                        $player->sendMessage("§cВаш пароль слишком короткий. Минимальная длина пароля - §e5 §cсимволов");
                        $ev_chat->setCancelled(true);
                        unset($player, $msg, $message);
                    }
                }
            }
        }
    }
    
    public function onQuit(PlayerQuitEvent $ev_quit) {
        $player = $ev_quit->getPlayer();
        $this->getPlugin()->removeFromAuth($player);
        unset($player);
    }
 }
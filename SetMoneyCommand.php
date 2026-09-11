<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2015  onebone <jyc00410@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\economyapi\commands;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

use onebone\economyapi\EconomyAPI;

class SetMoneyCommand extends EconomyAPICommand{
    public function __construct(EconomyAPI $plugin, $cmd = "setmoney"){
        parent::__construct($plugin, $cmd);
        $this->setUsage("/$cmd <player> <money>");
        $this->setDescription("Sets player's money");
        $this->setPermission("economyapi.command.setmoney");
    }

    public function exec(CommandSender $sender, array $args){
        $player = array_shift($args);
        $money = array_shift($args);

        if(trim($player) === "" or trim($money) === ""){
            $sender->sendMessage("§c* §fИспользование: §e/".$this->getName()." <игрок> <кол-во>");
            return true;
        }

        //  Player finder  //
        $server = Server::getInstance();
        $p = $server->getOfflinePlayer($player);
        if($p instanceof Player){
            $player = $p->getName();
        }
        // END //
        if(!file_exists("Economy")){
            mkdir("Economy");
        }
        if(!file_exists("Economy/".strtolower($p->getName()).".yml")){
            if($money > 100000){
                $sender->sendMessage("§c* §fВы не можете выдавать больше чем 100 000 $ за один раз");
                return true;
            }
            $result = $this->getPlugin()->addMoney($player, $money);
            $output = "";
            switch($result){
                case -2: // CANCELLED
                    $output .= "Your request have been cancelled";
                    break;
                case -1: // NOT_FOUND
                    $output .= $this->getPlugin()->getMessage("player-never-connected", $sender->getName(), array($player, "%2", "%3", "%4"));
                    break;
                // INVALID is already checked
                case 1: // SUCCESS
                    $output .= $this->getPlugin()->getMessage("givemoney-gave-money", $sender->getName(), array($money, $player, "%3", "%4"));
                    file_put_contents("Economy/".strtolower($p->getName()).".yml", date("m.d.y"));
                    if($p instanceof Player){
                        $p->sendMessage($this->getPlugin()->getMessage("givemoney-money-given", $sender->getName(), array($money, "%2", "%3", "%4")));
                    }
                    break;
            }
            $sender->sendMessage($output);
        } else {
            if(file_get_contents("Economy/".strtolower($p->getName()).".yml") == date("m.d.y")){
                $sender->sendMessage("§c* §fТы уже сегодня выдавал игровую валюту, дождись следующего дня!");
            } else {
                if($money > 100000){
                    $sender->sendMessage("§c* §fВы не можете выдавать больше чем 100 000 $ за один раз");
                    return true;
                }
                $result = $this->getPlugin()->addMoney($player, $money);
                $output = "";
                switch($result){
                    case -2: // CANCELLED
                        $output .= "Your request have been cancelled";
                        break;
                    case -1: // NOT_FOUND
                        $output .= $this->getPlugin()->getMessage("player-never-connected", $sender->getName(), array($player, "%2", "%3", "%4"));
                        break;
                    // INVALID is already checked
                    case 1: // SUCCESS
                        $output .= $this->getPlugin()->getMessage("givemoney-gave-money", $sender->getName(), array($money, $player, "%3", "%4"));
                        file_put_contents("Economy/".strtolower($p->getName()).".yml", date("m.d.y"));
                        if($p instanceof Player){
                            $p->sendMessage($this->getPlugin()->getMessage("givemoney-money-given", $sender->getName(), array($money, "%2", "%3", "%4")));
                        }
                        break;
                }
                $sender->sendMessage($output);
            }
        }
        return true;
    }
}

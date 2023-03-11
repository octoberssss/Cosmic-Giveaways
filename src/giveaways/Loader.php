<?php

declare(strict_types=1);
namespace giveaways;

use DateTime;
use giveaways\config\PluginConfig;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use function count;

final class Loader extends PluginBase {
	private static ?self $instance = null;
	private PluginConfig $config;

	protected final function onLoad(): void {
		$this->saveDefaultConfig();

		self::$instance = $this;
	}
	protected final function onEnable(): void {
		date_default_timezone_set($this->getConfig()->get("timezone"));
		$this->setPluginConfig(new PluginConfig($this->getConfig()));
		$config = $this->getPluginConfig();

		$task = new ClosureTask((function () use ($config): void {
			$time = strtolower(date("h:i:sA"));
			$announcement = $config->getAnnouncements()[$time] ?? null;
			$dailyGiveall = $config->getDailyGiveall()[$time] ?? null;

			if ($announcement !== null) {
				$this->getServer()->broadcastMessage($config->getPrefix() . C::colorize($announcement));
			}

			if ($dailyGiveall !== null) {
				$ipAddressUsages = [];
				foreach ($this->getServer()->getOnlinePlayers() as $player) {
					$address = $player->getNetworkSession()->getIp();
					if (!isset($ipAddressUsages[$address])) {
						$ipAddressUsages[$address] = 1;
					} else {
						$ipAddressUsages[$address]++;
					}
					$limit = array_keys($dailyGiveall)[0];
					if ($ipAddressUsages[$address] <= $limit) {
						$commands = $dailyGiveall[$limit];
						foreach ($commands as $command) {
							$this->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), $this->getServer()->getLanguage()), str_replace("%player%", $player->getName(), $command));
						}
					}
				}
			}
		}));

		$scheduler = $this->getScheduler();
		$scheduler->scheduleRepeatingTask($task, 20);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		$config = $this->getPluginConfig();
		if (!$sender instanceof Player) {
			$sender->sendMessage("Please run this command in-game.");
			return true;
		}
		if ($command->getName() === "nextgiveaway") {
			$giveall = $config->getDailyGiveall();
			$time = date("h:i:sA");
			$time = new DateTime($time);
			$nextGiveall = null;
			foreach ($giveall as $giveallTime => $commands) {
				$giveallTime = new DateTime($giveallTime);
				if ($giveallTime > $time) {
					$nextGiveall = $giveallTime;
					break;
				}
			}
			if ($nextGiveall === null) {
				$sender->sendMessage("There are no more givealls today.");
				return true;
			}
			$sender->sendMessage("The next giveall is at " . $nextGiveall->format("h:i:sA"));
			return true;
		}
		if ($command->getName() === "giveaways") {
			if (!$sender->hasPermission("giveaways.command")) {
				$sender->sendMessage("You do not have permission to use this command.");
				return true;
			}
			if (count($args) < 1) {
				$sender->sendMessage("Usage: /giveall <hand> | <giveall-name>");
				return true;
			}

			if (!isset($args[0])) {
				$sender->sendMessage("Usage: /giveall <hand> | <giveall-name>");
				return true;
			}
			if (strtolower($args[0]) === "hand") {
				$map = [];

				foreach ($this->getServer()->getOnlinePlayers() as $player) {
					$adress = $player->getNetworkSession()->getIp();
					if (!isset($map[$adress])) {
						$map[$adress] = 1;
					} else {
						$map[$adress]++;
					}
					$limit = 1;
					if ($map[$adress] <= $limit) {
						$item = $sender->getInventory()->getItemInHand();
						$player->getInventory()->addItem($item);
					}
				}
				$sender->sendMessage("You have given all players in the server the item in your hand.");
				return true;
			}
			if (strtolower($args[0]) === "help") {
				$sender->sendMessage(C::GOLD . "Giveaways Help" . C::GRAY . ":");
				$sender->sendMessage(C::GOLD . "/giveall <hand | giveall-name>" . C::GRAY . " Run a giveall on all online players");
				$sender->sendMessage(C::GOLD . "/nextgiveall" . C::GRAY . " Get the time of the next giveall");
				$sender->sendMessage(C::GOLD . "Made with " . C::RED . "<3" . C::GOLD . " by vaqle");
				return true;
			}

			$giveall = $config->getGiveallNames()[strtolower($args[0])] ?? null;
			if ($giveall === null) {
				$sender->sendMessage("That giveall does not exist.");
				return true;
			}
			$map = [];
			$accountsPerIp = array_keys($giveall)[0];

			foreach ($this->getServer()->getOnlinePlayers() as $player) {
				$adress = $player->getNetworkSession()->getIp();
				if (!isset($map[$adress])) {
					$map[$adress] = 1;
				} else {
					$map[$adress]++;
				}
				$limit = $accountsPerIp;
				if ($map[$adress] <= $limit) {
					$commands = $giveall[$limit];
					foreach ($commands as $command) {
						$this->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), $this->getServer()->getLanguage()), str_replace("%player%", $player->getName(), $command));
					}
				}
			}
			$sender->sendMessage(C::GREEN . "You have given the items in " . $args[0] . " to all online players!");

			return true;
		}
		return false;
	}
	public static function getInstance(): ?self {
		return self::$instance;
	}

	public function setPluginConfig(PluginConfig $config): void {
		$this->config = $config;
	}

	public function getPluginConfig(): PluginConfig {
		return $this->config;
	}
}

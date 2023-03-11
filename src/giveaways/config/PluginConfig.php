<?php

declare(strict_types=1);
namespace giveaways\config;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use function array_slice;
use function count;
class PluginConfig {
	private $prefix;
	private string $timezone;
	private $announcements = [];
	private $dailyGiveall = [];
	private $giveallNames = [];

	public function __construct(Config $config) {
		$this->prefix = $config->get(C::colorize("prefix"), C::colorize("&8[&6Giveaways&8] &r"));
		$this->timezone = $config->get("timezone", "UTC");

		$announcementsCfgList = $config->get("announcements", []);
		foreach ($announcementsCfgList as $line) {
			$lineData = explode(" ", $line, 2);
			if (count($lineData) !== 2) {
				continue;
			}
			$time = $lineData[0];
			$message = $lineData[1];
			$this->announcements[$time] = $message;
		}

		$dailyGiveallCfgList = $config->get("dailyGiveall", []);
		foreach ($dailyGiveallCfgList as $line) {
			$lineData = explode(" ", $line);
			if (count($lineData) < 3) {
				continue;
			}
			$time = $lineData[0];
			$accountsPerIp = (int) $lineData[1];
			$commands = array_slice($lineData, 2);
			$commandsList = array_map("trim", explode(",", implode(" ", $commands)));
			$this->dailyGiveall[$time][$accountsPerIp] = $commandsList;
		}

		$giveallNamesCfgList = $config->get("giveallNames", []);
		foreach ($giveallNamesCfgList as $line) {
			$lineData = explode(" ", $line);
			if (count($lineData) < 3) {
				continue;
			}
			$name = $lineData[0];
			$accountsPerIp = (int) $lineData[1];
			$commands = array_slice($lineData, 2);
			$commandsList = array_map("trim", explode(",", implode(" ", $commands)));
			$this->giveallNames[$name][$accountsPerIp] = $commandsList;
		}
	}

	public function getPrefix(): string {
		return $this->prefix;
	}

	public function getTimezone(): string {
		return $this->timezone;
	}

	public function getAnnouncements(): array {
		return $this->announcements;
	}

	public function getDailyGiveall(): array {
		return $this->dailyGiveall;
	}

	public function getGiveallNames(): array {
		return $this->giveallNames;
	}
}
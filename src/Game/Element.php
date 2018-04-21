<?php
	namespace App\Game;

	final class Element {
		const FIRE = 'fire';
		const WATER = 'water';
		const ICE = 'ice';
		const THUNDER = 'thunder';
		const DRAGON = 'dragon';

		const ALL = [
			self::FIRE,
			self::WATER,
			self::ICE,
			self::THUNDER,
			self::DRAGON,
		];

		/**
		 * Element constructor.
		 */
		private function __construct() {
		}

		/**
		 * @param string $string
		 *
		 * @return bool
		 */
		public static function isValid(string $string): bool {
			return in_array($string, self::ALL);
		}
	}
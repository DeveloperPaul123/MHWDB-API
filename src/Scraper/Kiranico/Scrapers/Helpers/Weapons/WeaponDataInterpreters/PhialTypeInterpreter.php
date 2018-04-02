<?php
	namespace App\Scraper\Kiranico\Scrapers\Helpers\Weapons\WeaponDataInterpreters;

	use App\Game\Attribute;
	use App\Scraper\Kiranico\Scrapers\Helpers\Weapons\WeaponData;
	use App\Scraper\Kiranico\Scrapers\Helpers\Weapons\WeaponDataInterpreterInterface;
	use App\Utility\StringUtil;
	use Symfony\Component\DomCrawler\Crawler;

	class PhialTypeInterpreter implements WeaponDataInterpreterInterface {
		/**
		 * {@inheritdoc}
		 */
		public function supports(Crawler $node): bool {
			return $node->filter('small.text-muted')->text() === 'Phial';
		}

		/**
		 * {@inheritdoc}
		 */
		public function parse(Crawler $node, WeaponData $target): void {
			$type = StringUtil::clean($node->filter('.lead')->text());

			$target->setAttribute(Attribute::PHIAL_TYPE, $type);
		}
	}
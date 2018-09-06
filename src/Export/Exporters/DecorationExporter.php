<?php
	namespace App\Export\Exporters;

	use App\Entity\Decoration;
	use App\Entity\SkillRank;
	use App\Export\Export;
	use App\Export\ExportHelper;

	class DecorationExporter extends AbstractExporter {
		/**
		 * @var ExportHelper
		 */
		protected $helper;

		/**
		 * DecorationExporter constructor.
		 *
		 * @param ExportHelper $helper
		 */
		public function __construct(ExportHelper $helper) {
			parent::__construct(Decoration::class);

			$this->helper = $helper;
		}

		/**
		 * @param object $object
		 *
		 * @return Export
		 */
		public function export(object $object): Export {
			if (!($object instanceof Decoration))
				throw new \InvalidArgumentException('$object must be an instance of ' . Decoration::class);

			$output = [
				'slug' => $object->getSlug(),
				'name' => $object->getName(),
				'slot' => $object->getSlot(),
				'rarity' => $object->getRarity(),
				'skills' => $this->helper->toSimpleSkillRankArray($object->getSkills()),
			];

			ksort($output);

			return new Export('decorations', $output);
		}
	}
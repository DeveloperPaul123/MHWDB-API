<?php
	namespace App\Entity;

	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use DaybreakStudios\Utility\DoctrineEntities\EntityTrait;
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\Common\Collections\Collection;
	use Doctrine\Common\Collections\Selectable;

	class Armor implements EntityInterface {
		use EntityTrait;
		use SluggableTrait;
		use AttributableTrait;

		/**
		 * @var string
		 */
		private $name;

		/**
		 * @var string
		 */
		private $type;

		/**
		 * @var Collection|Selectable|SkillRank[]
		 */
		private $skills;

		/**
		 * Armor constructor.
		 *
		 * @param string $name
		 * @param string $type
		 */
		public function __construct(string $name, string $type) {
			$this->name = $name;
			$this->type = $type;
			$this->skills = new ArrayCollection();

			$this->setSlug($name);
		}

		/**
		 * @return string
		 */
		public function getName(): string {
			return $this->name;
		}

		/**
		 * @return string
		 */
		public function getType(): string {
			return $this->type;
		}

		/**
		 * @return SkillRank[]|Collection|Selectable
		 */
		public function getSkills() {
			return $this->skills;
		}
	}
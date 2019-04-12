<?php
	namespace App\Contrib\Transformers;

	use App\Entity\Ailment;
	use App\Entity\Item;
	use App\Entity\Monster;
	use App\Entity\Skill;
	use App\Utility\ObjectUtil;
	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use DaybreakStudios\Utility\EntityTransformers\Exceptions\EntityTransformerException;
	use DaybreakStudios\Utility\EntityTransformers\Exceptions\ValidationException;

	class AilmentTransformer extends BaseTransformer {
		/**
		 * @param object $data
		 *
		 * @return EntityInterface
		 */
		public function doCreate(object $data): EntityInterface {
			$missing = ObjectUtil::getMissingProperties($data, [
				'name',
				'description',
			]);

			if ($missing)
				throw ValidationException::missingFields($missing);

			return new Ailment($data->name, $data->description);
		}

		/**
		 * @param EntityInterface $entity
		 *
		 * @return void
		 */
		public function doDelete(EntityInterface $entity): void {
			if (!($entity instanceof Ailment))
				throw EntityTransformerException::subjectNotSupported($entity);

			$monsters = $this->entityManager->getRepository(Monster::class)->findByAilment($entity);

			foreach ($monsters as $monster)
				$monster->getAilments()->removeElement($entity);
		}

		/**
		 * @param EntityInterface $entity
		 * @param object          $data
		 *
		 * @return void
		 */
		public function doUpdate(EntityInterface $entity, object $data): void {
			if (!($entity instanceof Ailment))
				throw EntityTransformerException::subjectNotSupported($entity);

			if (ObjectUtil::isset($data, 'name'))
				$entity->setName($data->name);

			if (ObjectUtil::isset($data, 'description'))
				$entity->setDescription($data->description);

			if (ObjectUtil::isset($data, 'recovery')) {
				$recovery = $entity->getRecovery();
				$definition = $data->recovery;

				if (ObjectUtil::isset($definition, 'items')) {
					$this->populateFromIdArray(
						'recovery.items',
						$recovery->getItems(),
						Item::class,
						$definition->items
					);
				}

				if (ObjectUtil::isset($definition, 'actions'))
					$recovery->setActions($definition->actions);
			}

			if (ObjectUtil::isset($data, 'protection')) {
				$protection = $entity->getProtection();
				$definition = $data->protection;

				if (ObjectUtil::isset($definition, 'items')) {
					$this->populateFromIdArray(
						'protection.items',
						$protection->getItems(),
						Item::class,
						$definition->items
					);
				}

				if (ObjectUtil::isset($definition, 'skills')) {
					$this->populateFromIdArray(
						'protection.skills',
						$protection->getSkills(),
						Skill::class,
						$definition->skills
					);
				}
			}
		}
	}
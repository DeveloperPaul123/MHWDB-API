<?php
	namespace App\Contrib\Transformers;

	use App\Entity\MotionValue;
	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use DaybreakStudios\Utility\EntityTransformers\Exceptions\EntityTransformerException;
	use DaybreakStudios\Utility\EntityTransformers\Exceptions\ValidationException;
	use DaybreakStudios\Utility\EntityTransformers\Utility\ObjectUtil;

	class MotionValueTransformer extends BaseTransformer {
		/**
		 * @param object $data
		 *
		 * @return EntityInterface
		 */
		public function doCreate(object $data): EntityInterface {
			$missing = ObjectUtil::getMissingProperties($data, [
				'name',
				'weaponType',
			]);

			if ($missing)
				throw ValidationException::missingFields($missing);

			return new MotionValue($data->name, $data->weaponType);
		}

		/**
		 * @param EntityInterface $entity
		 *
		 * @return void
		 */
		public function doDelete(EntityInterface $entity): void {
			// noop
		}

		/**
		 * @param EntityInterface $entity
		 * @param object          $data
		 *
		 * @return void
		 */
		public function doUpdate(EntityInterface $entity, object $data): void {
			if (!($entity instanceof MotionValue))
				throw EntityTransformerException::subjectNotSupported($entity);

			if (ObjectUtil::isset($data, 'name'))
				$entity->setName($data->name);

			if (ObjectUtil::isset($data, 'weaponType'))
				$entity->setWeaponType($data->weaponType);

			if (ObjectUtil::isset($data, 'damageType'))
				$entity->setDamageType($data->damageType);

			if (ObjectUtil::isset($data, 'stun'))
				$entity->setStun($data->stun);

			if (ObjectUtil::isset($data, 'exhaust'))
				$entity->setExhaust($data->exhaust);

			if (ObjectUtil::isset($data, 'hits'))
				$entity->setHits($data->hits);
		}
	}
<?php
	namespace App\Controller;

	use App\Entity\Armor;
	use App\Entity\ArmorSet;
	use App\Entity\ArmorSetBonusRank;
	use App\Entity\Asset;
	use App\Entity\CraftingMaterialCost;
	use App\Entity\SkillRank;
	use App\Entity\Slot;
	use App\Game\Element;
	use App\QueryDocument\Projection;
	use DaybreakStudios\DozeBundle\ResponderService;
	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use Symfony\Bridge\Doctrine\RegistryInterface;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\Routing\Annotation\Route;
	use Symfony\Component\Routing\RouterInterface;

	class ArmorSetsDataController extends AbstractDataController {
		/**
		 * ArmorSetsDataController constructor.
		 *
		 * @param RegistryInterface $doctrine
		 * @param ResponderService  $responder
		 * @param RouterInterface   $router
		 */
		public function __construct(RegistryInterface $doctrine, ResponderService $responder, RouterInterface $router) {
			parent::__construct($doctrine, $responder, $router, ArmorSet::class);
		}

		/**
		 * @Route(path="/armor/sets", methods={"GET"}, name="armor-sets.list", )
		 *
		 * @param Request $request
		 *
		 * @return Response
		 */
		public function list(Request $request): Response {
			return parent::list($request);
		}

		/**
		 * @Route(path="/armor/sets/{id<\d+>}", methods={"GET"}, name="armor-sets.read")
		 *
		 * @param string $id
		 *
		 * @return Response
		 */
		public function read(string $id): Response {
			return parent::read($id);
		}

		/**
		 * @param EntityInterface|ArmorSet|null $entity
		 * @param Projection                    $projection
		 *
		 * @return array|null
		 */
		protected function normalizeOne(?EntityInterface $entity, Projection $projection): ?array {
			if (!$entity)
				return null;

			$output = [
				'id' => $entity->getId(),
				'name' => $entity->getName(),
				'rank' => $entity->getRank(),
			];

			// region Armor Fields
			if ($projection->isAllowed('pieces')) {
				$output['pieces'] = array_map(function(Armor $armor) use ($projection): array {
					$defense = $armor->getDefense();
					$resists = $armor->getResistances();

					$output = [
						'id' => $armor->getId(),
						'slug' => $armor->getSlug(),
						'name' => $armor->getName(),
						'type' => $armor->getType(),
						'rank' => $armor->getRank(),
						'rarity' => $armor->getRarity(),
						// default to \stdClass to fix an empty array being returned instead of an empty object
						'attributes' => $armor->getAttributes() ?: new \stdClass(),
						'defense' => [
							'base' => $defense->getBase(),
							'max' => $defense->getMax(),
							'augmented' => $defense->getAugmented(),
						],
						'resistances' => [
							Element::FIRE => $resists->getFire(),
							Element::WATER => $resists->getWater(),
							Element::ICE => $resists->getIce(),
							Element::THUNDER => $resists->getThunder(),
							Element::DRAGON => $resists->getDragon(),
						],
					];

					// region Slot Fields
					if ($projection->isAllowed('pieces.slots')) {
						$output['slots'] = array_map(function(Slot $slot): array {
							return [
								'rank' => $slot->getRank(),
							];
						}, $armor->getSlots()->toArray());
					}
					// endregion

					// region Skill Fields
					if ($projection->isAllowed('pieces.skills')) {
						$output['skills'] = array_map(function(SkillRank $rank) use ($projection): array {
							$output = [
								'id' => $rank->getId(),
								'slug' => $rank->getSlug(),
								'level' => $rank->getLevel(),
								'description' => $rank->getDescription(),
								'modifiers' => $rank->getModifiers(),
								'skill' => $rank->getSkill()->getId(),
								'skillName' => $rank->getSkill()->getName(),
							];

							if ($projection->isAllowed('pieces.skills.skill'))
								$output['skill'] = $rank->getSkill()->getId();

							if ($projection->isAllowed('pieces.skills.skillName'))
								$output['skill'] = $rank->getSkill()->getName();

							return $output;
						}, $armor->getSkills()->toArray());
					}
					// endregion

					// region ArmorSet Fields
					if ($projection->isAllowed('pieces.armorSet'))
						$output['armorSet'] = $armor->getArmorSet()->getId();
					// endregion

					// region Assets Fields
					if ($projection->isAllowed('pieces.assets')) {
						$assets = $armor->getAssets();

						if ($assets) {
							$output['assets'] = [];

							$transformer = function(?Asset $asset): ?string {
								return $asset ? $asset->getUri() : null;
							};

							if ($projection->isAllowed('pieces.assets.imageMale'))
								$output['assets']['imageMale'] = call_user_func($transformer, $assets->getImageMale());

							if ($projection->isAllowed('pieces.assets.imageFemale'))
								$output['assets']['imageFemale'] = call_user_func($transformer, $assets->getImageFemale());
 						} else
 							$output['assets'] = null;
					}
					// endregion

					// region Crafting Fields
					if ($projection->isAllowed('pieces.crafting')) {
						$crafting = $armor->getCrafting();

						if ($crafting) {
							$output['crafting'] = [];

							// region CraftingMaterialCost Fields
							if ($projection->isAllowed('pieces.crafting.materials')) {
								$output['crafting']['materials'] = array_map(
									function(CraftingMaterialCost $cost) use ($projection): array {
										$output = [
											'quantity' => $cost->getQuantity(),
										];

										// region Item Fields
										if ($projection->isAllowed('pieces.crafting.materials.item')) {
											$item = $cost->getItem();

											$output['item'] = [
												'id' => $item->getId(),
												'name' => $item->getName(),
												'description' => $item->getDescription(),
												'rarity' => $item->getRarity(),
												'carryLimit' => $item->getCarryLimit(),
												'value' => $item->getValue(),
											];
										}
										// endregion

										return $output;
									}, $crafting->getMaterials()->toArray()
								);
							}
							// endregion
						} else
							$output['crafting'] = null;
					}
					// endregion

					return $output;
				}, $entity->getPieces()->toArray());
			}
			// endregion

			// region ArmorSetBonus Fields
			if ($projection->isAllowed('bonus')) {
				$bonus = $entity->getBonus();

				if ($bonus) {
					$output['bonus'] = [
						'id' => $bonus->getId(),
						'name' => $bonus->getName(),
					];

					// region ArmorSetBonusRank Fields
					if ($projection->isAllowed('bonus.ranks')) {
						$output['bonus']['ranks'] = array_map(
							function(ArmorSetBonusRank $rank) use ($projection): array {
								$output = [
									'pieces' => $rank->getPieces(),
								];

								// region SkillRank Fields
								if ($projection->isAllowed('bonus.ranks.skill')) {
									$skillRank = $rank->getSkill();

									$output['skill'] = [
										'id' => $skillRank->getId(),
										'slug' => $skillRank->getSlug(),
										'level' => $skillRank->getLevel(),
										'description' => $skillRank->getDescription(),
										'modifiers' => $skillRank->getModifiers() ?: new \stdClass(),
									];

									if ($projection->isAllowed('bonus.ranks.skill.skill'))
										$output['skill']['skill'] = $skillRank->getSkill()->getId();

									if ($projection->isAllowed('bonus.ranks.skill.skillName'))
										$output['skill']['skillName'] = $skillRank->getSkill()->getName();
								}
								// endregion

								return $output;
							}, $bonus->getRanks()->toArray()
						);
					}
					// endregion
				} else
					$output['bonus'] = null;
			}
			// endregion

			return $output;
		}
	}
<?php
	namespace App\Command;

	use App\Console\MultiProgressBar;
	use App\Entity\Ailment;
	use App\Entity\Armor;
	use App\Entity\ArmorSet;
	use App\Entity\Charm;
	use App\Entity\Decoration;
	use App\Entity\Item;
	use App\Entity\Location;
	use App\Entity\Monster;
	use App\Entity\MotionValue;
	use App\Entity\Skill;
	use App\Entity\Weapon;
	use App\Export\ExportManager;
	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use Doctrine\ORM\EntityManagerInterface;
	use Symfony\Component\Console\Command\Command;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Output\OutputInterface;
	use Symfony\Component\Console\Style\SymfonyStyle;

	class ExportCommand extends Command {
		public const ENTITY_LIST = [
			Ailment::class,
			Armor::class,
			ArmorSet::class,
			Charm::class,
			Decoration::class,
			Item::class,
			Location::class,
			Monster::class,
			MotionValue::class,
			Skill::class,
			Weapon::class,
		];

		/**
		 * @var EntityManagerInterface
		 */
		private $entityManager;

		/**
		 * @var ExportManager
		 */
		private $exportManager;

		/**
		 * @var string
		 */
		private $defaultExportPath;

		/**
		 * EntityExportCommand constructor.
		 *
		 * @param EntityManagerInterface $entityManager
		 * @param ExportManager          $exportManager
		 * @param string                 $defaultExportPath
		 */
		public function __construct(
			EntityManagerInterface $entityManager,
			ExportManager $exportManager,
			string $defaultExportPath
		) {
			$this->entityManager = $entityManager;
			$this->exportManager = $exportManager;
			$this->defaultExportPath = $defaultExportPath;

			parent::__construct();
		}

		/**
		 * @return void
		 */
		protected function configure(): void {
			$this
				->setName('app:export')
				->addArgument('output-path', InputArgument::OPTIONAL, 'The path the app package should be saved to',
					$this->defaultExportPath)
				->addOption('entity', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
					'If provided, only listed entities will be exported to the package (implies --no-clean)')
				->addOption('target', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
					'If provided, only export entities matching the given ID (format: "<entity>:<id>"')
				->addOption('no-clean', null, InputOption::VALUE_NONE,
					'Perform the export without cleaning the package directory first')
				->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer "yes" to all questions')
				->addOption('journal-only', null, InputOption::VALUE_NONE, 'Only rebuild journal entries, do not ' .
					'export data; implies --no-clean')
				->addOption('skip-assets', null, InputOption::VALUE_NONE, 'Do not export / download any asset data; ' .
					'implies --no-clean');
		}

		/**
		 * @param InputInterface  $input
		 * @param OutputInterface $output
		 *
		 * @return void
		 */
		protected function execute(InputInterface $input, OutputInterface $output): void {
			$io = new SymfonyStyle($input, $output);

			$path = rtrim($input->getArgument('output-path'), '/');

			if (!$path) {
				$io->error('Cannot export to an empty path (or your filesystem\'s root, ya dingus)');

				return;
			}

			$journalOnly = $input->getOption('journal-only');
			$skipAssets = $input->getOption('skip-assets');

			$noClean = $journalOnly || $skipAssets || $input->getOption('no-clean');

			if (!$journalOnly && !$noClean) {
				if (!$input->getOption('yes') && file_exists($path) && sizeof(scandir($path)) > 2) {
					if (!$io->confirm($path . ' is not empty. Are you sure you want to export there?', false)) {
						$io->warning('User cancelled operation.');

						return;
					}
				}

				exec(sprintf('rm -rf %s', escapeshellarg($path . '/json')));
				exec(sprintf('rm -rf %s', escapeshellarg($path . '/assets')));
			}

			if (!file_exists($path))
				mkdir($path, 0755, true);

			$classes = $input->getOption('entity');

			if (!$classes)
				$classes = self::ENTITY_LIST;
			else {
				$classes = array_map(function(string $name): string {
					if (strpos($name, '\\') === false)
						$name = 'App\\Entity\\' . ucfirst($name);

					return $name;
				}, $classes);
			}

			$targetCollections = [];

			if ($rawTargets = $input->getOption('target')) {
				foreach ($rawTargets as $rawTarget) {
					$entity = strtok($rawTarget, ':');
					$id = (int)strtok('');

					if (!$id)
						throw new \InvalidArgumentException('Invalid target descriptor: ' . $rawTarget);

					if (!in_array($entity, $classes))
						$classes[] = $entity;

					if (!isset($targetCollections[$entity]))
						$targetCollections[$entity] = [];

					$targetCollections[$entity][] = $id;
				}
			}

			$progress = new MultiProgressBar($output);
			$progress->append(sizeof($classes));
			$progress->start();

			foreach ($classes as $class) {
				$qb = $this->entityManager->createQueryBuilder()
					->from($class, 'e')
					->select('e');

				if ($targets = ($targetCollections[$class] ?? null)) {
					$qb
						->andWhere('e.id IN (:targets)')
						->setParameter('targets', $targets);
				}

				/** @var EntityInterface[] $entities */
				$entities = $qb->getQuery()->getResult();

				if (!sizeof($entities)) {
					$progress->advance();

					continue;
				}

				$progress->append(sizeof($entities));

				$journal = [];
				$topLevelGroup = null;

				foreach ($entities as $entity) {
					$export = $this->exportManager->export($entity);
					$group = $export->getGroup();

					if ($topLevelGroup === null)
						$topLevelGroup = substr($group, 0, strpos($group, '/') ?: strlen($group));

					$groupPath = ltrim(str_replace($topLevelGroup, '', $group), '/');

					if ($groupPath)
						$groupPath .= '/';

					$filename = $groupPath . $entity->getId() . '.json';
					$journal[$entity->getId()] = $filename;

					// Once the filename has been put in the journal, we convert it to an absolute path for the
					// rest of the iteration.
					$filename = $path . '/json/' . $topLevelGroup . '/' . $filename;

					if ($journalOnly) {
						$progress->advance();

						continue;
					}

					if (!file_exists($dir = dirname($filename)))
						mkdir($dir, 0755, true);

					$encoded = str_replace('    ', "\t", json_encode($export->getData(),
						JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

					file_put_contents($filename, $encoded);

					if (!$skipAssets && $assets = $export->getAssets()) {
						foreach ($assets as $asset) {
							$filename = $path . '/assets/' . ltrim(parse_url($asset->getUri(), PHP_URL_PATH), '/');

							if (file_exists($filename))
								continue;
							else if (!file_exists($dir = dirname($filename)))
								mkdir($dir, 0755, true);

							file_put_contents($filename, file_get_contents($asset->getUri()));
						}
					}

					$progress->advance();
				}

				if ($journal) {
					if (!$topLevelGroup) {
						throw new \RuntimeException('No top level group found for ' . $class .
							'; this is definitely not right');
					}

					$encoded = json_encode($journal, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

					file_put_contents($path . '/json/' . $topLevelGroup . '/.journal.json', $encoded);
				}

				$progress->advance();
			}

			$progress->finish();

			$io->success('Done!');
		}
	}
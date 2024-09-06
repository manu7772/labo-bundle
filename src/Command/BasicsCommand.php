<?php

namespace Aequation\LaboBundle\Command;

use Aequation\LaboBundle\Component\Opresult;
use Aequation\LaboBundle\Service\AppEntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:basics',
    description: 'Génère des entités d\'après des fichiers de description',
)]
class BasicsCommand extends Command
{
    public const DEFAULT_DATA_PATH = '/src/DataBasics/data/';
    protected const ALL_CLASSES = 'Toute les classes';

    // public readonly string $path;

    public function __construct(
        protected AppEntityManager $appEntityManager,
    )
    {
        parent::__construct();
    }
    
    protected function configure(): void
    {
        // $this->path = static::DEFAULT_DATA_PATH;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /**
         * @see https://symfony.com/doc/current/components/console/helpers/questionhelper.html
         */

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $allClassnames = $this->appEntityManager->getEntityNames(false, false, true);
        $question = new ChoiceQuestion(
            question: 'Choisissez une ou plusieurs classes d\'entités à générer :',
            choices: array_merge([0 => static::ALL_CLASSES], array_values($allClassnames)),
            default: 0
        );
        $question->setMultiselect(true);
        $classnames = $helper->ask($input, $output, $question);
        if(in_array(static::ALL_CLASSES, $classnames)) $classnames = array_values($allClassnames);
        $io->writeln('Entités à générer :'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $classnames));


        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $paths = [static::DEFAULT_DATA_PATH];
        $question = new Question('Indiquez le chemin vers les données ['.static::DEFAULT_DATA_PATH.'] :', static::DEFAULT_DATA_PATH);
        $question->setAutocompleterValues($paths);
        $path = $helper->ask($input, $output, $question);
        $io->writeln(vsprintf('- Chemin vers les données de génération : %s', [$path]));


        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Remplace si existante (oui/non) (défaut: oui) ? ', true, '/^(o|oui|y|yes)/i');
        $replace = $helper->ask($input, $output, $question);
        $io->writeln(vsprintf('- Remplace : %s', [$replace ? 'Oui' : 'Non']));
        if($replace) {
            $io->warning('Remplace si existant.');
            sleep(1);
        }

        $result = $this->appEntityManager->loadEntities(
            path: $path,
            replace: $replace,
            persist: true,
            classes: $classnames,
            io: $io,
        );

        // Print all messages
        $result->printMessages($io);
        // Report
        if($result->isUndone()) {
            $io->warning(vsprintf('Aucune action n\'a été effectuée%s', [PHP_EOL.$result->getMessagesAsString(false)]));
            return Command::INVALID;
        } else if($result->isSuccess()) {
            $io->success(vsprintf('Les entités ont été générées/mises à jour : %d entité(s) enregistrée(s) sur %d', [$result->getActions(Opresult::ACTION_SUCCESS), $result->getData('total')]));
            return Command::SUCCESS;
        } else if($result->hasSuccess()) {
            $io->warning(vsprintf('Les entités ont été générées/mises à jour, certaines n\'ont pu être générées : %d entité(s) enregistrée(s) sur %d', [$result->getActions(Opresult::ACTION_SUCCESS), $result->getData('total')]));
            return Command::INVALID;
        } else {
            $io->error(vsprintf('Génération échouée : %d. %d entité(s) enregistrée(s) sur %d', [$result->getActions([Opresult::ACTION_DANGER, Opresult::ACTION_WARNING]), $result->getActions(Opresult::ACTION_SUCCESS), $result->getData('total')]));
            return Command::FAILURE;
        }

    }
}

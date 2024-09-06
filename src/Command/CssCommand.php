<?php
namespace Aequation\LaboBundle\Command;

use Aequation\LaboBundle\Service\Interface\CssDeclarationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:css',
    description: 'Declare css classes (for tailwind) in a twig file (app/templates/tailwind_css_declarations.html.twig)',
)]
class CssCommand extends Command
{
    public function __construct(
        private CssDeclarationInterface $cssDeclaration
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // $this
        //     ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        // ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if(!$this->cssDeclaration->refreshClasses()) {
            return $io->error(vsprintf('L\'enregistrement du fichier %s a échoué !', []));
        }
        
        $classes = $this->cssDeclaration->getClasses(true);
        $io->success(vsprintf('%s classes CSS ont été déclarées.', [count($classes)]));
        return Command::SUCCESS;
    }

}

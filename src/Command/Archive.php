<?php

namespace JCli\Command;

use JCli\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Archive extends Command
{

    protected function configure()
    {
        $this->setName('archive');
        $this->setDescription('Archive project');
        $this->addOption('db', null, InputOption::VALUE_NONE, 'Include database dump to archive');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $db      = $input->getOption('db') === true;
        $db_path = false;

        if ($db) {
            $db_path = $this->config->db . $this->getTimestamp() . '.sql';

            $command   = new Database();
            $arguments = [
                '--export' => $db_path
            ];
            $arguments = new ArrayInput($arguments);
            $command->run($arguments, $output);

            $db_path = realpath($db_path);
        }

        $output->writeln('<info>Scanning files</info>');
        $files = $this->getJoomlaFiles();

        $output->writeln("<info>Archiving files</info>");

        $path = getcwd() . DIRECTORY_SEPARATOR . $this->name . $this->getTimestamp() . '.zip';
        $zip  = new \ZipArchive();
        if (true !== $zip->open($path, \ZipArchive::CREATE)) throw new \RuntimeException("Can't open {$path}.");

        $progress = new ProgressBar($output, count($files));
        $progress->display();

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $realpath  = $file->getRealPath();
                $localname = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $realpath);

                $zip->addFile($realpath, $localname);
            }

            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');
        $output->write("<info>Finalizing archive...</info>");

        $zip->close();

        if ($db_path) unlink($db_path);

        $output->writeln(" <info>Done: " . basename($path) . "</info>");

    }


}
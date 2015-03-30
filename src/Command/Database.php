<?php

namespace JCli\Command;

use JCli\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Database extends Command
{

    protected function configure()
    {
        $this->setName('db');
        $this->setDescription('Database functions');
        $this->addOption('export', 'e', InputOption::VALUE_REQUIRED, 'Export database');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $export = $input->getOption('export');

        if ($export) {
            $output->writeln('<info>Exporting database</info>');
            $db_path = $this->exportDatabase($export);

            $output->writeln('<comment>Database exported: ' . basename($db_path) . '</comment>');

            return;
        }
    }

    protected function exportDatabase($path)
    {
        $cmd   = [];
        $cmd[] = 'mysqldump';
        $cmd[] = '--user=' . escapeshellarg($this->config->user);
        $cmd[] = '--password=' . escapeshellarg($this->config->password);
        $cmd[] = '--add-drop-table';
        $cmd[] = '--extended-insert';
        $cmd[] = '--compact';
        $cmd[] = $this->config->db;
        $cmd[] = '>';
        $cmd[] = escapeshellarg($path);
        $cmd   = implode(' ', $cmd);

        exec($cmd, $output, $code);

        if ($code !== 0) throw new \RuntimeException('mysqldump failed.');

        return realpath($path);
    }

}
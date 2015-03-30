<?php

namespace JCli\Command;

use JCli\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Database extends Command
{

    protected function configure()
    {
        $this->setName('db');
        $this->setDescription('Database functions');
        $this->addOption('export', 'e', InputOption::VALUE_REQUIRED, 'Export database');
        $this->addOption('import', 'i', InputOption::VALUE_REQUIRED, 'Import database');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $export = $input->getOption('export');
        $import = $input->getOption('import');

        if ($export) {
            $output->writeln('<info>Exporting database</info>');
            $db_path = $this->exportDatabase($export);

            $output->writeln('<comment>Database exported: ' . basename($db_path) . '</comment>');

            return;
        }

        if ($import) {
            $import = realpath($import);
            if (!$import) throw new \RuntimeException("Import file doesn't exist");

            $db         = $this->config->db;
            $connection = $this->getConnection(false);

            $query  = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "' . $db . '"';
            $result = $connection->query($query);
            $exists = $result ? $result->fetch(\PDO::FETCH_ASSOC) : false;

            if ($exists) {
                $helper   = $this->getHelper('question');
                $question = new ConfirmationQuestion('<question>Dump existing database? [Y/n]</question> ', true);
                $backup   = $helper->ask($input, $output, $question);

                if ($backup) {
                    $path = getcwd() . DIRECTORY_SEPARATOR . $db . $this->getTimestamp() . '.sql';
                    $path = $this->exportDatabase($path);

                    $output->writeln('<comment>Database dumped to ' . basename($path) . '</comment>');
                }
            }

            $output->writeln('<info>Importing database</info>');
            $this->dbCreate($connection, $db);

            $cmd   = [];
            $cmd[] = 'mysql';
            $cmd[] = '--user=' . escapeshellarg($this->config->user);
            $cmd[] = '--password=' . escapeshellarg($this->config->password);
            $cmd[] = $db;
            $cmd[] = '<';
            $cmd[] = escapeshellarg($import);
            $cmd   = implode(' ', $cmd);

            exec($cmd, $o, $c);

            if ($c !== 0) throw new \RuntimeException('mysql import failed.');

            $output->writeln('<comment>Database imported successfully</comment>');
        }
    }

    private function dbCreate(\PDO $connection, $db)
    {
        $query  = 'DROP DATABASE IF EXISTS ' . $db;
        $result = $connection->query($query);

        $query  = 'CREATE DATABASE ' . $db;
        $result = $connection->query($query);

        if (!$result) throw new \RuntimeException('Could not create database ' . $db);

        $query  = 'ALTER DATABASE ' . $db . ' CHARACTER SET utf8 COLLATE utf8_general_ci';
        $result = $connection->query($query);

        if (!$result) throw new \RuntimeException('Could not change collation');

        return $result;
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
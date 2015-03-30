<?php

namespace JCli\Command;

use JCli\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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
            $output->writeln('<info>Exporting database</info>');
            $db_path = $this->exportDatabase();
        }

        $output->writeln('<info>Scanning files</info>');
        $files = $this->getFiles();

        $output->writeln("<info>Archiving files</info>");

        $path = getcwd() . DIRECTORY_SEPARATOR . $this->name . '-' . date('Y-m-d') . '-' . time() . '.zip';
        $zip  = new \ZipArchive();
        if (true !== $zip->open($path, \ZipArchive::CREATE)) throw new \RuntimeException("Can't open {$path}.");

        $progress = new ProgressBar($output, count($files));
        $progress->display();

        foreach ($files as $file) {
            $realpath  = $file->getRealPath();
            $localname = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $realpath);

            $zip->addFile($realpath, $localname);

            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');
        $output->write("<info>Finalizing archive...</info>");

        $zip->close();

        if ($db_path) unlink($db_path);

        $output->writeln(" <info>Done: " . basename($path) . "</info>");

    }

    protected function getFiles()
    {
        $dir      = getcwd();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        $files    = iterator_to_array($iterator);

        $ignore   = [
            $this->config->log_path,
            $this->config->tmp_path,
            getcwd() . '/cache',
            getcwd() . '/administrator/cache',
        ];
        $ignore   = array_map(function ($path) { return realpath($path); }, $ignore);
        $ignore   = array_filter($ignore, function ($path) { return $path; });
        $ignore[] = DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR;
        $ignore[] = DIRECTORY_SEPARATOR . '.idea' . DIRECTORY_SEPARATOR;
        $ignore[] = DIRECTORY_SEPARATOR . '.berk' . DIRECTORY_SEPARATOR;

        $files = array_filter($files, function ($file) use ($ignore) {
            if ($file->isDir()) return false;

            $realpath = $file->getRealPath();
            foreach ($ignore as $path) {
                if (strpos($realpath, $path) !== false) return false;
            }

            $basename = $file->getBasename();
            if (in_array($basename, ['.DS_Store', 'Thumbs.db'])) return false;
            if (preg_match('/' . $this->name . '-\d{4}-\d{2}-\d{2}-\d+.zip/', $basename)) return false;
            if (preg_match('/' . $this->config->db . '-\d{4}-\d{2}-\d{2}-\d+.sql/', $basename)) return false;

            return true;
        });

        return $files;
    }

}
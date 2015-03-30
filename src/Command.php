<?php

namespace JCli;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{

    protected $config;
    protected $config_file;
    protected $name;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $config_file = getcwd() . '/configuration.php';
        if (is_file($config_file)) include_once $config_file;

        if (!class_exists('JConfig')) throw new \RuntimeException("Directory doesn't appear to be a Joomla installation");

        $this->config      = new \JConfig();
        $this->config_file = realpath($config_file);

        $name = getcwd();
        $name = explode(DIRECTORY_SEPARATOR, $name);

        $this->name = array_pop($name);
    }

    protected function exportDatabase()
    {

        $path = $this->config->db . '-' . date('Y-m-d') . '-' . time() . '.sql';

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

    protected function getAllFiles()
    {
        $dir      = getcwd();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        $files    = iterator_to_array($iterator);
        $ignore   = ['.git', '.idea', '.berk'];

        return array_filter($files, function ($file) use ($ignore) {
            $realpath = $file->getRealPath();

            foreach ($ignore as $value) {
                if (strpos($realpath, DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR) !== false) return false;

                if ($file->isDir()) {
                    $realpath = explode(DIRECTORY_SEPARATOR, $realpath);
                    $realpath = array_pop($realpath);

                    if ($realpath == $value) return false;
                }
            }

            return true;
        });
    }

    protected function getJoomlaFiles()
    {
        $files  = $this->getAllFiles();
        $ignore = [
            $this->config->log_path,
            $this->config->tmp_path,
            getcwd() . '/cache',
            getcwd() . '/administrator/cache',
        ];

        $ignore = array_map(function ($path) { return realpath($path); }, $ignore);
        $ignore = array_filter($ignore, function ($path) { return $path; });

        return array_filter($files, function ($file) use ($ignore) {
            if ($file->isDir()) return true;

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
    }
}
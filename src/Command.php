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
}
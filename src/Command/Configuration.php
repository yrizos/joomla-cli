<?php

namespace JCli\Command;

use JCli\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Configuration extends Command
{

    protected function configure()
    {
        $this->setName('config');
        $this->setDescription('Update configuration');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $logs_path = !empty($this->config->log_path) ? basename($this->config->log_path) : 'logs';
        $logs_path = getcwd() . DIRECTORY_SEPARATOR . $logs_path;
        $tmp_path  = !empty($this->config->tmp_path) ? basename($this->config->tmp_path) : 'tmp';
        $tmp_path  = getcwd() . DIRECTORY_SEPARATOR . $tmp_path;


        $variables = [
            'host'     => [$this->config->host, 'Database host'],
            'db'       => [$this->config->db, 'Database name'],
            'user'     => [$this->config->user, 'Database user'],
            'password' => [null, 'Database password'],
            'log_path' => [$logs_path, 'Logs path'],
            'tmp_path' => [$tmp_path, 'Temp path'],
        ];

        $replace = [];
        $helper  = $this->getHelper('question');

        foreach ($variables as $key => $value) {
            $default  = $value[0];
            $question = '<question>' . $value[1] . '?' . ($default ? ' [' . $default . ']' : '') . '</question> ';
            $question = new Question($question, $default);
            $result   = $helper->ask($input, $output, $question);
            $result   = strval($result);
            $result   = trim($result);

            if (in_array($key, ['log_path', 'tmp_path'])) {
                if (!is_dir($result)) {
                    if (strpos($result, getcwd()) !== 0) $result = getcwd() . DIRECTORY_SEPARATOR . $result;

                    $mkdir = @mkdir($result, 0777, true);
                    if (!$mkdir) throw new \RuntimeException("Couldn't create directory {$result}");
                }

                $result = realpath($result);
            }

            if ($result != $default) $replace[$key] = $result;
        }

        if (empty($replace)) return;

        if (empty($replace)) return;

        $contents = file_get_contents($this->config_file);

        foreach ($replace as $key => $value) {
            $pattern  = 'public \$' . $key . ' = \'(.*?)\';';
            $replace  = 'public $' . $key . ' = \'' . trim($value) . '\';';
            $contents = preg_replace('/' . $pattern . '/', $replace, $contents);
        }

        file_put_contents($this->config_file, $contents);

        $output->writeln('<info>Configuration updated</info>');
    }

}
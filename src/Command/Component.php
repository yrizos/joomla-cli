<?php

namespace JCli\Command;

use JCli\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Component extends Command
{

    protected function configure()
    {
        $this->setName('component');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        //        $name         = $this->ask('Component name?', true, $input, $output);
        //        $version      = $this->ask('Version?', true, $input, $output);
        //        $author_name  = $this->ask('Author name?', true, $input, $output);
        //        $author_email = $this->ask('Author email?', true, $input, $output);

        $name    = 'Hello World';
        $version = '0.0.1';

        $component = self::getComponentName($name);
        $path      = strtolower($component);
        $base_dir  = $this->config->tmp_path . DIRECTORY_SEPARATOR . 'com_' . $path . '_' . $version . '_' . time();

        $this->createFiles($component, $version, $path, $base_dir);

    }

    private function createFiles($component, $version, $path, $base_dir)
    {
        $files                                              = [];
        $files['site/' . $path . '.php']                    = self::parseTemplate('site-entry-point', ['component' => $component]);
        $files['site/controller.php']                       = self::parseTemplate('site-controller', ['component' => $component]);
        $files['site/views/' . $path . '/view.html.php']    = self::parseTemplate('site-view', ['component' => $component]);
        $files['site/views/' . $path . '/tmpl/default.php'] = self::parseTemplate('site-tmpl', ['component' => $component]);
        $files['site/assets/js/' . $path . '.js']           = '';
        $files['site/assets/css/' . $path . '.css']         = '';

        $files['admin/sql/install.mysql.utf8.sql']             = '';
        $files['admin/sql/uninstall.mysql.utf8.sql']           = '';
        $files['admin/sql/updates/mysql/' . $version . '.sql'] = '';


        foreach ($files as $file => $contents) {
            $path = $base_dir . DIRECTORY_SEPARATOR . $file;
            $dir  = dirname($path);

            if (!is_dir($dir)) mkdir($dir, 0777, true);

            file_put_contents($path, $contents);
        }
    }

    public static function parseTemplate($template, array $data)
    {
        $template = __DIR__ . '/../../templates/' . $template . '.tmpl';
        $template = file_exists($template) ? @file_get_contents($template) : false;

        if (!$template) throw new \RuntimeException("Template {$template} doesn't exist");

        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return trim($template);
    }

    public static function getComponentName($name)
    {
        $name = preg_replace("/\\W|_/", "", $name);

        return $name;
    }

    private function ask($question, $required = false, InputInterface $input, OutputInterface $output)
    {
        $helper   = $this->getHelper('question');
        $required = $required === true;
        $question = '<question>' . trim($question) . '</question> ';
        $question = new Question($question);

        if ($required) {
            $question->setValidator(function ($answer) {
                if (empty($answer)) throw new \InvalidArgumentException('Cannot be empty.');

                return $answer;
            });
        }


        $answer = $helper->ask($input, $output, $question);

        return trim($answer);
    }


}
<?php

namespace JCli\Command;

use JCli\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComponentArchive extends Command
{

    protected function configure()
    {
        $this->setName('com:archive');
        $this->addArgument('component', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $component = $i->getArgument('component');
        $component = $this->loadComponent($component);

        $zip = new \ZipArchive();
        if (true !== $zip->open($component['archive'], \ZipArchive::CREATE)) throw new \RuntimeException("Can't open {$component['archive']}.");

        foreach ($component['files'] as $source => $target) {
            $target = str_replace(["\\", '/'], '/', $target);
            $target = ltrim($target, '/');

            $zip->addFile($source, $target);
        }

        $zip->close();

        $o->writeln("<info>Done: " . basename($component['archive']) . "</info>");
    }

    protected function loadComponent($component)
    {
        $dir_site  = realpath(getcwd() . '/components/' . $component);
        $dir_admin = realpath(getcwd() . '/administrator/components/' . $component);
        $xml_file  = realpath($dir_site . '/' . $component . '.xml');

        if (!is_file($xml_file)) $xml_file = realpath($dir_admin . '/' . $component . '.xml');
        if (!is_file($xml_file)) throw new \RuntimeException("Couldn't find component's xml manifest.");

        $xml   = simplexml_load_file($xml_file);
        $files = [];
        $files = $files + $this->getFilesFromXml($xml->files, $dir_site);
        $files = $files + $this->getFilesFromXml($xml->administration->files, $dir_admin);
        $files = $files + $this->getLanguagesFromXml($xml->languages, realpath(getcwd() . '/language'));
        $files = $files + $this->getLanguagesFromXml($xml->administration->languages, realpath(getcwd() . '/administrator/language'));
        $files = $files + $this->getLanguagesFromXml($xml->administration->languages, realpath(getcwd() . '/administrator/language'));
        $files = $files + $this->getSqlFromXml($xml, $dir_admin);

        $result                     = [];
        $result['archive']          = getcwd() . '/' . $component . '_' . strval($xml->version) . '.zip';
        $result['files']            = $files;
        $result['files'][$xml_file] = basename($xml_file);

        return $result;
    }

    protected function getSqlFromXml($xml, $original_dir)
    {
        $result = [];

        if (!empty($xml->install)) {
            foreach ($xml->install->sql->file as $target) {
                $target = strval($target);
                $source = realpath($original_dir . '/' . $target);
                if (!$source) continue;

                $result[$source] = $target;
            }
        }

        if (!empty($xml->uninstall)) {
            foreach ($xml->uninstall->sql->file as $target) {
                $target = strval($target);
                $source = realpath($original_dir . '/' . $target);
                if (!$source) continue;

                $result[$source] = $target;
            }
        }

        return $result;
    }

    protected function getLanguagesFromXml($xml, $original_dir)
    {
        $result = [];
        $folder = isset($xml['folder']) ? (string)$xml['folder'] . '/' : '';

        if (!empty($xml->language)) {
            foreach ($xml->language as $language) {
                $tag    = isset($language['tag']) ? (string)$language['tag'] . '/' : '';
                $target = $folder . (string)$language;
                $source = realpath($original_dir . '/' . $tag . basename($target));

                if (!$source) continue;

                $result[$source] = $target;
            }
        }

        return $result;
    }

    protected function getFilesFromXml($xml, $original_dir)
    {

        $result = [];
        $folder = isset($xml['folder']) ? (string)$xml['folder'] . '/' : '';

        if (!empty($xml->filename)) {
            foreach ($xml->filename as $filename) {
                $filename = strval($filename);
                $source   = realpath($original_dir . '/' . $filename);

                if (!file_exists($source)) continue;

                $result[$source] = $folder . $filename;
            }
        }

        if (!empty($xml->folder)) {
            foreach ($xml->folder as $dir) {
                $dir   = realpath($original_dir . '/' . strval($dir));
                $files = $this->getFiles($dir);

                foreach ($files as $source) {
                    $target          = str_replace($original_dir . DIRECTORY_SEPARATOR, '', $source);
                    $result[$source] = $folder . $target;
                }
            }
        }

        return $result;
    }

    protected function getFiles($dir)
    {
        if (!is_dir($dir)) return [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        $files    = [];
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir()) continue;

            $files[] = $fileinfo->getRealPath();
        }

        return $files;
    }

}
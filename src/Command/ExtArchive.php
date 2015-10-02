<?php

namespace JCli\Command;

use JCli\Command;
use JCli\Joomla\Helper;
use JCli\Joomla\ManiferstReader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtArchive extends Command
{

    protected function configure()
    {
        $this->setName('ext:archive');
        $this->setDescription('Archive extension');
        $this->addArgument('name', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {

        $path_ext  = $i->getArgument('name');
        $path_ext  = str_replace("\\", '/', $path_ext);
        $name      = array_pop(explode('/', $path_ext));
        $path_base = getcwd();
        $path_ext  = ManiferstReader::discoverExtension($path_ext, $path_base);

        if (!$path_ext) throw new \RuntimeException('Extension ' . $name . ' not found');

        $manifest = ManiferstReader::discoverManifest($name, $path_ext);
        if (!$manifest) throw new \RuntimeException('Manifest XML file not found');

        $manifest = ManiferstReader::parse($manifest, $path_base);
        $version  = !empty($manifest['info']['version']) ? $manifest['info']['version'] : '0.0.0';
        $version .= '_' . date('Ymd');

        $archive = $manifest['info']['extension']['type'] == 'plugin' ? 'plg_' . $name : $name;
        $archive = $path_base . '/' . $archive . '_' . $version . '.zip';

        $o->writeln('<info>Archiving extension</info>');
        if (file_exists($archive)) unlink($archive);

        $zip = new \ZipArchive();
        if (true !== $zip->open($archive, \ZipArchive::CREATE)) throw new \RuntimeException("Can't open {$archive}.");

        foreach ($manifest['files'] as $path) {
            $o->writeln($path[1]);
            $zip->addFile($path[0], $path[1]);
        }

        $zip->close();

        $o->writeln('');
        $o->writeln('<comment>Done: ' . basename($archive) . '</comment>');

    }


}
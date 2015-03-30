<?php

namespace JCli;

use JCli\Command\Archive;
use JCli\Command\Configuration;
use JCli\Command\Database;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    const NAME = 'joomla cli';
    const VERSION = '0.0.1';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->add(new Archive());
        $this->add(new Configuration());
        $this->add(new Database());
    }
}
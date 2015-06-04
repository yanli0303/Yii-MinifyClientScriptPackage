<?php

namespace YiiMinifyClientScriptPackage;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class MinifyCommand extends Command
{

    private function findConfigFile($appBasePath)
    {
        $configDir = $appBasePath.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config';
        if (is_dir($configDir)) {
            $clientScript = $configDir.DIRECTORY_SEPARATOR.'clientscript.php';
            if (is_file($clientScript)) {
                return $clientScript;
            }

            $mainConfig = $configDir.DIRECTORY_SEPARATOR.'main.php';
            if (is_file($mainConfig)) {
                return $mainConfig;
            }
        }
    }

    protected function configure()
    {
        $cwd                = getcwd();
        $configDefault      = $this->findConfigFile($cwd);
        $configMode         = is_string($configDefault) ? InputOption::VALUE_OPTIONAL : InputOption::VALUE_REQUIRED;
        $appBasePathDefault = is_string($configDefault) ? $cwd : null;

        $this
                ->setName('minify')
                ->setDescription('Minify client script packages for a Yii web application.')
                ->addOption('appBasePath', 'a', $configMode, 'Root path of the Yii web application.', $appBasePathDefault)
                ->addOption('config', 'c', $configMode, 'Path to Yii config file. If you are using a separated file for "clientScript" component, use that file instead.', $configDefault)
                ->addOption('rewriteCssUrl', 'r', InputOption::VALUE_OPTIONAL, 'Whether to rewrite "url()" rules after relocating CSS files.', 'true')
                ->addOption('minFileSuffix', 'm', InputOption::VALUE_OPTIONAL, 'The file name(without extension) suffix of minified files.', '.min')
                ->addOption('publishDir', 'p', InputOption::VALUE_OPTIONAL, 'The path which is relative to "appBasePath" for publishing minified resources.', 'assets')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appBasePath   = $input->getOption('appBasePath');
        $rewriteCssUrl = 'true' === $input->getOption('rewriteCssUrl');
        $minFileSuffix = $input->getOption('minFileSuffix');
        $publishDir    = $input->getOption('publishDir');
        $options       = new MinifyOptions($appBasePath, $rewriteCssUrl, $minFileSuffix, $publishDir);

        $configFile = $input->getOption('config');
        if (!is_file($configFile)) {
            $configFile = $this->findConfigFile($options->getAppBasePath());
        }
        $config = new YiiConfig($configFile);

        if (false === rename($configFile, $configFile.'.bak')) {
            throw new Exception('Unable to create backup for file: '.$configFile);
        }

        $config->minifyClientScriptPackages($options);

        if (false === file_put_contents($configFile, $config->render())) {
            throw new Exception('Unable to write file: '.$configFile);
        }
    }

}

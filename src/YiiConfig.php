<?php

namespace YiiMinifyClientScriptPackage;

class YiiConfig
{
    /**
     * @var array
     */
    protected $statements;

    /**
     * @var array An array of YiiClientScriptPackage instances.
     */
    protected $clientScriptPackages;

    public function __construct($configFile)
    {
        if (!is_string($configFile)) {
            throw new \InvalidArgumentException('$configFile should be a string.');
        }

        if (!is_file($configFile)) {
            throw new \InvalidArgumentException('File not found: '.$configFile);
        }

        $phpCode          = file_get_contents($configFile);
        $parser           = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative());
        $this->statements = $parser->parse($phpCode);

        $clientScriptPackages       = $this->findClientScriptPackagesExpression();
        $this->clientScriptPackages = $this->getClientScriptPackages($clientScriptPackages->value);
    }

    protected function findClientScriptExpression()
    {
        $returnArray = PhpParserHelper::findFirstReturnArrayStatement($this->statements);
        if (empty($returnArray)) {
            return;
        }
        // find element as in: 'components' => array(...)
        $components = PhpParserHelper::arrayGetValueByKey($returnArray->expr, 'components');
        if (empty($components)) {
            // if the root settings array contains: "class" => "client script class name, such as CClientScript"
            // the clientScript is stored in a separated file, the $returnArray is the $clientScriptExpression
            $class_ = PhpParserHelper::arrayGetValueByKey($returnArray->expr, 'class');
            if (!empty($class_)) {
                return $returnArray->expr;
            }

            return;
        }

        return PhpParserHelper::arrayGetValueByKey($components, 'clientScript');
    }

    protected function findClientScriptPackagesExpression()
    {
        $clientScript = $this->findClientScriptExpression();
        if (empty($clientScript)) {
            return;
        }

        if ($clientScript instanceof \PhpParser\Node\Expr\Include_) {
            throw new \Exception('It seems the "clientScript" options are in a separated file, please specify that file as the config file.');
        }

        if ($clientScript instanceof \PhpParser\Node\Expr\Array_) {
            return PhpParserHelper::arrayGetItemByKey($clientScript, 'packages');
        }
    }

    protected function getClientScriptPackages(\PhpParser\Node\Expr\Array_ $clientScriptPackages)
    {
        $packages = array();
        foreach ($clientScriptPackages->items as $package) {
            $packages[] = new YiiClientScriptPackage($package);
        }

        return $packages;
    }

    /**
     * @param string $name
     * @return YiiClientScriptPackage
     */
    protected function getClientScriptPackageByName($name)
    {
        foreach ($this->clientScriptPackages as $package) {
            if ($name === $package->name) {
                return $package;
            }
        }
    }

    protected function resolveDepends(YiiClientScriptPackage $package)
    {
        if (empty($package->depends)) {
            return;
        }

        $newCss = array();
        $newJs  = array();

        $externalDepends = array();
        foreach ($package->depends as $dependPackageName) {
            $dependPackage = $this->getClientScriptPackageByName($dependPackageName);
            if (empty($dependPackage)) {
                throw new \Exception('Client script package not found: '.$dependPackageName);
            }

            $this->resolveDepends($dependPackage);
            if ($dependPackage->isExternal) {
                $externalDepends[] = $dependPackageName;
            } else {
                MinifyHelper::joinNumericArray($newCss, $dependPackage->css);
                MinifyHelper::joinNumericArray($newJs, $dependPackage->js);
            }
        }

        MinifyHelper::joinNumericArray($newCss, $package->css);
        MinifyHelper::joinNumericArray($newJs, $package->js);

        $package->css     = $newCss;
        $package->js      = $newJs;
        $package->depends = $externalDepends;
    }

    public function minifyClientScriptPackages(MinifyOptions $options)
    {
        foreach ($this->clientScriptPackages as $package) {
            $this->resolveDepends($package);
        }

        $items = array();
        foreach ($this->clientScriptPackages as $package) {
            $package->minify($options);
            $items[] = $package->generateArrayItem();
        }

        $clientScriptPackages        = $this->findClientScriptPackagesExpression();
        $clientScriptPackages->value = new \PhpParser\Node\Expr\Array_($items);
    }

    public function render()
    {
        $prettyPrinter = new WrappingArrayPrettyPrinter();
        return $prettyPrinter->prettyPrintFile($this->statements);
    }

}

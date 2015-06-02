<?php

namespace YiiMinifyClientScriptPackage;

class TestHelper
{

    /**
     * Make use of PHP reflection, invoke a "private" or "protected" method of specifiled class instance.
     * Usually it's not necessary to test the "private" or "protected" methods.
     * Unless you have overriden such a method of framework.
     *
     * @param object $classInstance the class instance whose method will be invoked
     * @param string $methodName    the name of the method
     * @param array  $arguments     the arguments for the method
     *
     * @return mixed returns the return value of the method
     */
    public static function invokeProtectedMethod($classInstance, $methodName, $arguments = array())
    {
        $className = get_class($classInstance);
        $class     = new ReflectionClass($className);
        $method    = $class->getMethod($methodName);
        $method->setAccessible(true);

        if (empty($arguments)) {
            return $method->invoke($classInstance);
        } else {
            array_unshift($arguments, $classInstance);

            return call_user_func_array(array($method, 'invoke'), $arguments);
        }
    }

    /**
     * Make use of PHP reflection, invoke a static "private" or "protected" method of specifiled class.
     * Usually it's not necessary to test the "private" or "protected" methods.
     * Unless you have overriden such a method of framework.
     *
     * @param string $className  the class name
     * @param string $methodName the name of the method
     * @param array  $arguments  the arguments for the method
     *
     * @return mixed returns the return value of the method
     */
    public static function invokeStaticProtectedMethod($className, $methodName, $arguments = array())
    {
        $class  = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        if (empty($arguments)) {
            return $method->invoke(null);
        } else {
            array_unshift($arguments, null);

            return call_user_func_array(array($method, 'invoke'), $arguments);
        }
    }

    /**
     * Get value of private and protected properties of an object.
     *
     * @param object $classInstance a class instance.
     * @param string $propertyName  property name
     *
     * @return mixed returns the property value.
     */
    public static function getProtectedProperty($classInstance, $propertyName)
    {
        $instance = new ReflectionObject($classInstance);
        $prop     = $instance->getProperty($propertyName);
        $prop->setAccessible(true);

        return $prop->getValue($classInstance);
    }

    /**
     * Set new value for a private and protected property of an object.
     *
     * @param object $classInstance a class instance.
     * @param string $propertyName  property name
     * @param mixed  $newValue      new value to set
     *
     * @return mixed returns the property value.
     */
    public static function setProtectedProperty($classInstance, $propertyName, $newValue)
    {
        $instance = new ReflectionObject($classInstance);
        $prop     = $instance->getProperty($propertyName);
        $prop->setAccessible(true);

        return $prop->setValue($classInstance, $newValue);
    }

    /**
     * Gets static property value.
     *
     * @param string $className    class name
     * @param string $propertyName property name
     *
     * @return mixed returns the static property value
     */
    public static function getStaticPropertyValue($className, $propertyName)
    {
        $ref = new ReflectionClass($className);

        return $ref->getStaticPropertyValue($propertyName);
    }

    /**
     * Sets static property value.
     *
     * @@param string $className class name
     * @param string $propertyName property name
     * @param mixed  $newValue     New property value.
     */
    public static function setStaticPropertyValue($className, $propertyName, $newValue)
    {
        $ref  = new ReflectionClass($className);
        $prop = $ref->getProperty($propertyName);
        $prop->setAccessible(true);

        return $prop->setValue($newValue);
    }

    /**
     * Copied from Yii CFileHelper class.
     * Removes a directory recursively.
     * @param string $directory to be deleted recursively.
     * @param array $options for the directory removal. Valid options are:
     * <ul>
     * <li>traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     * Defaults to `false`, meaning that the content of the symlinked directory would not be deleted.
     * Only symlink would be removed in that default case.</li>
     * </ul>
     * Note, options parameter is available since 1.1.16
     * @since 1.1.14
     */
    public static function removeDirectory($directory, $options = array())
    {
        if (!isset($options['traverseSymlinks']))
            $options['traverseSymlinks'] = false;
        $items                       = glob($directory.DIRECTORY_SEPARATOR.'{,.}*', GLOB_MARK | GLOB_BRACE);
        foreach ($items as $item) {
            if (basename($item) == '.' || basename($item) == '..')
                continue;
            if (substr($item, -1) == DIRECTORY_SEPARATOR) {
                if (!$options['traverseSymlinks'] && is_link(rtrim($item, DIRECTORY_SEPARATOR)))
                    unlink(rtrim($item, DIRECTORY_SEPARATOR));
                else
                    self::removeDirectory($item, $options);
            } else
                unlink($item);
        }
        if (is_dir($directory = rtrim($directory, '\\/'))) {
            if (is_link($directory))
                unlink($directory);
            else
                rmdir($directory);
        }
    }

    /**
     * Copied from Yii CFileHelper class.
     * Shared environment safe version of mkdir. Supports recursive creation.
     * For avoidance of umask side-effects chmod is used.
     *
     * @param string $dst path to be created
     * @param integer $mode the permission to be set for newly created directories, if not set - 0777 will be used
     * @param boolean $recursive whether to create directory structure recursive if parent dirs do not exist
     * @return boolean result of mkdir
     * @see mkdir
     */
    public static function createDirectory($dst, $mode = null, $recursive = false)
    {
        if ($mode === null)
            $mode    = 0777;
        $prevDir = dirname($dst);
        if ($recursive && !is_dir($dst) && !is_dir($prevDir))
            self::createDirectory(dirname($dst), $mode, true);
        $res     = mkdir($dst, $mode);
        @chmod($dst, $mode);
        return $res;
    }

    /**
     * Returns the files found under the specified directory and subdirectories.
     * This method is mainly used by {@link findFiles}.
     * @param string $dir the source directory
     * @param string $base the path relative to the original source directory
     * @param array $fileTypes list of file name suffix (without dot). Only files with these suffixes will be returned.
     * @param array $exclude list of directory and file exclusions. Each exclusion can be either a name or a path.
     * If a file or directory name or path matches the exclusion, it will not be copied. For example, an exclusion of
     * '.svn' will exclude all files and directories whose name is '.svn'. And an exclusion of '/a/b' will exclude
     * file or directory '$src/a/b'. Note, that '/' should be used as separator regardless of the value of the DIRECTORY_SEPARATOR constant.
     * @param integer $level recursion depth. It defaults to -1.
     * Level -1 means searching for all directories and files under the directory;
     * Level 0 means searching for only the files DIRECTLY under the directory;
     * level N means searching for those directories that are within N levels.
     * @return array files found under the directory.
     */
    protected static function findFilesRecursive($dir, $base, $fileTypes, $exclude, $level)
    {
        $list   = array();
        $handle = opendir($dir);
        while (($file   = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..')
                continue;
            $path   = $dir.DIRECTORY_SEPARATOR.$file;
            $isFile = is_file($path);
            if (self::validatePath($base, $file, $isFile, $fileTypes, $exclude)) {
                if ($isFile)
                    $list[] = $path;
                elseif ($level)
                    $list   = array_merge($list, self::findFilesRecursive($path, $base.'/'.$file, $fileTypes, $exclude, $level - 1));
            }
        }
        closedir($handle);
        return $list;
    }

    /**
     * Copies a directory recursively as another.
     * If the destination directory does not exist, it will be created recursively.
     * @param string $src the source directory
     * @param string $dst the destination directory
     * @param array $options options for directory copy. Valid options are:
     * <ul>
     * <li>fileTypes: array, list of file name suffix (without dot). Only files with these suffixes will be copied.</li>
     * <li>exclude: array, list of directory and file exclusions. Each exclusion can be either a name or a path.
     * If a file or directory name or path matches the exclusion, it will not be copied. For example, an exclusion of
     * '.svn' will exclude all files and directories whose name is '.svn'. And an exclusion of '/a/b' will exclude
     * file or directory '$src/a/b'. Note, that '/' should be used as separator regardless of the value of the DIRECTORY_SEPARATOR constant.
     * </li>
     * <li>level: integer, recursion depth, default=-1.
     * Level -1 means copying all directories and files under the directory;
     * Level 0 means copying only the files DIRECTLY under the directory;
     * level N means copying those directories that are within N levels.
     * </li>
     * <li>newDirMode - the permission to be set for newly copied directories (defaults to 0777);</li>
     * <li>newFileMode - the permission to be set for newly copied files (defaults to the current environment setting).</li>
     * </ul>
     */
    public static function copyDirectory($src, $dst, $options = array())
    {
        $fileTypes = array();
        $exclude   = array();
        $level     = -1;
        extract($options);
        if (!is_dir($dst))
            self::mkdir($dst, $options, true);

        self::copyDirectoryRecursive($src, $dst, '', $fileTypes, $exclude, $level, $options);
    }

}

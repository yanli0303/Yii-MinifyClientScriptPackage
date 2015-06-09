<?php

namespace YiiMinifyClientScriptPackage;

class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected static $filesToRemove = array();
    protected static $dirsToRemove   = array();

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        foreach (self::$filesToRemove as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach (self::$dirsToRemove as $dir) {
            if (is_dir($dir)) {
                self::removeDirectory($dir);
            }
        }
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

    public static function copyDirectory($src, $dst)
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }

        $dir  = opendir($src);
        while (false !== ( $file = readdir($dir))) {
            if (( $file !== '.' ) && ( $file !== '..' )) {
                if (is_dir($src.'/'.$file)) {
                    self::copyDirectory($src.'/'.$file, $dst.'/'.$file);
                } else {
                    copy($src.'/'.$file, $dst.'/'.$file);
                }
            }
        }
        closedir($dir);
    }

}

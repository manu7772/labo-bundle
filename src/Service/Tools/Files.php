<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;
use SplFileInfo;
use Closure;
use Exception;
use phpDocumentor\Reflection\PseudoTypes\False_;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;

class Files extends BaseService
{

    const PROJECT_DIR = __DIR__.'/../../../../../../';
    const TEMP_DIR = 'tmp';
    const SERVER_GROUP = 'www-data';
    const SERVER_DIR_CHMOD = 0775;
    const SERVER_FIL_CHMOD = 0764;
    const UMASK = 0002;
    const APPLY_GRANTS_ENABLED = false;

        /** ***********************************************************************************
     * SYSTEM FILES
     * @see https://symfony.com/doc/current/components/filesystem.html
     * @see https://symfony.com/doc/current/components/finder.html
     *************************************************************************************/

    /**
     * Apply system grants for a file
     * @param File|string $file
     * @return boolean
     */
    public static function applyGrants(
        File|string $file,
        bool $checkfile = true
    ): bool
    {
        $check = true;
        if(static::APPLY_GRANTS_ENABLED) {
            if(@file_exists($file)) {
                $file = is_string($file) && @is_file($file) ? new File($file, $checkfile) : $file;
                $filepath = $file instanceof File ? $file->getRealPath() : $file;
                $filesystem = new Filesystem();
                $chmod = @is_dir($filepath) ? static::SERVER_DIR_CHMOD : static::SERVER_FIL_CHMOD;
                $filesystem->chown(files: $filepath, user: static::SERVER_GROUP, recursive: true);
                $filesystem->chmod(files: $filepath, mode: $chmod, umask: static::UMASK, recursive: true);
            } else {
                $filepath = $file;
            }
            if($checkfile) {
                switch (true) {
                    case !file_exists($filepath):
                        throw new Exception(vsprintf('Le fichier %s est introuvable !', [$filepath]));
                        $check = false;
                        break;
                    case !is_readable($filepath):
                        throw new Exception(vsprintf('Le fichier %s est non lisible !', [$filepath]));
                        $check = false;
                        break;                    
                    default:
                        $check = true;
                        break;
                }
            }
        }
        return $check;
    }

    /**
     * List files in Directory
     * Filter is array or Closure [ex. ->filter(static function (SplFileInfo $file) { ... })]
     * @param string $path
     * @param array|Closure|null $filter
     * @param integer $depth
     * @return array
     */
    public static function listFiles(
        string $path = null,
        array|Closure $filter = null,
        int $depth = 1,
    ): array
    {
        $finder = Finder::create()->ignoreUnreadableDirs()->files();
        $path = static::getProjectDir($path, false);
        if(!is_dir($path)) return [];
        $files = [];
        for ($i = 0; $i < $depth; $i++) {
            $finder->in($path)->depth($i);
            if($filter instanceof Closure) {
                $finder->filter($filter);
            } else if(is_array($filter) && count($filter) > 0) {
                $finder->name($filter);
            }
            $files = array_merge($files, iterator_to_array($finder, true));
        }
        return $files;
    }

    public static function listDirs(
        string $path = null,
        array|Closure $filter = null,
        int $depth = 1,
        bool|int $filter_depth = 0,
    ): array
    {
        $finder = Finder::create()->ignoreUnreadableDirs()->directories();
        $path = static::getProjectDir($path, false);
        if(!is_dir($path)) return [];
        if(is_bool($filter_depth)) $filter_depth = $filter_depth ? $depth : 0;
        if($filter_depth < 0) $filter_depth = 0;
        $dirs = [];
        for ($i = 0; $i < $depth; $i++) {
            $finder->in($path)->depth($i);
            if($i <= $filter_depth) {
                if($filter instanceof Closure) {
                    $finder->filter($filter);
                } else if(is_array($filter) && count($filter) > 0) {
                    $finder->name($filter);
                }
            }
            $dirs = array_merge($dirs, iterator_to_array($finder, true));
        }
        return $dirs;
    }

    public static function list(
        string $path = null,
        array|Closure $filter = null,
        int $depth = 1,
        bool|int $filter_depth = 0,
    ): array
    {
        $finder = Finder::create()->ignoreUnreadableDirs();
        $path = static::getProjectDir($path, false);
        if(!is_dir($path)) return [];
        if(is_bool($filter_depth)) $filter_depth = $filter_depth ? $depth : 0;
        if($filter_depth < 0) $filter_depth = 0;
        $dirs = [];
        for ($i = 0; $i < $depth; $i++) {
            $finder->in($path)->depth($i);
            if($i <= $filter_depth) {
                if($filter instanceof Closure) {
                    $finder->filter($filter);
                } else if(is_array($filter) && count($filter) > 0) {
                    $finder->name($filter);
                }
            }
            $dirs = array_merge($dirs, iterator_to_array($finder, true));
        }
        return $dirs;
    }

    // public static function nestedDirs(
    //     string $path = null,
    //     array|Closure $filter = null,
    //     int $depth = 1,
    // ): array
    // {
    //     $list = static::listDirs($path, $filter, 0);
    //     if($depth > 0) {

    //     }
    //     return $list;
    // }

    public static function getParentDir(
        string $path = null
    ): ?SplFileInfo
    {
        $path = static::getProjectDir($path, false);
        if(!is_dir($path)) return null;
        $path = new SplFileInfo($path);
        $parent = $path->getPath();
        return new SplFileInfo($parent);
    }

    public static function removePrefixSeparator(
        string &$path = null
    ): string
    {
        $path = is_string($path) ? preg_replace('/^\.?[\\/\\\\]+/', '', $path) : '';
        return (string)$path;
    }

    /**
     * Get project directory, with path if given.
     * Create path if not found (if $create is true)
     * @param string|null $path
     * @param boolean $create
     * @return string|false
     */
    public static function getProjectDir(
        string $path = null,
        bool $create = true
    ): string|false
    {
        $projectDir = realpath(static::PROJECT_DIR);
        $fullpath = is_string($path) && strlen($path) > 0 ?
            $projectDir.DIRECTORY_SEPARATOR.static::truncateProjectDirName($path):
            $projectDir;
        if($create && !@is_dir($fullpath)) {
            // Check path and create if not found
            if(!static::createPath($fullpath)) {
                throw new Exception('La création du chemin '.json_encode($fullpath).' a échoué !');
            }
        }
        return $fullpath;
    }

    public static function truncateProjectDirName(
        string $path = null
    ): string
    {
        $projectDir = static::getProjectDir();
        if(substr($path, 0, strlen($projectDir)) === $projectDir) {
            $path = Path::makeRelative($path, $projectDir);
        }
        return $path;
    }

    public static function removeFile(
        string $filepath
    ): bool
    {
        if(@file_exists($filepath)) @unlink($filepath);
        return !@file_exists($filepath);
    }

    /**
     * Remove directory recursively
     * @see https://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
     * @param string $path
     * @return boolean
     */
    public static function removeDir(
        string $path
    ): bool
    {
        $dir = static::getProjectDir($path, false);
        return static::cleanDir($dir, false) && @rmdir($dir);
    }

    public static function cleanDir(
        string $path,
        bool $keepSubDirs = false
    ): bool
    {
        $dir = static::getProjectDir($path, false);
        if (!@file_exists($dir)) {
            return true;
        }
        if (!@is_dir($dir)) {
            return static::removeFile($dir);
        }
        // $beforeScanDir = json_encode(@scandir($dir));
        foreach (@scandir($dir) as $item) {
            $subFile = $dir.DIRECTORY_SEPARATOR.$item;
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!static::cleanDir($subFile, $keepSubDirs)) return false;
            if (!$keepSubDirs && !@rmdir($subFile)) {
                return false;
            }
        }
        $subFiles = static::listFiles(path: $dir, depth: 12);
        // echo(PHP_EOL.'--> TEST DIRS '.$dir.': '.$beforeScanDir.PHP_EOL.'--> Now subfiles: '.json_encode($subFiles).' > '.(empty($subFiles) ? 'OK!!!' : 'FAILED!!!').PHP_EOL.PHP_EOL);
        return empty($subFiles);
    }

    public static function createPath(
        string $path = null
    ): string|false
    {
        $fullpath = static::getProjectDir($path, false);
        // dump($fullpath);
        if(!$fullpath) return false;
        if(!@is_dir($fullpath)) {
            // Not found: create
            $filesystem = new Filesystem();
            $filesystem->mkdir($fullpath);
        }
        if(@is_dir($fullpath)) {
            static::applyGrants($fullpath, true);
        }
        return @is_dir($fullpath) ? $fullpath : false;
    }

    /**
     * Returns file content
     * @param string $path
     * @param string $filename
     * @return string|false
     */
    public static function getFileContent(
        string $path,
        string $filename = null
    ): string|false
    {
        $projectDir = static::getProjectDir($path, false);
        if(!$projectDir) return false;
        $file = new SplFileInfo($projectDir.(empty($filename) ? '' : DIRECTORY_SEPARATOR.$filename));
        $filepath = $file->getRealPath();
        return $filepath
            ? file_get_contents($filepath)
            : false;
    }

    public static function getInFilesPhpInfo(
        string|array $paths,
        bool $onlyExists = false,
    ): array
    {
        $files = [];
        foreach ((array)$paths as $path) {
            $files[$path] = static::listFiles($path, ['*.php'], 6);
        }
        $phps = [];
        foreach ($files as $list) {
            foreach ($list as $file) {
                /** @var FinderSplFileInfo $file */
                if($file->isReadable() && $file->getRealPath() && strtolower($file->getExtension()) === 'php') {
                    $content = static::getFileContent($file->getRealPath());
                    preg_match('/^namespace\s+(.+?);$/sm', $content, $namespace);
                    if(count($namespace) === 2) {
                        $exists = false;
                        preg_match('/(class|interface|trait)\s+(\w+)(.*)?\{/sm', $content, $class);
                        if(count($class) > 2) {
                            switch (strtolower($class[1])) {
                                case 'class':
                                    // class
                                    $classtype = 'class';
                                    $classname = $namespace[1].'\\'.($class[2]);
                                    $shortname = Classes::getShortname($classname);
                                    $exists = class_exists($classname);
                                    break;
                                case 'interface':
                                    // interface
                                    $classtype = 'interface';
                                    $classname = $namespace[1].'\\'.($class[2]);
                                    $shortname = Classes::getShortname($classname);
                                    $exists = interface_exists($classname);
                                    break;
                                case 'trait':
                                    // trait
                                    $classtype = 'trait';
                                    $classname = $namespace[1].'\\'.($class[2]);
                                    $shortname = Classes::getShortname($classname);
                                    $exists = trait_exists($classname);
                                    break;
                            }
                            if(isset($classname) && (!$onlyExists || $exists)) {
                                $phps[$classname] = [
                                    'file' => $file->getRealPath(),
                                    'classname' => $classname,
                                    'shortname' => $shortname,
                                    'exists' => $exists,
                                    'type' => $classtype,
                                ];
                            }
                            unset($classname);
                        }
                    }
                }
            }
        }
        ksort($phps);
        return $phps;
    }

    /**
     * Returns file content
     * @param string $path
     * @param string $filename
     * @param mixed $content
     * @return bool
     */
    public static function putFileContent(
        string $path,
        string $filename,
        mixed $content
    ): bool
    {
        $projectDir = static::getProjectDir($path, true);
        if($projectDir) {
            $dir = new SplFileInfo($projectDir);
            $filepath = $dir->getRealPath();
            if($filepath) {
                // dd('Writing file '.$filename.' in path '.$filepath.'...');
                return !empty(file_put_contents($filepath.DIRECTORY_SEPARATOR.$filename, $content));
            }
        }
        return false;
    }


    /** TMP directory */

    public static function getTempDir(): string
    {
        return static::createPath(static::TEMP_DIR);
    }

    public static function removeTempDir(): bool
    {
        return static::removeDir(static::TEMP_DIR);
    }

    /**
     * Get UploadedFile from (copied in) tmp/
     * @param File|string $file
     * @return UploadedFile|false
     */
    public static function getCopiedTmpFile(
        File|string $file
    ): UploadedFile|false
    {
        if($file instanceof UploadedFile) return $file;
        if(is_string($file)) {
            $pdir = static::getProjectDir($file, false);
            if(!$pdir) return false;
            $file = new File($pdir, true);
        }
        $filesystem = new Filesystem();
        $source = $file->getRealPath();
        $dest = $filesystem->tempnam(dir: static::getTempDir(), prefix: pathinfo($file->getFilename(), PATHINFO_FILENAME).'_', suffix: '.'.pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        // $dest = static::getTempDir().DIRECTORY_SEPARATOR.$file->getFilename();
        if(copy($source, $dest)) {
            static::applyGrants($dest, true);
            $uf = new UploadedFile(path: $dest, originalName: $file->getFilename(), test: true);
            if($uf->isValid()) return $uf;
        }
        return false;
    }

    // public static function getUploadedFile(
    //     File|string $file,
    //     bool $normalizeName = false,
    // ): UploadedFile|false
    // {
    //     if(is_string($file)) {
    //         $file = new File($file, true);
    //     }
    //     $filename = $normalizeName
    //         ? Strings::getSlug(pathinfo($file->getFilename(), PATHINFO_FILENAME)).'.'.pathinfo($file->getFilename(), PATHINFO_EXTENSION)
    //         : $file->getFilename();
    //     return new UploadedFile(path: $file->getRealPath(), originalName: $filename, mimeType: $file->getMimeType(), test: true);
    // }

    /** Fixtures cleanup */

    public static function cleanDirsBeforeFixtures(
        array &$dirs
    ): bool
    {
        // Add tmp dir
        $dirs = array_unique(array_merge($dirs, [static::TEMP_DIR]));
        foreach ($dirs as $dir) {
            if(!static::cleanDir($dir, true)) return false;
        }
        return true;
    }

    /** YAML files */

    /**
     * Read a YAML file and return data
     * @param string|SplFileInfo $file
     * @return array|null
     */
    public static function readYamlFile(
        string|SplFileInfo $file
    ): array|null
    {
        $filepath = $file instanceof SplFileInfo ? $file->getRealPath() : $file;
        return Yaml::parse(file_get_contents($filepath));
    }

    /**
     * Create UploadedFile file from url
     * @param string $url
     * @param string $prefix
     * @param string|null $extension
     * @return UploadedFile
     */
    public static function getUploadedFileFromUrl(
        string $url,
        string $prefix,
        string $extension = null
    ): UploadedFile
    {
        $filesystem = new Filesystem();
        $tmpdir = static::getTempDir();
        $fullpath = $filesystem->tempnam($tmpdir, $prefix, $extension);
        $filesystem->dumpFile($fullpath, file_get_contents($url));
        static::applyGrants($fullpath, true);
        $file = new File($fullpath, true);
        return new UploadedFile(path: $file->getRealPath(), originalName: $file->getFilename(), test: true);
    }



}
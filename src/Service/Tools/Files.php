<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

use SplFileInfo;
use Closure;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsAlias('Tool:Files', public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class Files extends BaseService
{

    const PROJECT_DIR = __DIR__.'/../../../../../../';
    const TEMP_DIR = 'tmp';
    const SERVER_GROUP = 'www-data';
    const SERVER_DIR_CHMOD = 0775;
    const SERVER_FIL_CHMOD = 0764;
    const UMASK = 0002;
    const APPLY_GRANTS_ENABLED = false;

    public readonly string $project_dir;

    public function __construct(
        public readonly ?KernelInterface $kernel = null
    )
    {
        $this->project_dir = $this->kernel ? $this->kernel->getProjectDir() : static::PROJECT_DIR;
    }

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
    public function applyGrants(
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
    public function listFiles(
        string $path = null,
        array|Closure $filter = null,
        int $depth = 1,
    ): array
    {
        $finder = Finder::create()->ignoreUnreadableDirs()->files();
        $path = $this->getProjectDir($path, false);
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

    public function listDirs(
        string $path = null,
        array|Closure $filter = null,
        int $depth = 1,
        bool|int $filter_depth = 0,
    ): array
    {
        $finder = Finder::create()->ignoreUnreadableDirs()->directories();
        $path = $this->getProjectDir($path, false);
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

    public function list(
        string $path = null,
        array|Closure $filter = null,
        int $depth = 1,
        bool|int $filter_depth = 0,
    ): array
    {
        $finder = Finder::create()->ignoreUnreadableDirs();
        $path = $this->getProjectDir($path, false);
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

    // public function nestedDirs(
    //     string $path = null,
    //     array|Closure $filter = null,
    //     int $depth = 1,
    // ): array
    // {
    //     $list = $this->listDirs($path, $filter, 0);
    //     if($depth > 0) {

    //     }
    //     return $list;
    // }

    public function getParentDir(
        string $path = null
    ): ?SplFileInfo
    {
        $path = $this->getProjectDir($path, false);
        if(!is_dir($path)) return null;
        $path = new SplFileInfo($path);
        $parent = $path->getPath();
        return new SplFileInfo($parent);
    }

    public function removePrefixSeparator(
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
    public function getProjectDir(
        string $path = null,
        bool $create = true
    ): string|false
    {
        if(!empty($path) && file_exists($path)) return $path;
        $projectDir = realpath($this->project_dir);
        $fullpath = strlen((string)$path) > 0
            ? $projectDir.DIRECTORY_SEPARATOR.$this->truncateProjectDirName($path)
            : $projectDir;
        // dump(vsprintf('%s : Project dir : %s with path "%s" : full path : "%s" (exists: %s)', [__METHOD__, $projectDir, $path, $fullpath, @is_dir($fullpath) ? 'true' : 'false']));
        if($create && !@is_dir($fullpath)) {
            // Check path and create if not found
            if(!$this->createPath($fullpath)) {
                throw new Exception('La création du chemin '.json_encode($fullpath).' a échoué !');
            }
        }
        return $fullpath;
    }

    public function truncateProjectDirName(
        string $path = null
    ): string
    {
        $projectDir = $this->getProjectDir();
        if(substr($path, 0, strlen($projectDir)) === $projectDir) {
            $path = Path::makeRelative($path, $projectDir);
        }
        return $path;
    }

    public function removeFile(
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
    public function removeDir(
        string $path
    ): bool
    {
        $dir = $this->getProjectDir($path, false);
        return $this->cleanDir($dir, false) && @rmdir($dir);
    }

    public function cleanDir(
        string $path,
        bool $keepSubDirs = false
    ): bool
    {
        $dir = $this->getProjectDir($path, false);
        if (!@file_exists($dir)) {
            return true;
        }
        if (!@is_dir($dir)) {
            return $this->removeFile($dir);
        }
        // $beforeScanDir = json_encode(@scandir($dir));
        foreach (@scandir($dir) as $item) {
            $subFile = $dir.DIRECTORY_SEPARATOR.$item;
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->cleanDir($subFile, $keepSubDirs)) return false;
            if (!$keepSubDirs && !@rmdir($subFile)) {
                return false;
            }
        }
        $subFiles = $this->listFiles(path: $dir, depth: 12);
        // echo(PHP_EOL.'--> TEST DIRS '.$dir.': '.$beforeScanDir.PHP_EOL.'--> Now subfiles: '.json_encode($subFiles).' > '.(empty($subFiles) ? 'OK!!!' : 'FAILED!!!').PHP_EOL.PHP_EOL);
        return empty($subFiles);
    }

    public function createPath(
        string $path = null
    ): string|false
    {
        $fullpath = $this->getProjectDir($path, false);
        // dump($fullpath);
        if(!$fullpath) return false;
        if(!@is_dir($fullpath)) {
            // Not found: create
            $filesystem = new Filesystem();
            $filesystem->mkdir($fullpath);
        }
        if(@is_dir($fullpath)) {
            $this->applyGrants($fullpath, true);
        }
        return @is_dir($fullpath) ? $fullpath : false;
    }

    /**
     * Returns file content
     * @param string $path
     * @param string $filename
     * @return string|false
     */
    public function getFileContent(
        string $path,
        string $filename = null
    ): string|false
    {
        $projectDir = $this->getProjectDir($path, false);
        if(!$projectDir) return false;
        $file = new SplFileInfo($projectDir.(empty($filename) ? '' : DIRECTORY_SEPARATOR.$filename));
        $filepath = $file->getRealPath();
        return $filepath
            ? file_get_contents($filepath)
            : false;
    }

    /**
     * Returns file content
     * @param string $path
     * @param string $filename
     * @param mixed $content
     * @return bool
     */
    public function putFileContent(
        string $path,
        string $filename,
        mixed $content
    ): bool
    {
        $projectDir = $this->getProjectDir($path, true);
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

    public function getTempDir(): string
    {
        return $this->createPath(static::TEMP_DIR);
    }

    public function removeTempDir(): bool
    {
        return $this->removeDir(static::TEMP_DIR);
    }

    /**
     * Get UploadedFile from (copied in) tmp/
     * @param File|string $file
     * @return UploadedFile|false
     */
    public function getCopiedTmpFile(
        File|string $file
    ): UploadedFile|false
    {
        if($file instanceof UploadedFile) return $file;
        if(is_string($file)) {
            $pdir = $this->getProjectDir($file, false);
            if(!$pdir) return false;
            $file = new File($pdir, true);
        }
        $filesystem = new Filesystem();
        $source = $file->getRealPath();
        $dest = $filesystem->tempnam(dir: $this->getTempDir(), prefix: pathinfo($file->getFilename(), PATHINFO_FILENAME).'_', suffix: '.'.pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        // $dest = $this->getTempDir().DIRECTORY_SEPARATOR.$file->getFilename();
        if(copy($source, $dest)) {
            $this->applyGrants($dest, true);
            $uf = new UploadedFile(path: $dest, originalName: $file->getFilename(), test: true);
            if($uf->isValid()) return $uf;
        }
        return false;
    }

    // public function getUploadedFile(
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

    public function cleanDirsBeforeFixtures(
        array &$dirs
    ): bool
    {
        // Add tmp dir
        $dirs = array_unique(array_merge($dirs, [static::TEMP_DIR]));
        foreach ($dirs as $dir) {
            if(!$this->cleanDir($dir, true)) return false;
        }
        return true;
    }

    /** YAML files */

    /**
     * Read a YAML file and return data
     * @param string|SplFileInfo $file
     * @return array|null
     */
    public function readYamlFile(
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
    public function getUploadedFileFromUrl(
        string $url,
        string $prefix,
        string $extension = null
    ): UploadedFile
    {
        $filesystem = new Filesystem();
        $tmpdir = $this->getTempDir();
        $fullpath = $filesystem->tempnam($tmpdir, $prefix, $extension);
        $filesystem->dumpFile($fullpath, file_get_contents($url));
        $this->applyGrants($fullpath, true);
        $file = new File($fullpath, true);
        return new UploadedFile(path: $file->getRealPath(), originalName: $file->getFilename(), test: true);
    }

    public function getClassesFromPhpFiles(
        string $path,
        string|int|array $levels = 0,
        bool $sort_results = false,
    ): array
    {
        $classes = [];
        if(is_dir($path)) {
            $finder = Finder::create()->ignoreUnreadableDirs()->files();
            $finder->in($path);
            $finder->name('/.php$/');
            $finder->contains('/namespace\s+([\\w\\\\]+);/i'); // only files that contains a namespace
            $finder->exclude(['node_modules']);
            if(!empty($levels)) $finder->depth($levels);
            foreach ($finder as $file) {
                // echo('<div>File '.$file->getPathname().'</div>');
                $content = file_get_contents($file->getPathname());
                preg_match('/namespace\s+([\\w\\\\]+);/i', $content, $namespace);
                if(!empty($namespace) && count($namespace) > 1) {
                    // echo('<pre>'); var_dump($namespace); echo('</pre>');
                    $namespace = $namespace[1];
                    preg_match('/(class|interface|trait)\\s+(\\w+)[\\s\\r\\n]/i', $content, $class);
                    if(!empty($class) && count($class) > 2) {
                        // echo('<pre>'); var_dump($class); die('</pre>');Symfony\Component\Finder\Finder
                        $type = $class[1];
                        $classname = $namespace.'\\'.$class[2];
                        if($sort_results) {
                            $classes[$type][$classname] = [
                                'type' => $type,
                                'file' => $file->getPathname(),
                                'classname' => $classname,
                                'shortname' => $class[2],
                                'namespace' => $namespace,
                            ];
                        } else {
                            $classes[$classname] = $classname;
                        }
                    }
                }
            }
        }
        return $classes;
    }

}
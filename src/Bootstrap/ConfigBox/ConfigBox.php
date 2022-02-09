<?php

namespace YAPF\Bootstrap\ConfigBox;

use YAPF\Cache\Cache;
use YAPF\Cache\Drivers\Disk;
use YAPF\Cache\Drivers\Redis;
use YAPF\MySQLi\MysqliEnabled;

class BootstrapConfigBox extends ErrorLogging
{
    // SQL connection
    protected MysqliEnabled $sql;

    // url switchs
    protected string $page = "";
    protected string $module = "";
    protected string $option = "";
    protected string $area = "";

    // site flags
    protected string $html_title_after = "";
    protected string $url_base = "";
    protected string $html_title = "";

    // Folders
    protected string $rootfolder = "../";
    protected string $deepFolder = "../../";

    // Cache
    protected ?Cache $Cache;
    protected bool $cache_enabled = false;

    // Cache / Disk
    protected bool $use_disk_cache = false;
    protected string $disk_cache_folder = "cache";

    // Cache / Redis
    protected bool $use_redis_cache = false;

    // Cache / Redis / Unix socket
    protected bool $redisUnix = false;
    protected string $redis_socket = "/var/run/redis/redis.sock";

    // Cache / Redis / TCP
    protected string $redis_host = "redis";
    protected int $redis_port = 6379;
    protected int $redis_timeout = 3;

    // docker flag
    protected bool $dockerConfigLocked = false;

    // config Flags
    protected array $flags = [];

    public function __construct()
    {
        if (class_exists("App\\Db", false) == false) {
            $offline = [
                "status" => 0,
                "message" => "- Service offline -<br/> DB config missing",
            ];
            die(json_encode($offline));
        }
        $this->sql = new MysqliEnabled();
        $this->setFlag("SITE_NAME", "bootstrap enabled");
        $this->setFlag("SITE_URL", "http://localhost/");
        $this->setFlag("url_base", $this->getFlag("SITE_URL"));
        $this->loadURL();
        $this->loadFromDocker();
    }

    protected function loadFromDocker(): void
    {
        if (getenv('SITE_CACHE_ENABLED') !== false) {
            if (getenv('SITE_CACHE_ENABLED') == "true") {
                $this->configCacheRedisTCP(getenv("SITE_CACHE_REDIS_HOST"));
            }
            $this->dockerConfigLocked = true; // disable all config functions
            $this->startCache();
        }
    }

    /*
        Folder control
    */
    public function &getRootFolder(): string
    {
        return $this->rootfolder;
    }

    public function &getDeepFolder(): string
    {
        return $this->deepFolder;
    }

    /*
        Flag control
    */
    public function setFlag(string $envName, ?string $defaultValue): void
    {
        $allowSet = true;
        if (array_key_exists($envName, $this->flags) == true) {
            $allowSet = !$this->dockerConfigLocked;
        }
        if ($allowSet == false) {
            return;
        }
        if (getenv($envName) !== false) {
            $this->flags[$envName] = getevn($envName);
            return;
        }
        $this->flags[$envName] = $defaultValue;
    }

    public function getFlag(string $flagName): ?string
    {
        if (array_key_exists($flagName, $this->flags) == false) {
            return null;
        }
        return $this->flags[$flagName];
    }

    /*
        SQL functions
    */
    public function &getSQL(): MysqliEnabled
    {
        return $this->sql;
    }

    /*
        Cache functions
    */
    public function &getCacheDriver(): ?Cache
    {
        return $this->Cache;
    }

    public function configCacheDisabled(): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_redis_cache = false;
        $this->use_disk_cache = false;
    }
    public function configCacheRedisUnixSocket(string $socket = "/var/run/redis/redis.sock"): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_redis_cache = true;
        $this->redisUnix = true;
        $this->redis_socket = $socket;
    }
    public function configCacheRedisTCP(string $host = "redis", int $port = 6379, int $timeout = 3): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_redis_cache = true;
        $this->redisUnix = false;
        $this->redis_host = $host;
        $this->redis_port = $port;
        $this->redis_timeout = $timeout;
    }
    public function configCacheDisk(string $folder = "cache"): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_disk_cache = true;
        $this->disk_cache_folder = $folder;
    }

    public function startCache(): void
    {
        $this->Cache = null;
        if ($this->use_redis_cache == true) {
            $this->startRedisCache();
        }
        if ($this->use_disk_cache == true) {
            $this->startDiskCache();
        }
        $this->leakCache();
        return;
    }

    protected function startRedisCache(): void
    {
        $this->Cache = new Redis();
        if ($this->redisUnix == true) {
            $this->Cache->connectUnix($this->redis_socket);
            return;
        }
        $this->Cache->setTimeout($this->redis_timeout);
        $this->Cache->connectTCP($this->redis_host, $this->redis_port);
    }

    protected function leakCache(): void
    {
        global $cache; // old mode support [to be phased out]
        $cache = $this->Cache;
    }

    protected function startDiskCache(): void
    {
        $this->Cache = new Disk($this->rootfolder . "/" . $this->disk_cache_folder);
    }


    /*
        URL loading
    */

    public function &getPage(): string
    {
        return $this->page;
    }
    public function &getModule(): string
    {
        return $this->module;
    }
    public function &getOption(): string
    {
        return $this->option;
    }
    public function &getArea(): string
    {
        return $this->area;
    }

    public function setModule(string $module): void
    {
        $this->module = ucfirst($module);
    }

    public function setArea(string $area): void
    {
        $this->area = ucfirst($area);
    }

    protected function loadURL(string $process = null): void
    {
        $this->module = "";
        $this->area = "";
        $this->page = "";
        $this->option = "";

        if ($process == null) {
            if (array_key_exists("REQUEST_URI", $_SERVER) == true) {
                $process = $_SERVER['REQUEST_URI']);
            }
        }
        if ($process == null) {
            return;
        }
        $uri_parts = explode('?', $process, 2);
        $bits = array_values(array_diff(explode("/", $uri_parts[0]), [""]));
        if (count($bits) > 0) {
            if (strpos($bits[0], "php") !== false) {
                array_shift($bits);
            }
        }
        if (count($bits) == 1) {
            $this->module = urldecode($bits[0]);
        } elseif (count($bits) >= 2) {
            if (count($bits) >= 1) {
                $this->module = $bits[0 ];
            }
            if (count($bits) >= 2) {
                $this->area = $bits[1];
            }
            if (count($bits) >= 3) {
                $this->page = $bits[2];
            }
            if (count($bits) >= 4) {
                $this->option = $bits[3];
            }
        }

        $this->module = ucfirst($this->module);
        $this->area = ucfirst($this->area);
        $this->page = ucfirst($this->page);
        $this->option = ucfirst($this->option);

        if ($this->page == "") {
            $this->page = "0";
        }
    }
}

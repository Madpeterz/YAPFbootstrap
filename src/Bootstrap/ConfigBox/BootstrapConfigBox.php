<?php

namespace YAPF\Bootstrap\ConfigBox;

use YAPF\Framework\Config\SimpleConfig;
use YAPF\InputFilter\InputFilter;

class BootstrapConfigBox extends SimpleConfig
{
    // system flags
    protected bool $callWaitFor = false;
    public function enableWaitFor(): void
    {
        $this->callWaitFor = true;
    }
    public function getCallWaitFor(): bool
    {
        return $this->callWaitFor;
    }

    // url switchs
    protected string $page = "";
    protected string $module = "";
    protected string $option = "";
    protected string $area = "";
    protected array $pageUrlBits = [];

    // site flags
    protected string $html_title_after = "";
    protected string $SITE_URL = "";
    protected string $html_title = "";

    // Folders
    protected string $rootfolder = "../";
    protected string $deepFolder = "../../";

    // config Flags
    protected array $flags = [];

    public function __construct()
    {
        foreach (getenv() as $key => $value) {
            $this->setFlag($key, $value, true);
        }
        parent::__construct();
        $this->loadURL();
    }

    public function getSiteName(): string
    {
        return $this->getFlag("SITE_NAME");
    }

    public function getSiteURL(): string
    {
        return $this->getFlag("SITE_URL");
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

    public function setFolders(string $rootFolder, string $deepFolder): void
    {
        $this->rootfolder = $rootFolder;
        $this->deepFolder = $deepFolder;
    }

    /*
        Flag control
    */
    public function setFlag(string $envName, ?string $value, bool $overWrite = false): void
    {
        if (array_key_exists($envName, $this->flags) == true) {
            if ($overWrite == false) {
                return;
            }
        }
        $this->flags[$envName] = $value;
    }

    public function getFlag(string $flagName): ?string
    {
        if (array_key_exists($flagName, $this->flags) == false) {
            return null;
        }
        return $this->flags[$flagName];
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

    protected function loadURL(?string $process = null): void
    {
        $this->module = "";
        $this->area = "";
        $this->page = "";
        $this->option = "";

        if ($process == null) {
            if (array_key_exists("REQUEST_URI", $_SERVER) == true) {
                $process = $_SERVER['REQUEST_URI'];
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
                $this->module = $bits[0];
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
        $this->pageUrlBits = $bits;

        $this->module = ucfirst($this->module);
        $this->area = ucfirst($this->area);
        $this->page = ucfirst($this->page);
        $this->option = ucfirst($this->option);

        if ($this->page == "") {
            $this->page = "0";
        }
    }
}

<?php

namespace YAPF\Bootstrap\Switchboard;

use YAPF\Bootstrap\ConfigBox\BootstrapConfigBox;
use YAPF\Bootstrap\Template\View;
use YAPF\Core\ErrorControl\ErrorLogging;

abstract class Switchboard extends ErrorLogging
{
    protected BootstrapConfigBox $config;

    protected string $targetEndpoint = "";
    protected ?View $loadedObject;

    public function __construct(&BootstrapConfigBox $config)
    {
        $this->config = $config;
        $this->loadPage();
    }

    protected function accessChecks(): bool
    {
        return true;
    }

    protected function notSet(?string $input): bool
    {
        if (($input === "") || ($input === null)) {
            return true;
        }
        return false;
    }

    protected function findMasterClass(int $step = 0): ?string
    {
        $args = [
            $this->config->getModule(),
            $this->config->getArea(),
            $this->config->getPage(),
            $this->config->getOption(),
        ];
        if ($step == 4) {
            $bits = ["App","Endpoint",$this->targetEndpoint,$this->config->getModule(),"DefaultView"];
            $use_class = "\\" . implode("\\", $bits);
            if (class_exists($use_class) == false) {
                return null;
            }
            return $use_class;
        }

        $bits = ["App","Endpoint",$this->targetEndpoint];
        $loop = 0;
        while ($loop <= $step) {
            $bits[] = $args[$loop];
            $loop++;
        }
        $use_class = "\\" . implode("\\", $bits);
        if (class_exists($use_class) == false) {
            return $this->findMasterClass($step + 1);
        }
        return $use_class;
    }

    protected function loadPage(): void
    {
        if ($this->notSet($this->config->getModule()) == true) {
            $this->config->setModule("Home");
        }

        if ($this->accessChecks() == false) {
            $this->addError("failed checks");
            http_response_code(400);
            print json_encode([
                "status" => "0",
                "message" => "badly formated request",
                ]);
            return;
        }
        if (in_array($this->config->getArea(), ["","*"]) == true) {
            $this->config->setArea("DefaultView")
        }
        $use_class = $this->findMasterClass();
        if (class_exists($use_class) == false) {
            $this->addError("Unsupported request");
            print json_encode([
            "status" => "0",
            "message" => "[" . $this->config->getModule() . " | "
            . $this->config->getArea() . " | " . $this->config->getPage() . "] Unsupported",
            ]);
            http_response_code(501);
            return;
        }

        $this->loadedObject = new $use_class($this->config);
        if ($this->loadedObject->getLoadOk() == true) {
            $this->fininalize();
        }

        $this->loadedObject->getoutput();
        $statussql = $$this->loadedObject->getOutputObject()->getSwapTagBool("status");
        if (($statussql === false) || ($statussql === null)) {
            $this->config->getSQL()->flagError();
        }
    }

    protected function fininalize($obj): void
    {
        $this->loadedObject->getOutputObject()->setSwapTag("module", $this->config->getModule());
        $this->loadedObject->getOutputObject()->setSwapTag("area", $this->config->getArea());
        $this->loadedObject->process();
    }
}

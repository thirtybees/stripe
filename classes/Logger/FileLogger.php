<?php

namespace StripeModule\Logger;

use Thirtybees\Core\Error\ErrorUtils;
use Throwable;
use Tools;

class FileLogger implements Logger
{
    /**
     * @var string
     */
    private $correlationId;

    /**
     *
     */
    public function __construct()
    {
        $this->correlationId = (string)Tools::passwdGen(12);
    }


    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message)
    {
        $formattedMessage = '[' . date('H:i:s.ss') . '] [' . $this->correlationId . '] ' . rtrim($message) . "\n";
        $path = _PS_ROOT_DIR_ . '/log/stripe_' . date('Ymd') . '.log';
        file_put_contents($path, $formattedMessage, FILE_APPEND);
    }

    /**
     * @param string $message
     */
    public function error(string $message)
    {
        $this->log("ERROR: " . $message);
    }

    /**
     * @param Throwable $e
     */
    public function exception(Throwable $e)
    {
        $this->error($this->formatErrorMessage($e));
    }

    /**
     * @param Throwable $e
     *
     * @return string
     */
    protected function formatErrorMessage(Throwable $e)
    {
        if (class_exists('\Thirtybees\Core\Error\ErrorUtils')) {
            $errorDescription = ErrorUtils::describeException($e);
            return (
                $errorDescription->getErrorName() . ': ' .
                $errorDescription->getMessage() . ' in ' .
                ErrorUtils::getRelativeFile($errorDescription->getSourceFile()) . ' at line '.
                $errorDescription->getSourceLine()
            );
        }
        return $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine();
    }

    /**
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

}
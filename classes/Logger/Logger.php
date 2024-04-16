<?php

namespace StripeModule\Logger;

use Throwable;

interface Logger
{
    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message);

    /**
     * @param string $string
     *
     * @return void
     */
    public function error(string $string);

    /**
     * @param Throwable $e
     *
     * @return void
     */
    public function exception(Throwable $e);

    /**
     * @return string
     */
    public function getCorrelationId(): string;
}
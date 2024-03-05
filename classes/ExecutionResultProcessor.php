<?php

namespace StripeModule;

interface ExecutionResultProcessor
{

    /**
     * @param PaymentMetadata $metadada
     * @param string $redirectUrl
     *
     * @return void
     */
    public function processRedirect(PaymentMetadata $metadada, string $redirectUrl);


    /**
     * @param PaymentMetadata $metadata
     * @param string $template
     * @param array $params
     * @param array $js
     * @param array $css
     *
     * @return void
     */
    public function processRender(PaymentMetadata $metadata, string $template, array $params, array $js, array $css);

    /**
     * @param string[] $errors
     *
     * @return void
     */
    public function processErrors(array $errors);

}
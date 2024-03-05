<?php

namespace StripeModule;

class ExecutionResult
{
    const RESULT_TYPE_REDIRECT = 'redirect';
    const RESULT_TYPE_ERROR = 'error';
    const RESULT_TYPE_RENDER = 'render';

    /**
     * @var string
     */
    private string $resultType;

    /**
     * @var PaymentMetadata|null
     */
    private $metadata;

    /**
     * @var array
     */
    private array $data;

    /**
     * @param string $type
     * @param null $metadata
     * @param array $data
     */
    private function __construct(string $type, $metadata = null, array $data = [])
    {
        $this->resultType = $type;
        $this->metadata = $metadata;
        $this->data = $data;
    }

    /**
     * @param PaymentMetadata $metadata
     * @param string $redirectUrl
     *
     * @return static
     */
    public static function redirect(PaymentMetadata $metadata, string $redirectUrl)
    {
        return new static(static::RESULT_TYPE_REDIRECT, $metadata, [
            'redirectUrl' => $redirectUrl
        ]);
    }

    /**
     * @param PaymentMetadata $metadata
     * @param string $template
     * @param array $templateParams
     * @param array $js
     * @param array $css
     *
     * @return static
     */
    public static function render(PaymentMetadata $metadata, string $template, array $templateParams = [], array $js = [], array $css = [])
    {
        return new static(static::RESULT_TYPE_RENDER, $metadata, [
            'template' => $template,
            'params' => $templateParams,
            'js' => $js,
            'css' => $css
        ]);
    }

    /**
     * @param string $error
     *
     * @return ExecutionResult
     */
    public static function error(string $error)
    {
        return new static(static::RESULT_TYPE_ERROR, null, [
            $error
        ]);
    }

    /**
     * @param ExecutionResultProcessor $processor
     *
     * @return $this
     */
    public function processResult(ExecutionResultProcessor $processor)
    {
        try {
            switch ($this->resultType) {
                case static::RESULT_TYPE_REDIRECT:
                    $processor->processRedirect($this->metadata, $this->data['redirectUrl']);
                    break;

                case static::RESULT_TYPE_RENDER:
                    $processor->processRender(
                        $this->metadata,
                        $this->data['template'],
                        $this->data['params'],
                        $this->data['js'],
                        $this->data['css'],
                    );
                    break;

                case static::RESULT_TYPE_ERROR:
                    // no-op-, errors will be handled laer
                    break;
                default:
                    throw new \RuntimeException("Invariant exception");
            }
        } catch (\Throwable $e) {
            if ($this->resultType !== static::RESULT_TYPE_ERROR) {
                $this->resultType = static::RESULT_TYPE_ERROR;
                $this->data = [ "Error during processing result" ];
            } else {
                $this->data[] = "Error during processing result";
            }

        }

        if ($this->resultType === static::RESULT_TYPE_ERROR) {
            $processor->processErrors($this->data);
        }
        return $this;
    }

}
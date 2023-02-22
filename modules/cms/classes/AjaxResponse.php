<?php namespace Cms\Classes;

use Illuminate\Http\Response;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ValidationException;
use Illuminate\Http\RedirectResponse;
use ArrayAccess;

/**
 * AjaxResponse
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class AjaxResponse extends Response implements ArrayAccess
{
    /**
     * @var array vars are variables included with the result
     */
    public $vars = [];

    /**
     * @var string ajaxRedirectUrl
     */
    protected $ajaxRedirectUrl;

    /**
     * @var array ajaxFlashMessages
     */
    protected $ajaxFlashMessages;

    /**
     * addHandlerVars
     */
    public function addHandlerVars($vars): static
    {
        $this->vars = (array) $vars;

        return $this;
    }

    /**
     * addFlashMessages
     */
    public function addFlashMessages($messages): static
    {
        $this->ajaxFlashMessages = $messages;

        return $this;
    }

    /**
     * setContent captures the variables from a handler and merges any resulting data
     */
    public function setHandlerResponse($content): static
    {
        if ($content instanceof RedirectResponse) {
            $this->setAjaxRedirect($content);
        }

        if (is_string($content)) {
            $content = ['result' => $content];
        }

        if (is_array($content)) {
            $this->vars = $content  + $this->vars;
        }

        $response = [
            'data' => $this->vars
        ];

        if ($this->ajaxRedirectUrl) {
            $response['redirect'] = $this->ajaxRedirectUrl;
        }

        if ($this->ajaxFlashMessages) {
            $response['flash'] = $this->ajaxFlashMessages;
        }

        $this->setContent($response);

        return $this;
    }

    /**
     * setException
     */
    public function setHandlerException($exception): static
    {
        $this->exception = $exception;

        $error = [];
        $error['message'] = $exception->getMessage();

        if ($exception instanceof ValidationException) {
            $this->setStatusCode(422);
            $error['fields'] = $exception->getFields();
        }
        elseif ($exception instanceof ApplicationException) {
            $this->setStatusCode(400);
        }
        else {
            $this->setStatusCode(500);
        }

        $this->setContent([
            'error' => $error
        ]);

        return $this;
    }

    /**
     * isAjaxRedirect
     */
    public function isAjaxRedirect(): bool
    {
        return $this->ajaxRedirectUrl !== null;
    }

    /**
     * getRedirectUrl
     */
    public function getAjaxRedirectUrl(): string
    {
        return $this->ajaxRedirectUrl;
    }

    /**
     * setRedirectUrl
     */
    public function setAjaxRedirect($response)
    {
        $this->ajaxRedirectUrl = $response->getTargetUrl();
    }

    /**
     * offsetExists implementation
     */
    public function offsetExists($offset): bool
    {
        return isset($this->original[$offset]);
    }

    /**
     * offsetSet implementation
     */
    public function offsetSet($offset, $value): void
    {
        $this->original[$offset] = $value;
    }

    /**
     * offsetUnset implementation
     */
    public function offsetUnset($offset): void
    {
        unset($this->original[$offset]);
    }

    /**
     * offsetGet implementation
     */
    public function offsetGet($offset): mixed
    {
        return $this->original[$offset] ?? null;
    }
}

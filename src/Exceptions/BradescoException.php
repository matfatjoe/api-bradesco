<?php

namespace Matfatjoe\ApiBradesco\Exceptions;

use Exception;

class BradescoException extends Exception
{
    protected $requestParameters;

    /**
     * Get the value of requestParameters
     */
    public function getRequestParameters()
    {
        return $this->requestParameters;
    }

    /**
     * Set the value of requestParameters
     */
    public function setRequestParameters($request): self
    {
        $this->requestParameters = [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'headers' => $request->getHeaders(),
            'body' => (string)$request->getBody(),
        ];

        return $this;
    }
}

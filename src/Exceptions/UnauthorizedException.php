<?php

namespace Matfatjoe\ApiBradesco\Exceptions;

use Matfatjoe\ApiBradesco\Exceptions\BradescoException;

class UnauthorizedException extends BradescoException
{
    const HTTP_STATUS_CODE = 401;

    protected $bodyContent;

    public function getStatusCode()
    {
        return self::HTTP_STATUS_CODE;
    }

    /**
     * Get the value of bodyContent
     */
    public function getBodyContent()
    {
        return $this->bodyContent;
    }

    /**
     * Set the value of bodyContent
     */
    public function setBodyContent($bodyContent): self
    {
        $this->bodyContent = $bodyContent;

        return $this;
    }
}

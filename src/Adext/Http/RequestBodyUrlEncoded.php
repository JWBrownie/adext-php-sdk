<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 12:57 PM
 */

namespace Adext\Http;


class RequestBodyUrlEncoded implements RequestBodyInterface
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function getBody()
    {
        return http_build_query($this->params, null, '&');
    }
}
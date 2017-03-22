<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 12:52 PM
 */

namespace Adext\Http;


class RequestBodyMultipart implements RequestBodyInterface
{
    protected $boundary;
    protected $params;
    protected $files;

    public function __construct(array $params = [], array $files = [], $boundary = null)
    {
        $this->params = $params;
        $this->files = $files;
        $this->boundary = $boundary ?: uniqid();
    }
    public function getBody()
    {
        // TODO: Implement getBody() method.
    }
}
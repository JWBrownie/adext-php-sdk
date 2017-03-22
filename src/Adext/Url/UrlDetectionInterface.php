<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 1:09 PM
 */

namespace Adext\Url;


interface UrlDetectionInterface
{
    /**
     * The current active URL
     * @return string
     */
    public function getCurrentUrl();
}
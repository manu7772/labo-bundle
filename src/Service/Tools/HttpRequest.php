<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;

class HttpRequest extends BaseService
{

    /*************************************************************************************
     * CLI
     *************************************************************************************/

    /**
     * Is command line
     * @see PHP php_sapi_name() or PHP_SAPI
     */
    public static function isCli(): bool
    {
        return strtolower(PHP_SAPI) === 'cli';
    }



}
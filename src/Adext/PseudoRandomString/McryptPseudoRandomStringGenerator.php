<?php
namespace Adext\PseudoRandomString;

use Adext\Exceptions\AdextSDKException;

class McryptPseudoRandomStringGenerator implements PseudoRandomStringGeneratorInterface
{
    use PseudoRandomStringGeneratorTrait;

    /**
     * @const string The error message when generating the string fails.
     */
    const ERROR_MESSAGE = 'Unable to generate a cryptographically secure pseudo-random string from random_bytes(). ';

    /**
     * @throws AdextSDKException
     */
    public function __construct()
    {
        if (!function_exists('random_bytes')) {
            throw new AdextSDKException(
                static::ERROR_MESSAGE .
                'The function random_bytes() does not exist.'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getPseudoRandomString($length)
    {
        $this->validateLength($length);

        $binaryString = random_bytes($length);

        if ($binaryString === false) {
            throw new AdextSDKException(
                static::ERROR_MESSAGE .
                'random_bytes() returned an error.'
            );
        }

        return $this->binToHex($binaryString, $length);
    }
}

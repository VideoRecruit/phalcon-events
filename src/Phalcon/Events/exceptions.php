<?php

namespace VideoRecruit\Phalcon\Events;

/**
 * Common exception interface.
 */
interface Exception
{
}

/**
 * Class InvalidArgumentException
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}

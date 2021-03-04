<?php

namespace Contributte\Aop;

use LogicException;
use RuntimeException;

interface Exception
{

}



class InvalidStateException extends RuntimeException implements Exception
{

}



class CompilationException extends InvalidStateException
{

}



class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}



class UnexpectedValueException extends \UnexpectedValueException implements Exception
{

}



class InvalidAspectExceptions extends LogicException implements Exception
{

}



class NoRulesExceptions extends InvalidStateException implements Exception
{

}



class ParserException extends LogicException implements Exception
{

}



class NotImplementedException extends LogicException implements Exception
{

}

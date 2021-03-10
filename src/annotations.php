<?php declare(strict_types = 1); // phpcs:ignoreFile

namespace Contributte\Aop;

use Doctrine;

/**
 * @property string $value
 */
interface Annotation
{

}



interface AdviceAnnotation extends Annotation
{

}



abstract class BaseAnnotation extends Doctrine\Common\Annotations\Annotation implements Annotation
{

	/**
	 * @return string
	 */
	public static function getClassName()
	{
		return static::class;
	}

}



/**
 * @Annotation
 * @Target("CLASS")
 */
class Aspect extends BaseAnnotation
{

}



/**
 * @Annotation
 * @Target("METHOD")
 */
class Before extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @Annotation
 * @Target("METHOD")
 */
class AfterReturning extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @Annotation
 * @Target("METHOD")
 */
class AfterThrowing extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @Annotation
 * @Target("METHOD")
 */
class After extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @Annotation
 * @Target("METHOD")
 */
class Around extends BaseAnnotation implements AdviceAnnotation
{

}



/**
 * @Annotation
 * @Target({"METHOD", "CLASS", "PROPERTY"})
 */
class Introduce extends BaseAnnotation implements AdviceAnnotation
{

}

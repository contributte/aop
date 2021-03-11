<?php declare(strict_types = 1);

namespace Contributte\Aop\Annotations;

use Doctrine;

abstract class BaseAnnotation extends Doctrine\Common\Annotations\Annotation implements Annotation
{

	public static function getClassName(): string
	{
		return static::class;
	}

}

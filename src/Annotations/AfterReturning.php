<?php declare(strict_types = 1);

namespace Contributte\Aop\Annotations;

/**
 * @Annotation
 * @Target("METHOD")
 */
class AfterReturning extends BaseAnnotation implements AdviceAnnotation
{

}

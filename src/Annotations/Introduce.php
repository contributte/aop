<?php declare(strict_types = 1);

namespace Contributte\Aop\Annotations;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS", "PROPERTY"})
 */
class Introduce extends BaseAnnotation implements AdviceAnnotation
{

}

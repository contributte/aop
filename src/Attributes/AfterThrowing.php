<?php declare(strict_types = 1);

namespace Contributte\Aop\Attributes;

use Attribute;

/**
 * @Target("METHOD")
 */
#[Attribute]
class AfterThrowing extends BaseAttribute implements AdviceAttribute
{

}

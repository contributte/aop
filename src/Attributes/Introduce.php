<?php declare(strict_types = 1);

namespace Contributte\Aop\Attributes;

use Attribute;

/**
 * @Target({"METHOD", "CLASS", "PROPERTY"})
 */
#[Attribute]
class Introduce extends BaseAttribute implements AdviceAttribute
{

}

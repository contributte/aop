<?php declare(strict_types = 1);

namespace Contributte\Aop\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class Introduce extends BaseAttribute implements AdviceAttribute
{

}

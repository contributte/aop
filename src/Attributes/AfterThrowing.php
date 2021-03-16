<?php declare(strict_types = 1);

namespace Contributte\Aop\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AfterThrowing extends BaseAttribute implements AdviceAttribute
{

}

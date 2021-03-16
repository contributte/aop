<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

use Attribute;
use Contributte\Aop\Attributes\BaseAttribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Test extends BaseAttribute
{

}

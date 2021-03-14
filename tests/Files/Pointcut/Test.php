<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

use Attribute;
use Contributte\Aop\Attributes\BaseAttribute;

/**
 * @Target({"CLASS", "METHOD"})
 */
#[Attribute]
class Test extends BaseAttribute
{

}

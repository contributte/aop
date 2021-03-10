<?php declare(strict_types = 1);

namespace Tests\Files\Pointcut;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Test extends Annotation
{

}

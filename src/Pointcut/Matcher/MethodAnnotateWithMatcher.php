<?php


namespace Contributte\Aop\Pointcut\Matcher;

use Doctrine\Common\Annotations\Reader;

use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class MethodAnnotateWithMatcher implements \Contributte\Aop\Pointcut\Filter
{

	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $annotationClass;

	/**
	 * @var \Doctrine\Common\Annotations\Reader
	 */
	private $reader;



	public function __construct($annotationClass, Reader $reader)
	{
		$this->annotationClass = $annotationClass;
		$this->reader = $reader;
	}



	public function matches(\Contributte\Aop\Pointcut\Method $method): bool
	{
		foreach ($method->getAnnotations($this->reader) as $annotation) {
			if (!$annotation instanceof $this->annotationClass) {
				continue;
			}

			return TRUE;
		}

		return FALSE;
	}



	/**
	 * @return array|bool
	 */
	public function listAcceptedTypes()
	{
		return FALSE;
	}

}

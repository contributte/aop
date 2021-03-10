<?php declare(strict_types = 1);

namespace Contributte\Aop\DI;

use Contributte\Aop\UnexpectedValueException;
use Nette;
use stdClass;

class AspectsConfig
{

	use Nette\SmartObject;

	/** @var array */
	private $aspectsList;

	/** @var bool */
	private $prefix = true;

	public function __construct(array $aspectsList)
	{
		$this->aspectsList = $aspectsList;
	}



	public function disablePrefixing(): self
	{
		$this->prefix = false;
		return $this;
	}



	public function load(Nette\DI\Compiler $compiler, Nette\DI\ContainerBuilder $containerBuilder): void
	{
		foreach ($this->aspectsList as $def) {
			if ( (!is_array($def)) && !is_string($def) && (!$def instanceof stdClass || empty($def->value)) && !$def instanceof Nette\DI\Statement) {
				$serialised = Nette\Utils\Json::encode($def);
				throw new UnexpectedValueException('The service definition ' . $serialised . ' is expected to be an array or Neon entity.');
			}

			$definition = new Nette\DI\Definitions\ServiceDefinition();
			$definition->setFactory(is_array($def) ? $def['class'] : $def);
			$definition->setTags([AspectsExtension::ASPECT_TAG => true]);
			$containerBuilder->addDefinition(null, $definition);
		}
	}

}

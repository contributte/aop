<?php


namespace Contributte\Aop\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

use Contributte\Aop\Pointcut;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AspectsExtension extends Nette\DI\CompilerExtension
{
	const ASPECT_TAG = 'contributte.aspect';


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$config = new AspectsConfig($this->getConfig(), $this);
		$config->disablePrefixing()->load($this->compiler, $builder);

		foreach ($this->compiler->getExtensions() as $extension) {
			if (!$extension instanceof IAspectsProvider) {
				continue;
			}

			if (!($config = $extension->getAspectsConfiguration()) || !$config instanceof AspectsConfig) {
				$refl = Nette\Reflection\Method::from($extension, 'getAspectsConfiguration');
				$given = is_object($config) ? 'instance of ' . get_class($config) : gettype($config);
				throw new \Contributte\Aop\UnexpectedValueException("Method $refl is expected to return instance of Contributte\\Aop\\DI\\AspectsConfig, but $given given.");
			}

			$config->load($this->compiler, $builder);
		}
	}



	/**
	 * @param string $configFile
	 * @param Nette\DI\CompilerExtension $extension
	 * @return AspectsConfig
	 */
	public static function loadAspects($configFile, Nette\DI\CompilerExtension $extension)
	{
		return new AspectsConfig($extension->loadFromFile($configFile), $extension);
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('aspects', new AspectsExtension());
		};
	}

}

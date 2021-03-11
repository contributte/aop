<?php declare(strict_types = 1);

namespace Contributte\Aop\DI;

use Nette;

class AspectsExtension extends Nette\DI\CompilerExtension
{

	public const ASPECT_TAG = 'contributte.aspect';

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$config = new AspectsConfig((array) $this->getConfig());
		$config->load($this->compiler, $builder);

		foreach ($this->compiler->getExtensions() as $extension) {
			if (!$extension instanceof IAspectsProvider) {
				continue;
			}

			$config->load($this->compiler, $builder);
		}
	}



	public static function loadAspects(string $configFile, Nette\DI\CompilerExtension $extension): AspectsConfig
	{
		return new AspectsConfig($extension->loadFromFile($configFile));
	}



	public static function register(Nette\Configurator $configurator): void
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('aspects', new AspectsExtension());
		};
	}

}

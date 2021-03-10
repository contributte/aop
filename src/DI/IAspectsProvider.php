<?php declare(strict_types = 1);

namespace Contributte\Aop\DI;

/**
 * Implement this interface to your CompilerExtension if you want it to provide aspects.
 *
 * Example:
 * <code>
 * class AclExtension extends Nette\DI\CompilerExtension implements \Contributte\Aop\DI\IAspectsProvider
 * {
 *     public function getAspectsConfiguration()
 *     {
 *         return \Contributte\Aop\DI\AspectsExtension::loadAspects(__DIR__ . '/aspects.neon', $this);
 *     }
 * }
 * </code>
 *
 * The `aspects.neon` file should be list of unnamed services
 */
interface IAspectsProvider
{

	public function getAspectsConfiguration(): AspectsConfig;

}

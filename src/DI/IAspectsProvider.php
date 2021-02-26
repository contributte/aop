<?php


namespace Contributte\Aop\DI;


use Nette;



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
 *
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
interface IAspectsProvider
{

	function getAspectsConfiguration(): AspectsConfig;

}

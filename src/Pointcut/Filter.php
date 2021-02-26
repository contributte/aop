<?php


namespace Contributte\Aop\Pointcut;


use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface Filter
{

	/**
	 * Analyzes method if it can be accepted.
	 */
	function matches(Method $method): bool;



	/**
	 * Tries to figure out types, that could be used for searching in ContainerBuilder.
	 * Pre-filtering of services should increase speed of filtering.
	 *
	 * @return array|bool
	 */
	function listAcceptedTypes();

}

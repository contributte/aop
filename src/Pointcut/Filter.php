<?php declare(strict_types = 1);

namespace Contributte\Aop\Pointcut;

interface Filter
{

	/**
	 * Analyzes method if it can be accepted.
	 */
	public function matches(Method $method): bool;



	/**
	 * Tries to figure out types, that could be used for searching in ContainerBuilder.
	 * Pre-filtering of services should increase speed of filtering.
	 *
	 * @return string[]
	 */
	public function listAcceptedTypes(): array;

}

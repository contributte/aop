<?php declare(strict_types = 1);

namespace Tests\Files\Annotations;

/**
 * @Annotation
 */
class TestAnnotation
{

	public ?int $seconds;

	public ?string $key;

	/**
	 * @param mixed[] $params
	 * @codeCoverageIgnore
	 */
	public function __construct(array $params)
	{
		$this->seconds = $params['seconds'] ?? null;
		$this->key = $params['key'] ?? null;
	}

}

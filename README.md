![](https://heatbadger.now.sh/github/readme/contributte/aop/)

<p align=center>
  <a href="https://github.com/contributte/aop/actions"><img src="https://github.com/contributte/aop/workflows/build/badge.svg"></a>
  <a href="https://codecov.io/gh/contributte/aop"><img src="https://badgen.net/codecov/c/github/contributte/aop"></a>
  <a href="https://packagist.org/packages/contributte/aop"><img src="https://badgen.net/packagist/dm/contributte/aop"></a>
  <a href="https://packagist.org/packages/contributte/aop"><img src="https://badgen.net/packagist/v/contributte/aop"></a>
</p>
<p align=center>
  <a href="https://packagist.org/packages/contributte/aop"><img src="https://badgen.net/packagist/php/contributte/aop"></a>
  <a href="https://github.com/contributte/aop"><img src="https://badgen.net/github/license/contributte/aop"></a>
  <a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
  <a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
  <a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

Aspect oriented programming support for [Nette Framework](https://nette.org/) DI container.

## Versions

All three versions are currently maintained. Use a version supported by your current PHP version and dependencies. Version 3 uses PHP 8 attributes and does not need Doctrine annotations, so it is highly advised for PHP 8 projects.

| State  | Version | Branch   | Nette | PHP     | README |
|--------|---------|----------|-------|---------|--------|
| stable | `^3.0` | `master` | 3.0+  | `>=8.0` | [README-v3](.docs/README-v3.md) |
| stable | `^2.0` | `v2`     | 3.0+  | `>=7.4` | [README-v2](.docs/README-v2.md) |
| stable | `^1.0` | `v1`     | 3.0+  | `>=7.2` | [README-v1](.docs/README-v1.md) |

## Content

- [Installation](#installation)
- [Configuration](#configuration)
- [Upgrade to v3](#upgrade-to-v3)
- [Dictionary](#dictionary)
- [Advice types](#advice-types)
- [Pointcut syntax](#pointcut-syntax)
- [Join points](#join-points)
- [Aspect examples](#aspect-examples)
- [Development](#development)

## Installation

To install latest version of `contributte/aop` use [Composer](https://getcomposer.org).

```bash
composer require contributte/aop
```

Enable the extension using your neon config.

```neon
extensions:
	aop: Contributte\Aop\DI\AopExtension
	aspects: Contributte\Aop\DI\AspectsExtension
```

## Configuration

This extension creates new configuration section `aspects` and it should behave exactly like services, but all the services are marked as aspects.

```neon
aspects:
	- MyApp\LoggingAspect(@dep, %param%)
```

> Never give the aspects any names, keep them anonymous.

This internally works exactly like `services` section, but it tags all the aspects with `contributte.aspect` tag.
So if you don't want to, or cannot use the section, just tag the aspect and you're good to go.

### IAspectsProvider

Implement this interface to your CompilerExtension if you want it to provide aspects. Example:

```php
class AclExtension extends Nette\DI\CompilerExtension implements \Contributte\Aop\DI\IAspectsProvider
{
	public function getAspectsConfiguration()
	{
		return \Contributte\Aop\DI\AspectsExtension::loadAspects(__DIR__ . '/aspects.neon', $this);
	}
}
```

The `aspects.neon` file should be list of unnamed services as in `aspects` section.

### AspectsExtension

> There are two extensions?!

Yeah, why not? I needed the section `aspects` for services and section `aop` for configuration.

## Upgrade to v3

There are few breaking changes you have to deal with when upgrading to v3 from v2.

- Dropped [nette/reflection](https://github.com/nette/reflection), so all methods that were returning Nette\Reflection objects are now returning raw PHP \Reflection* objects.
- Dropped [nettrine/annotations](https://github.com/contributte/doctrine-annotations), we're using native PHP attributes now.

## Dictionary

<dl>
	<dt>Aspect</dt>
	<dd>The object that extends behaviour of other objects</dd>

	<dt>Advice</dt>
	<dd>The action that is taken when you're extending the behaviour. You can read it as "The object Application is being advised by Logger Aspect"</dd>

	<dt>Join point</dt>
	<dd>The exact moment at the runtime, where your advice is connected.</dd>

	<dt>Pointcut</dt>
	<dd>The special syntax for join point definition</dd>
</dl>

## Advice types

<dl>
	<dt>Before</dt>
	<dd>This advice can be used for reading/logging of method arguments, or their modification</dd>

	<dt>After</dt>
	<dd>Think of this as the `finally` keyword, it should get called even if the method throws, but it cannot change what is returned.</dd>

	<dt>After returning</dt>
	<dd>You can read/log or modify the return value here</dd>

	<dt>After throwing</dt>
	<dd>You can read the exception here</dd>

	<dt>Around</dt>
	<dd>The most powerful advice, if it's defined, it can prevent the original method from being called, change its arguments or return value, even the exception.</dd>
</dl>

> Choose wisely, great powers comes with a (performance) cost.

## Pointcut syntax

### method(`[public|protected] ns\class->method(argument == value)`)

Examples:

- `method(public Nette\Application\Application->processRequest())`
- `method(Nette\Application\UI\Presenter->[render|action|handle]*())` - should match all three variants of methods, meaning all `render*()`, `action*()` and `handle*()` (presenters have to be registered in DIC)
- `method(Nette\Application\UI\*->[handle]*())` - should match all `Presenter`, `Control` and `PresenterComponent` signals (they have to be registered in DIC)
- `method(*->*())` - matches all methods of all services in DIC - **You should never do this, it would be really painful!!!**

> Keep those conditions as simple as possible! The more complex they are, the longer it will take to compile!

The arguments are evaluated at runtime, read more at `evaluate` pointcut.

### class(`ns\class`)

Examples:

- `class(Nette\Application\UI\Presenter)`
- `class(Nette\Application\IPresenter)` - yeah, it can match also interfaces or parent classes
- `class(Nette\Application\UI\*)` - matches all classes in namespace `Nette\Application\UI`
- `class(*)` - matches all classes - **You should never do this, it would be really painful!!!**

> Keep in mind, that exact class name can be be optimized, to analyze only those services, that matches it exactly.
> When you use wildmark, all the services has to be scanned it they match and this can literary kill your application in development mode!

### within(`ns\class`)

This is basically an alias for `class` pointcut. But it expresses better the nature of this pointcut.
It scans all the types of the service and if it implements an interface, or one of parent classes matches, than this will also match.

### filter(`filterClass`)

Argument of this pointcut should be name of class that implements `Contributte\Aop\Pointcut\Filter`.
You can basically write your own pointcut filter here.

### setting(`%foo.bar% == TRUE`)

Wanna have the ability to turn on and off the advices based on DIC parameters? No problem!

### evaluate(`this.foo.bar == TRUE`)

This is really advanced runtime pointcut and it's also used in method arguments.
What does it mean, runtime? Well, it's serialised to condition and every time you run the method, the condition is evaluated and decides it the advice gets called.

Examples:

- `evaluate($argument == 1)` - this is for arguments matching
- `evaluate(this.dave.lister[kryten] == TRUE)` - this translates to $this->dave->lister['kryten'] but it's little smarter than that, have a look at [Symfony/PropertyAccess](https://symfony.com/doc/current/components/property_access.html), it's used for (surprisingly) property access.
- `evaluate(context.httpRequest.post == TRUE)` - this is translated to `$context->getService('httpRequest')->isPost()` but also here, for property access is used `Symfony/PropertyAccess`
- `evaluate(context.Nette\Http\IRequest.post == TRUE)` - this is translated to `$context->getByType('Nette\Http\IRequest')->isPost()`
- `evaluate(%foo.bar% == TRUE)` - you can write this, but it's a nonsense, there is `setting` pointcut for DIC parameters

And don't forget to have a look at [Symfony/PropertyAccess](https://symfony.com/doc/current/components/property_access.html).

### classAttributedWith(`Some\Attribute`)

Matches all classes, that have this attribute.

### methodAttributedWith(`Some\Attribute`)

Matches all methods, that have this attribute.

## Join points

When join point is invoked, an instance of concrete JoinPoint class is also created and passed to your advice (the method on aspect).
For every type of advice, there is a join point class in namespace `Contributte\Aop\JoinPoint`.

- `BeforeMethod` provides arguments and can be used for arguments modification
- `AfterMethod` provides return value or exception
- `AfterReturning` provides return value and can be used for its modification
- `AfterThrowing` provides the exception (only if there is some thrown)
- `AroundMethod` provides everything and allows you to change it completely

## Aspect examples

Let's utilize what we've learned so far and write some aspects.

```php
use Contributte\Aop;

class BeforeAspect
{
	private $db;

	public function __construct(Contributte\Doctrine\Connection $db)
	{
		$this->db = $db;
	}

	#[Aop\Attributes\Before("method(CommonService->magic)")]
	public function log(Aop\JoinPoint\BeforeMethod $before)
	{
		$this->db->insert('log', ['something' => $before->arguments[1]]);
		$before->setArgument(1, "changed value");
	}
}
```

This aspect will add an advice `log` to method `magic` of class `CommonService`, that will log its second argument (index `1`) and always change it.

```php
use Contributte\Aop;

class AroundAspect
{
	#[Aop\Attributes\Around("method(CommonService->magic)")]
	public function log(Aop\JoinPoint\AroundMethod $around)
	{
		// I can change the arguments here

		$result = $around->proceed();

		// I can change the result here

		return $result;
	}
}
```

In around aspect, you must manually call the method `->proceed()` which will either invoke another around advice in chain, or the method itself. You can never know and you shouldn't even care.

## Development

See [how to contribute](https://contributte.org/contributing.html) to this package.

This package is currently maintaining by these authors.

<a href="https://github.com/dakorpar">
 <img width="80" height="80" src="https://avatars0.githubusercontent.com/u/9303856?v=3&s=80">
</a>

-----

Inspired by [Filip Procházka](https://github.com/fprochazka) package [kdyby/Aop](https://github.com/Kdyby/Aop)

-----


Consider to [support](https://contributte.org/partners.html) **contributte** development team.
Also thank you for using this package.

<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use Closure;
use DateTime;
use DateTimeImmutable;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CallableType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class HasMethodTypeTest extends PHPStanTestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new HasMethodType('format'),
				new HasMethodType('format'),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('format'),
				new HasMethodType('FORMAT'),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('format'),
				new HasMethodType('lorem'),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('format'),
				new ObjectType(DateTimeImmutable::class),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('foo'),
				new ObjectType('UnknownClass'),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('foo'),
				new ObjectType(Closure::class),
				TrinaryLogic::createNo(),
			],
			[
				new HasMethodType('foo'),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('foo'),
				new HasMethodType('bar'),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('foo'),
				new HasPropertyType('bar'),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('foo'),
				new IterableType(new MixedType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('foo'),
				new CallableType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('__invoke'),
				new CallableType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('format'),
				new UnionType([
					new ObjectType(DateTimeImmutable::class),
					new ObjectType(DateTime::class),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('format'),
				new UnionType([
					new ObjectType(DateTimeImmutable::class),
					new ObjectType('UnknownClass'),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('format'),
				new UnionType([
					new ObjectType(DateTimeImmutable::class),
					new ObjectType(Closure::class),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('format'),
				new IntersectionType([
					new ObjectType(DateTimeImmutable::class),
					new IterableType(new MixedType(), new MixedType()),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('format'),
				new IntersectionType([
					new ObjectType('UnknownClass'),
					new HasMethodType('format'),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('format'),
				new IntersectionType([
					new ObjectWithoutClassType(),
					new HasMethodType('format'),
				]),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(HasMethodType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function dataIsSubTypeOf(): array
	{
		return [
			[
				new HasMethodType('foo'),
				new HasMethodType('foo'),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('foo'),
				new UnionType([
					new HasMethodType('foo'),
					new NullType(),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new HasMethodType('foo'),
				new IntersectionType([
					new HasMethodType('foo'),
					new HasMethodType('bar'),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasMethodType('format'),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(), // an intentional imprecision
			],
			[
				new HasMethodType('format'),
				new ObjectType(DateTimeImmutable::class),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOf(HasMethodType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * @dataProvider dataIsSubTypeOf
	 */
	public function testIsSubTypeOfInversed(HasMethodType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $otherType->isSuperTypeOf($type);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

}

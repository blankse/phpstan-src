<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
final class NonexistentOffsetInArrayDimFetchRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck,
		private bool $reportMaybes,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrayDimFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->dim !== null) {
			$dimType = $scope->getType($node->dim);
			$unknownClassPattern = sprintf('Access to offset %s on an unknown class %%s.', SprintfHelper::escapeFormatString($dimType->describe(VerbosityLevel::value())));
		} else {
			$dimType = null;
			$unknownClassPattern = 'Access to an offset on an unknown class %s.';
		}

		$isOffsetAccessibleTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->var),
			$unknownClassPattern,
			static fn (Type $type): bool => $type->isOffsetAccessible()->yes(),
		);
		$isOffsetAccessibleType = $isOffsetAccessibleTypeResult->getType();
		if ($isOffsetAccessibleType instanceof ErrorType) {
			return $isOffsetAccessibleTypeResult->getUnknownClassErrors();
		}

		if ($scope->hasExpressionType($node)->yes()) {
			return [];
		}

		$isOffsetAccessible = $isOffsetAccessibleType->isOffsetAccessible();

		if ($scope->isInExpressionAssign($node) && $isOffsetAccessible->yes()) {
			return [];
		}

		if ($scope->isUndefinedExpressionAllowed($node) && $isOffsetAccessibleType->isOffsetAccessLegal()->yes()) {
			return [];
		}

		if (!$isOffsetAccessible->yes()) {
			if ($isOffsetAccessible->no() || $this->reportMaybes) {
				if ($dimType !== null) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Cannot access offset %s on %s.',
							$dimType->describe(count($dimType->getConstantStrings()) > 0 ? VerbosityLevel::precise() : VerbosityLevel::value()),
							$isOffsetAccessibleType->describe(VerbosityLevel::value()),
						))->identifier('offsetAccess.nonOffsetAccessible')->build(),
					];
				}

				return [
					RuleErrorBuilder::message(sprintf(
						'Cannot access an offset on %s.',
						$isOffsetAccessibleType->describe(VerbosityLevel::typeOnly()),
					))->identifier('offsetAccess.nonOffsetAccessible')->build(),
				];
			}

			return [];
		}

		if ($dimType === null) {
			return [];
		}

		return $this->nonexistentOffsetInArrayDimFetchCheck->check(
			$scope,
			$node->var,
			$unknownClassPattern,
			$dimType,
		);
	}

}

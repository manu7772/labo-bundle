<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\ExpressionServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Symfony DOC:
 * @see https://symfony.com/doc/current/components/expression_language.html
 * Expression synthax:
 * @see https://symfony.com/doc/current/reference/formats/expression_language.html
 */
#[AsAlias(ExpressionServiceInterface::class, public: true)]
class ExpressionService implements ExpressionServiceInterface
{

    public readonly ExpressionLanguage $expressionLanguage;

    public function __construct(
        public readonly AppServiceInterface $appService,
        // public readonly ExpressionLanguage $expressionLanguage
    )
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }


    public function evaluate(
        Expression|string $expression,
        array $names = [],
        bool $invalidReturnSame = false
    ): mixed
    {
        $this->mergeNames($names);
        try {
            $expression = $this->expressionLanguage->evaluate($expression, $names);
        } catch (\Throwable $th) {
            if(!$invalidReturnSame) throw $th;
        }
        return $expression instanceof Expression
            ? $expression->__toString()
            : $expression;
    }

    public function compile(
        Expression|string $expression,
        array $names = [],
        bool $invalidReturnSame = false
    ): string
    {
        $this->mergeNames($names);
        try {
            $expression = $this->expressionLanguage->compile($expression, $names);
        } catch (\Throwable $th) {
            if(!$invalidReturnSame) throw $th;
        }
        return $expression instanceof Expression
            ? $expression->__toString()
            : $expression;
    }

    public function isValid(
        Expression|string $expression,
        array $names = [],
    ): bool
    {
        try {
            $this->expressionLanguage->lint($expression, $names);
        } catch (\Throwable $th) {
            // throw $th;
            return false;
        }
        return true;
    }

    protected function mergeNames(
        array &$names = []
    ): array
    {
        // $apple = new Apple();
        // $apple->setVariety('Honeycrisp');
        $bases = [
            'appService' => $this->appService,
            'some_text' => 'Photo à gauche du texte',
            // 'fruit' => $apple,

        ];
        return $names = array_merge($bases, $names);
    }

}

// class Apple
// {
//     private string $variety;

//     public function setVariety(
//         string $variety
//     ): static
//     {
//         $this->variety = $variety;
//         return $this;
//     }

//     public function getVariety(): ?string
//     {
//         return $this->variety;
//     }
// }

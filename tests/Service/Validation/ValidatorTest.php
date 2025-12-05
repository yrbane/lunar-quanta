<?php
/**
 * Tests du système de validation.
 *
 * =============================================================================
 * TESTS DE VALIDATION
 * =============================================================================
 *
 * Ces tests vérifient que :
 * - Chaque règle valide/invalide correctement les données
 * - Le Validator combine correctement les règles
 * - Les messages d'erreur sont corrects
 * - Les messages personnalisés fonctionnent
 *
 * @package Tests\Service\Validation
 */
declare(strict_types=1);

namespace Tests\Service\Validation;

use Lunar\Service\Validation\Rule\EmailRule;
use Lunar\Service\Validation\Rule\MaxLengthRule;
use Lunar\Service\Validation\Rule\MinLengthRule;
use Lunar\Service\Validation\Rule\NumericRule;
use Lunar\Service\Validation\Rule\PatternRule;
use Lunar\Service\Validation\Rule\RequiredRule;
use Lunar\Service\Validation\ValidationResult;
use Lunar\Service\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    // =========================================================================
    // TESTS DE LA RÈGLE REQUIRED
    // =========================================================================

    public function testRequiredRuleValidatesNonEmpty(): void
    {
        $rule = new RequiredRule();

        $this->assertTrue($rule->validate('hello'));
        $this->assertTrue($rule->validate('0'));
        $this->assertTrue($rule->validate(0));
        $this->assertTrue($rule->validate(false));
        $this->assertTrue($rule->validate(['item']));
    }

    public function testRequiredRuleRejectsEmpty(): void
    {
        $rule = new RequiredRule();

        $this->assertFalse($rule->validate(''));
        $this->assertFalse($rule->validate('   '));
        $this->assertFalse($rule->validate(null));
        $this->assertFalse($rule->validate([]));
    }

    public function testRequiredRuleHasCorrectName(): void
    {
        $rule = new RequiredRule();

        $this->assertSame('required', $rule->getName());
    }

    public function testRequiredRuleHasMessage(): void
    {
        $rule = new RequiredRule();

        $this->assertNotEmpty($rule->getMessage());
    }

    // =========================================================================
    // TESTS DE LA RÈGLE EMAIL
    // =========================================================================

    public function testEmailRuleValidatesEmail(): void
    {
        $rule = new EmailRule();

        $this->assertTrue($rule->validate('john@example.com'));
        $this->assertTrue($rule->validate('user.name@domain.fr'));
        $this->assertTrue($rule->validate('a@b.co'));
        $this->assertTrue($rule->validate('')); // Vide = valide (utilisez required)
        $this->assertTrue($rule->validate(null));
    }

    public function testEmailRuleRejectsInvalid(): void
    {
        $rule = new EmailRule();

        $this->assertFalse($rule->validate('not-an-email'));
        $this->assertFalse($rule->validate('@domain.com'));
        $this->assertFalse($rule->validate('user@'));
        $this->assertFalse($rule->validate('user@domain'));
        $this->assertFalse($rule->validate(123)); // Non-string
    }

    public function testEmailRuleHasCorrectName(): void
    {
        $rule = new EmailRule();

        $this->assertSame('email', $rule->getName());
    }

    // =========================================================================
    // TESTS DE LA RÈGLE MIN LENGTH
    // =========================================================================

    public function testMinLengthRuleValidates(): void
    {
        $rule = new MinLengthRule(8);

        $this->assertTrue($rule->validate('12345678'));
        $this->assertTrue($rule->validate('password123'));
        $this->assertTrue($rule->validate('')); // Vide = valide
        $this->assertTrue($rule->validate(null));
    }

    public function testMinLengthRuleRejectsTooShort(): void
    {
        $rule = new MinLengthRule(8);

        $this->assertFalse($rule->validate('abc'));
        $this->assertFalse($rule->validate('1234567'));
    }

    public function testMinLengthRuleCountsUtf8Characters(): void
    {
        $rule = new MinLengthRule(5);

        // "éàüîô" = 5 caractères UTF-8
        $this->assertTrue($rule->validate('éàüîô'));
    }

    public function testMinLengthRuleHasCorrectName(): void
    {
        $rule = new MinLengthRule(8);

        $this->assertSame('min', $rule->getName());
    }

    public function testMinLengthRuleMessageIncludesLength(): void
    {
        $rule = new MinLengthRule(8);

        $this->assertStringContainsString('8', $rule->getMessage());
    }

    // =========================================================================
    // TESTS DE LA RÈGLE MAX LENGTH
    // =========================================================================

    public function testMaxLengthRuleValidates(): void
    {
        $rule = new MaxLengthRule(10);

        $this->assertTrue($rule->validate('hello'));
        $this->assertTrue($rule->validate('1234567890'));
        $this->assertTrue($rule->validate(''));
        $this->assertTrue($rule->validate(null));
    }

    public function testMaxLengthRuleRejectsTooLong(): void
    {
        $rule = new MaxLengthRule(10);

        $this->assertFalse($rule->validate('hello world!'));
        $this->assertFalse($rule->validate('12345678901'));
    }

    public function testMaxLengthRuleCountsUtf8Characters(): void
    {
        $rule = new MaxLengthRule(5);

        // "éàüîô" = 5 caractères UTF-8
        $this->assertTrue($rule->validate('éàüîô'));
        $this->assertFalse($rule->validate('éàüîôù'));
    }

    public function testMaxLengthRuleHasCorrectName(): void
    {
        $rule = new MaxLengthRule(10);

        $this->assertSame('max', $rule->getName());
    }

    // =========================================================================
    // TESTS DE LA RÈGLE NUMERIC
    // =========================================================================

    public function testNumericRuleValidatesNumbers(): void
    {
        $rule = new NumericRule();

        $this->assertTrue($rule->validate(42));
        $this->assertTrue($rule->validate(3.14));
        $this->assertTrue($rule->validate('123'));
        $this->assertTrue($rule->validate('-5'));
        $this->assertTrue($rule->validate('0'));
        $this->assertTrue($rule->validate(''));
        $this->assertTrue($rule->validate(null));
    }

    public function testNumericRuleRejectsNonNumbers(): void
    {
        $rule = new NumericRule();

        $this->assertFalse($rule->validate('abc'));
        $this->assertFalse($rule->validate('12abc'));
        $this->assertFalse($rule->validate('1.2.3'));
    }

    public function testNumericRuleHasCorrectName(): void
    {
        $rule = new NumericRule();

        $this->assertSame('numeric', $rule->getName());
    }

    // =========================================================================
    // TESTS DE LA RÈGLE PATTERN
    // =========================================================================

    public function testPatternRuleValidatesPattern(): void
    {
        $rule = new PatternRule('/^[a-z]+$/i');

        $this->assertTrue($rule->validate('hello'));
        $this->assertTrue($rule->validate('WORLD'));
        $this->assertTrue($rule->validate(''));
        $this->assertTrue($rule->validate(null));
    }

    public function testPatternRuleRejectsNonMatching(): void
    {
        $rule = new PatternRule('/^[a-z]+$/i');

        $this->assertFalse($rule->validate('hello123'));
        $this->assertFalse($rule->validate('hello world'));
        $this->assertFalse($rule->validate('123'));
    }

    public function testPatternRuleWithPostalCode(): void
    {
        $rule = new PatternRule('/^\d{5}$/');

        $this->assertTrue($rule->validate('75001'));
        $this->assertFalse($rule->validate('7500'));
        $this->assertFalse($rule->validate('750011'));
        $this->assertFalse($rule->validate('ABCDE'));
    }

    public function testPatternRuleHasCorrectName(): void
    {
        $rule = new PatternRule('/^.+$/');

        $this->assertSame('pattern', $rule->getName());
    }

    public function testPatternRuleWithInvalidPattern(): void
    {
        $rule = new PatternRule('/invalid(pattern/');

        // Un pattern invalide devrait échouer la validation
        $this->assertFalse($rule->validate('test'));
    }

    // =========================================================================
    // TESTS DU VALIDATION RESULT
    // =========================================================================

    public function testValidationResultIsInitiallyValid(): void
    {
        $result = new ValidationResult();

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertSame(0, $result->count());
    }

    public function testValidationResultWithErrors(): void
    {
        $result = new ValidationResult();
        $result->addError('email', 'Email invalide');
        $result->addError('password', 'Trop court');

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertSame(2, $result->count());
    }

    public function testValidationResultMultipleErrorsSameField(): void
    {
        $result = new ValidationResult();
        $result->addError('password', 'Trop court');
        $result->addError('password', 'Pas de chiffre');

        $this->assertSame(2, $result->count());
        $this->assertCount(2, $result->getErrors('password'));
    }

    public function testValidationResultGetFirstError(): void
    {
        $result = new ValidationResult();
        $result->addError('email', 'Première erreur');
        $result->addError('email', 'Deuxième erreur');

        $this->assertSame('Première erreur', $result->getFirstError('email'));
        $this->assertNull($result->getFirstError('inexistant'));
    }

    public function testValidationResultHasError(): void
    {
        $result = new ValidationResult();
        $result->addError('email', 'Erreur');

        $this->assertTrue($result->hasError('email'));
        $this->assertFalse($result->hasError('password'));
    }

    public function testValidationResultGetErrorFields(): void
    {
        $result = new ValidationResult();
        $result->addError('email', 'Erreur email');
        $result->addError('password', 'Erreur password');

        $fields = $result->getErrorFields();

        $this->assertCount(2, $fields);
        $this->assertContains('email', $fields);
        $this->assertContains('password', $fields);
    }

    public function testValidationResultMerge(): void
    {
        $result1 = new ValidationResult();
        $result1->addError('email', 'Erreur 1');

        $result2 = new ValidationResult();
        $result2->addError('password', 'Erreur 2');

        $result1->merge($result2);

        $this->assertSame(2, $result1->count());
        $this->assertTrue($result1->hasError('email'));
        $this->assertTrue($result1->hasError('password'));
    }

    public function testValidationResultToArray(): void
    {
        $result = new ValidationResult();
        $result->addError('email', 'Erreur');

        $array = $result->toArray();

        $this->assertArrayHasKey('valid', $array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertFalse($array['valid']);
    }

    // =========================================================================
    // TESTS DU VALIDATOR
    // =========================================================================

    public function testValidatorValidatesCorrectData(): void
    {
        $data = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertTrue($result->isValid());
    }

    public function testValidatorRejectsInvalidData(): void
    {
        $data = [
            'email' => 'not-an-email',
            'password' => 'abc',
        ];

        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasError('email'));
        $this->assertTrue($result->hasError('password'));
    }

    public function testValidatorDetectsMissingRequiredFields(): void
    {
        $data = [];

        $rules = [
            'email' => ['required'],
            'name' => ['required'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasError('email'));
        $this->assertTrue($result->hasError('name'));
    }

    public function testValidatorWithNumericRule(): void
    {
        $data = [
            'age' => '25',
            'name' => 'John',
        ];

        $rules = [
            'age' => ['numeric'],
            'name' => ['numeric'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertFalse($result->isValid());
        $this->assertFalse($result->hasError('age'));
        $this->assertTrue($result->hasError('name'));
    }

    public function testValidatorWithPatternRule(): void
    {
        $data = [
            'username' => 'john_doe',
            'invalid' => 'john doe!',
        ];

        $rules = [
            'username' => ['pattern:/^[a-z0-9_]+$/i'],
            'invalid' => ['pattern:/^[a-z0-9_]+$/i'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertFalse($result->isValid());
        $this->assertFalse($result->hasError('username'));
        $this->assertTrue($result->hasError('invalid'));
    }

    public function testValidatorWithMaxRule(): void
    {
        $data = [
            'short' => 'hello',
            'long' => 'this is a very long string',
        ];

        $rules = [
            'short' => ['max:10'],
            'long' => ['max:10'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertFalse($result->isValid());
        $this->assertFalse($result->hasError('short'));
        $this->assertTrue($result->hasError('long'));
    }

    public function testValidatorThrowsOnUnknownRule(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = ['field' => 'value'];
        $rules = ['field' => ['unknown_rule']];

        $this->validator->validate($data, $rules);
    }

    // =========================================================================
    // TESTS DES MESSAGES PERSONNALISÉS
    // =========================================================================

    public function testValidatorCustomMessage(): void
    {
        $this->validator->setMessage('email', 'required', 'L\'email est obligatoire !');

        $data = ['email' => ''];
        $rules = ['email' => ['required']];

        $result = $this->validator->validate($data, $rules);

        $this->assertSame('L\'email est obligatoire !', $result->getFirstError('email'));
    }

    public function testValidatorSetMultipleMessages(): void
    {
        $this->validator->setMessages([
            'email' => [
                'required' => 'Email requis',
                'email' => 'Email invalide',
            ],
        ]);

        $data = ['email' => 'not-valid'];
        $rules = ['email' => ['email']];

        $result = $this->validator->validate($data, $rules);

        $this->assertSame('Email invalide', $result->getFirstError('email'));
    }

    public function testValidatorChainableSetMessage(): void
    {
        $this->validator
            ->setMessage('email', 'required', 'Required!')
            ->setMessage('email', 'email', 'Invalid!');

        $data = ['email' => ''];
        $rules = ['email' => ['required']];

        $result = $this->validator->validate($data, $rules);

        $this->assertSame('Required!', $result->getFirstError('email'));
    }

    // =========================================================================
    // TESTS DE SCÉNARIOS RÉELS
    // =========================================================================

    public function testRegistrationFormValidation(): void
    {
        $data = [
            'email' => 'john@example.com',
            'password' => 'SecurePass123',
            'username' => 'john_doe',
            'age' => '25',
        ];

        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'max:100'],
            'username' => ['required', 'min:3', 'max:20', 'pattern:/^[a-z0-9_]+$/i'],
            'age' => ['required', 'numeric'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertTrue($result->isValid());
    }

    public function testRegistrationFormWithErrors(): void
    {
        $data = [
            'email' => 'invalid',
            'password' => 'abc',
            'username' => 'a',
            'age' => 'not-a-number',
        ];

        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'username' => ['required', 'min:3'],
            'age' => ['required', 'numeric'],
        ];

        $result = $this->validator->validate($data, $rules);

        $this->assertFalse($result->isValid());
        $this->assertSame(4, $result->count());
    }
}

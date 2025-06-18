<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rule;

use App\Validation\Rule\SupervisorMinimumRule;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validation;

/**
 * Unit tests for SupervisorMinimumRule
 */
class SupervisorMinimumRuleTest extends TestCase
{
    private SupervisorMinimumRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new SupervisorMinimumRule();
    }

    /**
     * Test that supervisors cannot be 0
     */
    public function testSupervisorsCannotBeZero(): void
    {
        $validation = $this->createMockValidation([
            'tickets' => [
                'students' => 20,
                'supervisors' => 0
            ]
        ]);
        
        $this->rule->setValidation($validation);
        
        $result = $this->rule->check(0);
        
        $this->assertFalse($result);
    }

    /**
     * Test that supervisors must be at least students/10 rounded down
     */
    public function testSupervisorsMinimumRequirement(): void
    {
        // Test with 25 students - should require at least 2 supervisors
        $validation = $this->createMockValidation([
            'tickets' => [
                'students' => 25,
                'supervisors' => 1
            ]
        ]);
        
        $this->rule->setValidation($validation);
        
        $result = $this->rule->check(1);
        $this->assertFalse($result, 'Should fail with 1 supervisor for 25 students');
        
        $result = $this->rule->check(2);
        $this->assertTrue($result, 'Should pass with 2 supervisors for 25 students');
        
        $result = $this->rule->check(3);
        $this->assertTrue($result, 'Should pass with 3 supervisors for 25 students');
    }

    /**
     * Test edge case with exactly 10 students
     */
    public function testExactlyTenStudents(): void
    {
        $validation = $this->createMockValidation([
            'tickets' => [
                'students' => 10,
                'supervisors' => 1
            ]
        ]);
        
        $this->rule->setValidation($validation);
        
        $result = $this->rule->check(1);
        $this->assertTrue($result, 'Should pass with 1 supervisor for 10 students');
    }

    /**
     * Test with no students
     */
    public function testNoStudents(): void
    {
        $validation = $this->createMockValidation([
            'tickets' => [
                'students' => 0,
                'supervisors' => 1
            ]
        ]);
        
        $this->rule->setValidation($validation);
        
        $result = $this->rule->check(1);
        $this->assertTrue($result, 'Should pass with 1 supervisor and no students');
    }

    /**
     * Test with less than 10 students
     */
    public function testLessThanTenStudents(): void
    {
        $validation = $this->createMockValidation([
            'tickets' => [
                'students' => 5,
                'supervisors' => 1
            ]
        ]);
        
        $this->rule->setValidation($validation);
        
        $result = $this->rule->check(1);
        $this->assertTrue($result, 'Should pass with 1 supervisor for 5 students');
    }

    /**
     * Test with invalid data structure (supervisors = 0 should still fail)
     */
    public function testInvalidDataStructure(): void
    {
        $validation = $this->createMockValidation([]);
        
        $this->rule->setValidation($validation);
        
        // Even with invalid data structure, supervisors = 0 should fail
        $result = $this->rule->check(0);
        $this->assertFalse($result, 'Should fail when supervisors is 0');
        
        // But supervisors > 0 should pass when no students data is available
        $result = $this->rule->check(1);
        $this->assertTrue($result, 'Should pass with supervisors > 0 and no students data');
    }

    /**
     * Create a mock validation object with given data
     *
     * @param array $data The validation data
     * @return Validation Mock validation object
     */
    private function createMockValidation(array $data): Validation
    {
        // Create a stub that extends Validation and overrides getValue
        $validation = new class($data) extends Validation {
            private array $testData;
            
            public function __construct(array $data) {
                $this->testData = $data;
                // Don't call parent constructor to avoid dependencies
            }
            
            public function getValue(string $key, $default = null) {
                $keys = explode('.', $key);
                $value = $this->testData;
                
                foreach ($keys as $k) {
                    if (!is_array($value) || !array_key_exists($k, $value)) {
                        return $default;
                    }
                    $value = $value[$k];
                }
                
                return $value;
            }
        };
        
        return $validation;
    }
}
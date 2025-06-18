<?php

declare(strict_types=1);

namespace App\Validation\Rule;

use Rakit\Validation\Rule;

/**
 * Custom validation rule for supervisor tickets
 * Ensures supervisors count is not 0 and at least floor(students/10)
 */
class SupervisorMinimumRule extends Rule
{
    protected $message = ":attribute must not be 0 and must be at least the number of students divided by 10 (rounded down).";

    /**
     * Check if the supervisor count meets the minimum requirements
     *
     * @param mixed $value The supervisor count to validate
     * @return bool True if validation passes, false otherwise
     */
    public function check($value): bool
    {
        $supervisorCount = (int) $value;
        
        // Supervisors must not be 0
        if ($supervisorCount === 0) {
            return false;
        }
        
        // Get the students count from the validation data
        $studentCount = (int) $this->validation->getValue('tickets.students', 0);
        
        // Calculate minimum required supervisors (students / 10, rounded down)
        $minimumSupervisors = (int) floor($studentCount / 10);
        
        // If there are students, we need at least the calculated minimum supervisors
        if ($studentCount > 0 && $supervisorCount < $minimumSupervisors) {
            return false;
        }
        
        return true;
    }
}
<?php

namespace App\Validation\Rule;

use Rakit\Validation\Rule;

class AtLeastOneTicketRule extends Rule
{
    protected $message = ":attribute sum must be at least 1.";

    public function check($value): bool
    {
        // Stellen Sie sicher, dass wir tatsächlich ein Array erhalten haben
        if (!is_array($value)) {
            return false;
        }

        // Berechnen Sie die Summe der 'value'-Einträge
        $sum = array_reduce(
            array: $value,
            callback: function ($sum, $item) {
                return $sum + ((int) $item ?? 0);
            },
            initial: 0
        );

        // Überprüfen, ob die Summe mindestens 1 ist
        return $sum >= 1;
    }
}

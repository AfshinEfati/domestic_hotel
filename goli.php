<?php

public function getPassengerCounts($passengers, $childRule, $room): array
{
    $counts = [
        'adult' => 0,
        'child' => 0,
        'infant' => 0,
    ];

    foreach ($passengers as $passenger) {
        if ($passenger['type'] == 'adl') {
            $counts['adult']++;
        } else {
            if ($childRule && json_decode($childRule->pivot->conditions) && count(json_decode($childRule->pivot->conditions, 1))) {
                if ($passenger['type'] == 'chd') {
                    if ((int)json_decode($childRule->pivot->conditions)->max_child_age) {
                        if ($passenger['age'] > (int)json_decode($childRule->pivot->conditions)->max_child_age)
                            $counts['adult']++;
                        elseif ($passenger['age'] > (int)json_decode($childRule->pivot->conditions)->max_infant_age && $passenger['age'] <= (int)json_decode($childRule->pivot->conditions)->max_child_age) {
                            if ($room['child'] && $counts['child'] < (int)$room['child']) $counts['child']++;
                            else $counts['adult']++;
                        } else {
                            if (!$counts['infant']) $counts['infant']++;
                            else {
                                if ($room['child'] && $counts['child'] < (int)$room['child']) $counts['child']++;
                                else $counts['adult']++;
                            }
                        }
                    } else {
                        if ($passenger['age'] > (int)json_decode($childRule->pivot->conditions)->max_infant_age) $counts['adult']++;
                        else {
                            if (!$counts['infant']) $counts['infant']++;
                            else $counts['adult']++;
                        }
                    }
                }
            } else {
                if ($room['child'] && $counts['child'] < (int)$room['child'])
                    $counts['child']++;
                else $counts['adult']++;
            }
        }
    }

    return $counts;
}

?>

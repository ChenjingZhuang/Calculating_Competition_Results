<?php
/**
 * Test Calculator
 * Tests the points calculation logic without WordPress
 *
 * @package Calculating_Competition_Results
 * @license GPL-2.0-or-later
 */

// Lisää alkuun (ennen require_once):

// Define ABSPATH for CLI
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

// Load calculator (without WordPress)
require_once __DIR__ . '/../includes/competition-results-calculator.php';


echo "===========================================\n";
echo "Competition Results Calculator - Tests\n";
echo "===========================================\n\n";

$calculator = new Competition_Results_Calculator();
$tests_passed = 0;
$tests_failed = 0;

// ============================================
// TEST 1: Basic points calculation
// ============================================
echo "TEST 1: Basic points calculation\n";
echo "-------------------------------------------\n";

$race_results = [
    ['placement' => 1, 'bonus_points' => 0],
    ['placement' => 2, 'bonus_points' => 0],
    ['placement' => 3, 'bonus_points' => 0],
];

$scoring_table = [
    1 => 30,
    2 => 25,
    3 => 21,
    4 => 18,
    5 => 16,
    6 => 14,
    7 => 12,
    8 => 10,
    9 => 8,
    10 => 7,
    11 => 6,
    12 => 5,
    13 => 4,
    14 => 3,
    15 => 2,
    16 => 1
];

$counted_limit = 3;

$result = $calculator->calculate_rider_total($race_results, $counted_limit, $scoring_table);

$expected = 76; // 30 + 25 + 21
$actual = $result['total_score'];

if ($actual === $expected) {
    echo "✅ PASSED: Total score = $actual (expected $expected)\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: Total score = $actual (expected $expected)\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 2A: Placements 16 and above receive one point
// ============================================
echo "TEST 2A: Placements 16+ receive one point\n";
echo "-------------------------------------------\n";

$race_results = [
    ['placement' => 16, 'bonus_points' => 0],
    ['placement' => 17, 'bonus_points' => 0],
    ['placement' => 25, 'bonus_points' => 0],
];

$result = $calculator->calculate_rider_total($race_results, 3, $scoring_table);
$expected = 3;
$actual = $result['total_score'];

if ($actual === $expected) {
    echo "✅ PASSED: Placements 16+ receive one point\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: Placements 16+ total = $actual (expected $expected)\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 2: Drop rule (best X out of Y)
// ============================================
echo "TEST 2: Drop rule (best 3 out of 5)\n";
echo "-------------------------------------------\n";

$race_results = [
    ['placement' => 1, 'bonus_points' => 0],  // 100
    ['placement' => 5, 'bonus_points' => 0],  // 55
    ['placement' => 2, 'bonus_points' => 0],  // 85
    ['placement' => 4, 'bonus_points' => 0],  // 65
    ['placement' => 3, 'bonus_points' => 0],  // 75
];

$counted_limit = 3; // Best 3 out of 5

$result = $calculator->calculate_rider_total($race_results, $counted_limit, $scoring_table);

$expected = 76; // Best 3: 30 + 25 + 21
$actual = $result['total_score'];

if ($actual === $expected) {
    echo "✅ PASSED: Total score = $actual (expected $expected)\n";
    echo "   Drop rule applied: Excluded positions 4 and 5\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: Total score = $actual (expected $expected)\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 3: DNF/DNS/DSQ handling
// ============================================
echo "TEST 3: DNF/DNS/DSQ handling\n";
echo "-------------------------------------------\n";

$race_results = [
    ['placement' => 1, 'bonus_points' => 0],      // 100
    ['placement' => 'DNF', 'bonus_points' => 0],  // 0
    ['placement' => 2, 'bonus_points' => 0],      // 85
    ['placement' => 'DNS', 'bonus_points' => 0],  // 0
    ['placement' => 3, 'bonus_points' => 0],      // 75
];

$counted_limit = 5;

$result = $calculator->calculate_rider_total($race_results, $counted_limit, $scoring_table);

$expected = 76; // 30 + 0 + 25 + 0 + 21
$actual = $result['total_score'];

if ($actual === $expected) {
    echo "✅ PASSED: Total score = $actual (expected $expected)\n";
    echo "   DNF/DNS correctly handled as 0 points\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: Total score = $actual (expected $expected)\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 4: Bonus points
// ============================================
echo "TEST 4: Bonus points\n";
echo "-------------------------------------------\n";

$race_results = [
    ['placement' => 1, 'bonus_points' => 10],  // 100 + 10 = 110
    ['placement' => 2, 'bonus_points' => 5],   // 85 + 5 = 90
    ['placement' => 3, 'bonus_points' => 0],   // 75 + 0 = 75
];

$counted_limit = 3;

$result = $calculator->calculate_rider_total($race_results, $counted_limit, $scoring_table);

$expected = 91; // 40 + 30 + 21
$actual = $result['total_score'];

if ($actual === $expected) {
    echo "✅ PASSED: Total score = $actual (expected $expected)\n";
    echo "   Bonus points correctly added\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: Total score = $actual (expected $expected)\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 5: Tie-breaker (place counts)
// ============================================
echo "TEST 5: Tie-breaker (place counts)\n";
echo "-------------------------------------------\n";

$race_results = [
    ['placement' => 1, 'bonus_points' => 0],
    ['placement' => 1, 'bonus_points' => 0],
    ['placement' => 2, 'bonus_points' => 0],
    ['placement' => 3, 'bonus_points' => 0],
];

$counted_limit = 4;

$result = $calculator->calculate_rider_total($race_results, $counted_limit, $scoring_table);

$place_counts = $result['place_counts'];

$expected_first_places = 2;
$actual_first_places = $place_counts[1];

if ($actual_first_places === $expected_first_places) {
    echo "✅ PASSED: First place count = $actual_first_places (expected $expected_first_places)\n";
    echo "   Place counts:\n";
    echo "   - 1st places: " . $place_counts[1] . "\n";
    echo "   - 2nd places: " . $place_counts[2] . "\n";
    echo "   - 3rd places: " . $place_counts[3] . "\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: First place count = $actual_first_places (expected $expected_first_places)\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 6: Sort standings (tie-breaker)
// ============================================
echo "TEST 6: Sort standings (tie-breaker)\n";
echo "-------------------------------------------\n";

$riders = [
    [
        'athlete_name' => 'Athlete A',
        'total_score' => 260,
        'place_counts' => [1 => 1, 2 => 1, 3 => 1, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0]
    ],
    [
        'athlete_name' => 'Athlete B',
        'total_score' => 260,
        'place_counts' => [1 => 2, 2 => 0, 3 => 1, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0]
    ],
    [
        'athlete_name' => 'Athlete C',
        'total_score' => 250,
        'place_counts' => [1 => 3, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0]
    ]
];

$sorted = $calculator->sort_standings($riders);

// Expected order: B (2 first places), A (1 first place), C (lower total)
$expected_order = ['Athlete B', 'Athlete A', 'Athlete C'];
$actual_order = [$sorted[0]['athlete_name'], $sorted[1]['athlete_name'], $sorted[2]['athlete_name']];

if ($expected_order === $actual_order) {
    echo "✅ PASSED: Tie-breaker correctly applied\n";
    echo "   Order:\n";
    foreach ($sorted as $rider) {
        echo "   - " . $rider['athlete_name'] . " (" . $rider['total_score'] . " points)\n";
    }
    $tests_passed++;
} else {
    echo "❌ FAILED: Tie-breaker not correctly applied\n";
    echo "   Expected: " . implode(', ', $expected_order) . "\n";
    echo "   Actual: " . implode(', ', $actual_order) . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 7: Tie-breaker using second-place counts
// ============================================
echo "TEST 7: Tie-breaker using second-place counts\n";
echo "-------------------------------------------\n";

$riders = [
    [
        'athlete_name' => 'Athlete A',
        'total_score' => 100,
        'place_counts' => [1 => 1, 2 => 2, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0]
    ],
    [
        'athlete_name' => 'Athlete B',
        'total_score' => 100,
        'place_counts' => [1 => 1, 2 => 1, 3 => 2, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0]
    ]
];

$sorted = $calculator->sort_standings($riders);
$expected_order = ['Athlete A', 'Athlete B'];
$actual_order = [$sorted[0]['athlete_name'], $sorted[1]['athlete_name']];

if ($expected_order === $actual_order) {
    echo "✅ PASSED: Second-place tie-breaker correctly applied\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: Second-place tie-breaker not correctly applied\n";
    echo "   Expected: " . implode(', ', $expected_order) . "\n";
    echo "   Actual: " . implode(', ', $actual_order) . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TESTS 8-10: Tie-breakers using third, fourth and fifth places
// ============================================
$tie_breaker_cases = [
    [
        'label' => 'third-place',
        'first' => [1 => 1, 2 => 1, 3 => 2, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0],
        'second' => [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0],
    ],
    [
        'label' => 'fourth-place',
        'first' => [1 => 1, 2 => 1, 3 => 1, 4 => 2, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0],
        'second' => [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0],
    ],
    [
        'label' => 'fifth-place',
        'first' => [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 2, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0],
        'second' => [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1, 7 => 0, 8 => 0, 9 => 0, 10 => 0],
    ],
];

foreach ( $tie_breaker_cases as $tie_breaker_case ) {
    $sorted = $calculator->sort_standings([
        [
            'athlete_name' => 'Athlete A',
            'total_score' => 100,
            'place_counts' => $tie_breaker_case['first'],
        ],
        [
            'athlete_name' => 'Athlete B',
            'total_score' => 100,
            'place_counts' => $tie_breaker_case['second'],
        ],
    ]);

    if ( $sorted[0]['athlete_name'] === 'Athlete A' ) {
        echo "TEST PASSED: {$tie_breaker_case['label']} tie-breaker\n";
        $tests_passed++;
    } else {
        echo "TEST FAILED: {$tie_breaker_case['label']} tie-breaker\n";
        $tests_failed++;
    }
}

echo "\n";

// ============================================
// TEST 11: Edge case - empty results
// ============================================
echo "TEST 11: Edge case - empty results\n";
echo "-------------------------------------------\n";

$race_results = [];
$counted_limit = 3;

$result = $calculator->calculate_rider_total($race_results, $counted_limit, $scoring_table);

$expected = 0;
$actual = $result['total_score'];

if ($actual === $expected) {
    echo "✅ PASSED: Empty results = $actual (expected $expected)\n";
    $tests_passed++;
} else {
    echo "❌ FAILED: Empty results = $actual (expected $expected)\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// Summary
// ============================================
echo "===========================================\n";
echo "TEST SUMMARY\n";
echo "===========================================\n";
echo "Tests passed: $tests_passed\n";
echo "Tests failed: $tests_failed\n";
echo "Total tests:  " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! 🎉\n";
    exit(0);
} else {
    echo "\n⚠️  SOME TESTS FAILED ⚠️\n";
    exit(1);
}
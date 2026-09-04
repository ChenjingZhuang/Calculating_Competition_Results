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
require_once __DIR__ . '/../includes/competition_results_calculator.php';

// Load calculator (without WordPress)
require_once __DIR__ . '/../includes/competition_results_calculator.php';

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
    1 => 100,
    2 => 85,
    3 => 75,
    4 => 65,
    5 => 55
];

$counted_limit = 3;

$result = $calculator->calculate_rider_total($race_results, $counted_limit, $scoring_table);

$expected = 260; // 100 + 85 + 75
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

$expected = 260; // Best 3: 100 + 85 + 75
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

$expected = 260; // 100 + 0 + 85 + 0 + 75
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

$expected = 275; // 110 + 90 + 75
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
// TEST 7: Edge case - empty results
// ============================================
echo "TEST 7: Edge case - empty results\n";
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
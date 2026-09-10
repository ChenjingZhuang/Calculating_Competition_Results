<?php

function validateResult(array $result): array
{
    $errors = [];
    $warnings = [];

    // Category is required.
    if (empty($result['category'])) {
        $errors[] = 'Category is missing.';
    }

    // Rider name is required.
    if (empty($result['last_name'])) {
        $errors[] = 'Last name is missing.';
    }

    if (empty($result['first_name'])) {
        $errors[] = 'First name is missing.';
    }

    // Club is optional, but we want to know when it is missing.
    if (empty($result['club'])) {
        $warnings[] = 'Club is missing.';
    }

    $allowedStatuses = ['Finished', 'DNS', 'DNF', 'DSQ'];

    if (!in_array($result['status'], $allowedStatuses, true)) {
        $errors[] = 'Invalid result status.';
    }

    if ($result['status'] === 'Finished') {

        if (
            $result['placement'] === null ||
            !is_int($result['placement']) ||
            $result['placement'] <= 0
        ) {
            $errors[] = 'Finished rider must have a valid placement.';
        }

        if (empty($result['finish_time'])) {
            $errors[] = 'Finished rider must have a finish time.';
        }
    }

    if ($result['status'] === 'DNS') {

        if ($result['placement'] !== null) {
            $errors[] = 'DNS rider must not have a placement.';
        }

        if (!empty($result['finish_time'])) {
            $errors[] = 'DNS rider must not have a finish time.';
        }
    }

    if (
        in_array($result['status'], ['DNF', 'DSQ'], true) &&
        $result['placement'] !== null
    ) {
        $errors[] = $result['status'] . ' rider should not have a placement.';
    }

    return [
        'errors' => $errors,
        'warnings' => $warnings
    ];
}
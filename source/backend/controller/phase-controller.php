<?php

class PhaseController implements Controller
{
    private function __construct() {}

    public static function index(): void {}

    public static function addPhase(): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Cannot decode data.');
        }

        // TODO: Validate data

        $newPhase = Phase::fromArray([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'startDateTime' => $data['startDateTime'],
            'completionDateTime' => $data['completionDateTime'],
            'actualCompletionDateTime' => $data['actualCompletionDateTime'] ?? null,
            'status' => WorkStatus::from($data['status']) ?? WorkStatus::getStatusFromDates($data['startDateTime'], $data['completionDateTime']),
        ]);
        if (!$newPhase) {
            Response::error('Invalid phase data.');
        }

        // TODO: Save to database

        Response::success([], 'Phase added successfully.', 201);
    }
}

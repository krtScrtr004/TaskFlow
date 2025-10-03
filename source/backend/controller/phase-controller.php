<?php

class PhaseController implements Controller
{
    private function __construct()
    {
    }

    public static function index(): void
    {
    }

    public static function addPhase(): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Cannot decode data.');
        }

        // TODO: Validate data

        $newPhase = Phase::fromArray([
            'id' => uniqid(), // TODO: Generate a UUIDv4 for the new phase
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'startDateTime' => new DateTime($data['startDateTime']),
            'completionDateTime' => new DateTime($data['completionDateTime']),
            'actualCompletionDateTime' => $data['actualCompletionDateTime'] ?? null,
            'status' => $data['status']
                ? WorkStatus::from($data['status'])
                : WorkStatus::getStatusFromDates(
                    new DateTime($data['startDateTime']),
                    new DateTime($data['completionDateTime'])
                ),
        ]);
        if (!$newPhase) {
            Response::error('Invalid phase data.');
        }

        // TODO: Save to database

        Response::success([
            'id' => $newPhase->getId()
        ], 'Phase added successfully.', 201);
    }
}

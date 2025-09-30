<?php

class ProjectController implements Controller
{
    private function __construct() {}

    public static function index(): void
    {
        // TODO: Dummy

        $workers = new ProjectWorkerContainer([
            new User(
                uniqid(),
                'Alice',
                'B.',
                'Smith',
                Gender::FEMALE,
                new DateTime('1990-05-15'),
                Role::WORKER,
                '123-456-7890',
                'alice@example.com',
                'Experienced developer',
                null,
                new DateTime('2020-01-10')
            ),
            new User(
                uniqid(),
                'Bob',
                'C.',
                'Johnson',
                Gender::MALE,
                new DateTime('1985-08-22'),
                Role::WORKER,
                '987-654-3210',
                'bob@example.com',
                'Skilled designer',
                null,
                new DateTime('2019-03-25')
            )
        ]);

        $start = new DateTime('2023-01-01 12:00:00');
        $end = new DateTime('2023-12-31 23:59:59');
        $completed = new DateTime('2023-11-30 18:30:00');
        $status = ProjectTaskStatus::getStatusFromDates($start, $end);

        $project = new Project(
            uniqid(),
            'New Project',
            'This is a new project created for testing purposes.',
            Me::getInstance(),
            10000000,
            null,
            $workers,
            null,
            $start,
            $end,
            $completed,
            $status,
            new DateTime()
        );

        $tasks = new TaskContainer();
        $tasks->addTask(new Task(
            uniqid(),
            'Task 1',
            'This is the first task.',
            new DateTime('2023-01-02 09:00:00'),
            new DateTime('2023-01-04 17:00:00'),
            new DateTime('2023-01-12 16:00:00'),
            TaskPriority::HIGH,
            ProjectTaskStatus::ON_GOING,
            new DateTime('2023-01-05 10:00:00'),
        ));
        $tasks->addTask(new Task(
            uniqid(),
            'Task 2',
            'This is the second task.',
            new DateTime('2023-02-01 09:00:00'),
            new DateTime('2023-02-05 17:00:00'),
            new DateTime('2023-02-06 14:00:00'),
            TaskPriority::MEDIUM,
            ProjectTaskStatus::PENDING,
            new DateTime('2023-01-15 11:00:00'),
        ));
        $project->setTasks($tasks);

        $phases = new ProjectPhaseContainer([
            new ProjectPhase(
                'Phase 1',
                'Lorem123',
                new DateTime('2024-12-23'),
                new DateTime('2024-12-25'),
                new DateTime('2024-12-30')
            ),
            new ProjectPhase(
                'Phase 2',
                'Lorem123',
                new DateTime('2024-12-23'),
                new DateTime('2024-12-25'),
                new DateTime('2024-12-30')
            ),
            new ProjectPhase(
                'Phase 3',
                'Lorem123',
                new DateTime('2024-12-23'),
                new DateTime('2024-12-25'),
                new DateTime('2024-12-30')
            )
        ]);
        $project->setPhases($phases);

        require_once VIEW_PATH . 'project.php';
    }
}

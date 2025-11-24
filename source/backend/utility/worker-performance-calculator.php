<?php

namespace App\Utility;

use App\Container\ProjectContainer;
use App\Container\TaskContainer;
use App\Entity\Task;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;

require_once ENUM_PATH . 'task-priority.php';
require_once ENUM_PATH . 'work-status.php';

/**
 * Utility class for calculating worker performance based on completed tasks and priorities
 */
class WorkerPerformanceCalculator
{
    // Priority weights for performance calculation
    private const PRIORITY_WEIGHTS = [
        TaskPriority::HIGH->value => 5.0,
        TaskPriority::MEDIUM->value => 3.0,
        TaskPriority::LOW->value => 1.0
    ];

    // Status multipliers (how much each status contributes to performance)
    private const STATUS_MULTIPLIERS = [
        WorkStatus::COMPLETED->value => 1.0,  // Full credit
        WorkStatus::ON_GOING->value => 0.5,   // Partial credit
        WorkStatus::DELAYED->value => 0.3,    // Reduced credit due to delay
        WorkStatus::PENDING->value => 0.0,    // No credit yet
        WorkStatus::CANCELLED->value => 0.0   // No credit
    ];

    // Worker status penalties (deducted from overall score)
    private const WORKER_STATUS_PENALTIES = [
        WorkerStatus::ASSIGNED->value => 0.0,      // No penalty
        WorkerStatus::TERMINATED->value => 15.0    // -15% penalty for task termination
    ];

    // Project-level termination penalty (greater than task termination)
    private const PROJECT_TERMINATION_PENALTY = 25.0;  // -25% penalty for project termination

    // Time-based bonuses
    private const EARLY_COMPLETION_BONUS = 1.2;  // 20% bonus for early completion
    private const ON_TIME_MULTIPLIER = 1.0;      // Standard multiplier
    private const LATE_PENALTY = 0.8;            // 20% penalty for late completion

    /**
     * Calculate worker's overall performance based on projects they worked on
     * Aggregates tasks from all projects and provides project-context metrics
     * Accounts for worker status (terminated workers receive penalties)
     * Tracks project progress and applies greater penalties for project-level termination
     * 
     * @param ProjectContainer $projects Container of projects the worker participated in
     * @return array Comprehensive performance data with project context
     */
    public static function calculate(ProjectContainer $projects): array
    {
        if ($projects->count() < 1) {
            return [
                'overallScore' => 0.0,
                'totalTasks' => 0,
                'totalProjects' => 0,
                'performanceGrade' => 'N/A',
                'taskMetrics' => [],
                'projectMetrics' => [],
                'statusPenalties' => [],
                'messages' => [
                    'insights' => ['No projects found for evaluation period'],
                    'recommendations' => []
                ]
            ];
        }

        // Aggregate all tasks from all projects (now nested in phases)
        $allTasks = new TaskContainer();
        $projectMetrics = [
            'totalProjects' => $projects->count(),
            'projectsByStatus' => [],
            'projectCompletionRates' => [],
            'averageTasksPerProject' => 0,
            'projectDiversity' => []
        ];

        $statusPenalties = [
            'taskTerminations' => 0,
            'projectTerminations' => 0,
            'totalPenalty' => 0.0,
            'penaltyBreakdown' => []
        ];

        $totalTaskCount = 0;

        foreach ($projects as $project) {
            $workerStatus = $project->getAdditionalInfo('workerStatus');

            // Track termination penalties at project level
            if ($workerStatus === WorkerStatus::TERMINATED) {
                $statusPenalties['projectTerminations']++;
                $statusPenalties['totalPenalty'] += self::PROJECT_TERMINATION_PENALTY;
                $statusPenalties['penaltyBreakdown'][] = [
                    'projectName' => $project->getName(),
                    'type' => 'project',
                    'penalty' => self::PROJECT_TERMINATION_PENALTY,
                    'reason' => 'Terminated from project'
                ];
            }

            // Get phases from project (new hierarchical structure)
            $phases = $project->getPhases();
            
            if ($phases && $phases->count() > 0) {
                $completedTasks = 0;
                
                // Iterate through phases to get tasks
                foreach ($phases as $phase) {
                    $tasks = $phase->getTasks();
                    
                    if ($tasks && $tasks->count() > 0) {
                        foreach ($tasks as $task) {
                            $allTasks->add($task);
                            $totalTaskCount++;

                            // Track completed tasks for completion rate
                            if ($task->getStatus()->value === WorkStatus::COMPLETED->value) {
                                $completedTasks++;
                            }

                            // Track task-level termination penalties
                            $taskWorkerStatus = $task->getAdditionalInfo('workerStatus');
                            if ($taskWorkerStatus === WorkerStatus::TERMINATED) {
                                $statusPenalties['taskTerminations']++;
                                $penalty = self::WORKER_STATUS_PENALTIES[WorkerStatus::TERMINATED->value];
                                $statusPenalties['totalPenalty'] += $penalty;
                                $statusPenalties['penaltyBreakdown'][] = [
                                    'taskName' => $task->getName(),
                                    'projectName' => $project->getName(),
                                    'phaseName' => $phase->getName(),
                                    'type' => 'task',
                                    'penalty' => $penalty,
                                    'reason' => 'Terminated from task'
                                ];
                            }
                        }
                    }
                }

                // Calculate task completion rate for this project
                $completionRate = $totalTaskCount > 0 ? ($completedTasks / $totalTaskCount) * 100 : 0;
                $projectMetrics['projectCompletionRates'][] = $completionRate;
            }

            // Track project-level metrics
            $projectStatus = $project->getStatus()->value;
            $projectMetrics['projectsByStatus'][$projectStatus] =
                ($projectMetrics['projectsByStatus'][$projectStatus] ?? 0) + 1;
        }

        // Calculate average tasks per project
        $projectMetrics['averageTasksPerProject'] = $projects->count() > 0
            ? round($totalTaskCount / $projects->count(), 1)
            : 0;

        // Calculate average project completion rate
        $projectMetrics['averageProjectCompletion'] = !empty($projectMetrics['projectCompletionRates'])
            ? round(array_sum($projectMetrics['projectCompletionRates']) / count($projectMetrics['projectCompletionRates']), 2)
            : 0;

        // Calculate task-based performance using existing method
        $taskPerformance = self::calculatePerformancePerTasks($allTasks);

        // Apply status penalties to overall score
        $baseScore = $taskPerformance['overallScore'];
        $penalizedScore = max(0, $baseScore - $statusPenalties['totalPenalty']);

        // Generate project-specific insights
        $projectInsights = self::generateProjectInsights($projectMetrics, $taskPerformance);
        
        // Generate status penalty insights
        $statusInsights = self::generateStatusPenaltyInsights($statusPenalties);

        // Combine insights
        $combinedInsights = array_merge(
            $taskPerformance['insights'], 
            $projectInsights,
            $statusInsights
        );

        return [
            'overallScore' => round($penalizedScore, 2),
            'baseScore' => round($baseScore, 2),
            'totalTasks' => $totalTaskCount,
            'totalProjects' => $projects->count(),
            'performanceGrade' => self::getTaskPerformanceGrade($penalizedScore),
            'taskMetrics' => [
                'rawScore' => $taskPerformance['rawScore'],
                'maxPossibleScore' => $taskPerformance['maxPossibleScore'],
                'totalTasks' => $totalTaskCount
            ],
            'projectMetrics' => $projectMetrics,
            'statusPenalties' => $statusPenalties,
            'messages' => [
                'insights' => $combinedInsights,
                'recommendations' => self::generateProjectRecommendations($projectMetrics, $taskPerformance, $statusPenalties)
            ]
        ];
    }

    /**
     * Generate insights based on project-level metrics
     */
    private static function generateProjectInsights(array $projectMetrics, array $taskPerformance): array
    {
        $insights = [];

        // Project count insights
        $totalProjects = $projectMetrics['totalProjects'];
        if ($totalProjects >= 10) {
            $insights[] = "Experienced worker with involvement in {$totalProjects} projects.";
        } elseif ($totalProjects >= 5) {
            $insights[] = "Worker has contributed to {$totalProjects} projects.";
        } elseif ($totalProjects > 0) {
            $insights[] = "Worker is building experience with {$totalProjects} project(s).";
        }

        // Project status insights
        if (!empty($projectMetrics['projectsByStatus'])) {
            $completedProjects = $projectMetrics['projectsByStatus'][WorkStatus::COMPLETED->value] ?? 0;
            if ($completedProjects > 0) {
                $completionPercentage = round(($completedProjects / $totalProjects) * 100, 1);
                $insights[] = "Contributed to {$completedProjects} completed projects ({$completionPercentage}% of total).";
            }

            $ongoingProjects = $projectMetrics['projectsByStatus'][WorkStatus::ON_GOING->value] ?? 0;
            if ($ongoingProjects > 0) {
                $insights[] = "Currently active in {$ongoingProjects} ongoing project(s).";
            }
        }

        // Task completion rate across projects
        $avgCompletion = $projectMetrics['averageProjectCompletion'];
        if ($avgCompletion >= 80) {
            $insights[] = "Strong task completion rate ({$avgCompletion}%) across all projects.";
        } elseif ($avgCompletion >= 60) {
            $insights[] = "Moderate task completion rate ({$avgCompletion}%) - room for improvement.";
        } elseif ($avgCompletion > 0) {
            $insights[] = "Low task completion rate ({$avgCompletion}%) - may need additional support.";
        }

        // Workload insights
        $avgTasksPerProject = $projectMetrics['averageTasksPerProject'];
        if ($avgTasksPerProject >= 20) {
            $insights[] = "Handles significant workload with average of {$avgTasksPerProject} tasks per project.";
        } elseif ($avgTasksPerProject >= 10) {
            $insights[] = "Maintains steady workload of {$avgTasksPerProject} tasks per project on average.";
        }

        return $insights;
    }

    /**
     * Generate insights based on status penalties
     */
    private static function generateStatusPenaltyInsights(array $statusPenalties): array
    {
        $insights = [];

        if ($statusPenalties['totalPenalty'] > 0) {
            $insights[] = "Performance adjusted with {$statusPenalties['totalPenalty']}% penalty for terminations.";

            if ($statusPenalties['projectTerminations'] > 0) {
                $insights[] = "Terminated from {$statusPenalties['projectTerminations']} project(s) - significant performance impact.";
            }

            if ($statusPenalties['taskTerminations'] > 0) {
                $insights[] = "Terminated from {$statusPenalties['taskTerminations']} task(s) - indicates reliability concerns.";
            }

            if ($statusPenalties['projectTerminations'] > 2) {
                $insights[] = "Multiple project terminations detected - urgent review required.";
            }
        }

        return $insights;
    }

    /**
     * Generate recommendations based on project-level performance
     */
    private static function generateProjectRecommendations(array $projectMetrics, array $taskPerformance, array $statusPenalties = []): array
    {
        $recommendations = $taskPerformance['recommendations'] ?? [];

        // Termination-based recommendations
        if (!empty($statusPenalties) && $statusPenalties['totalPenalty'] > 0) {
            if ($statusPenalties['projectTerminations'] > 0) {
                $recommendations[] = "Address reasons for project termination(s) to improve future performance.";
                $recommendations[] = "Meet with project manager to discuss expectations and performance improvement.";
            }

            if ($statusPenalties['taskTerminations'] >= 3) {
                $recommendations[] = "Multiple task terminations - consider additional training or mentorship.";
            }

            if ($statusPenalties['totalPenalty'] >= 50) {
                $recommendations[] = "High penalty score - formal performance improvement plan recommended.";
            }
        }

        // Project diversity recommendations
        $totalProjects = $projectMetrics['totalProjects'];
        if ($totalProjects < 3 && $taskPerformance['overallScore'] >= 80) {
            $recommendations[] = "Consider diversifying project experience to build broader skill set.";
        }

        // Project completion recommendations
        $avgCompletion = $projectMetrics['averageProjectCompletion'];
        if ($avgCompletion < 60) {
            $recommendations[] = "Focus on completing more tasks within assigned projects.";
            $recommendations[] = "Review project task priorities and seek clarification when needed.";
        }

        // Workload balance recommendations
        $avgTasksPerProject = $projectMetrics['averageTasksPerProject'];
        if ($avgTasksPerProject > 30) {
            $recommendations[] = "High task volume per project - ensure workload is manageable.";
            $recommendations[] = "Consider discussing task distribution with project manager.";
        } elseif ($avgTasksPerProject < 5 && $totalProjects > 5) {
            $recommendations[] = "Low task count per project - consider deeper involvement in fewer projects.";
        }

        // Ongoing projects insights
        if (!empty($projectMetrics['projectsByStatus'])) {
            $ongoingProjects = $projectMetrics['projectsByStatus'][WorkStatus::ON_GOING->value] ?? 0;
            $completedProjects = $projectMetrics['projectsByStatus'][WorkStatus::COMPLETED->value] ?? 0;

            if ($ongoingProjects > 5 && $completedProjects < 2) {
                $recommendations[] = "Many ongoing projects with few completions - prioritize finishing current work.";
            }
        }

        // Performance-based project recommendations
        if ($taskPerformance['overallScore'] >= 90 && $totalProjects >= 5) {
            $recommendations[] = "Excellent performance across multiple projects - potential for leadership roles.";
            $recommendations[] = "Consider mentoring other team members on project best practices.";
        }

        return array_unique($recommendations);
    }

    /**
     * Utility method to calculate basic performance metrics from tasks
     * Used internally by calculateOverall
     * 
     * @param TaskContainer $tasks Container of tasks assigned to the worker
     * @return array Basic performance data
     */
    private static function calculatePerformancePerTasks(TaskContainer $tasks): array
    {
        if ($tasks->count() < 1) {
            return [
                'overallScore' => 0.0,
                'totalTasks' => 0,
                'performanceGrade' => 'N/A',
                'rawScore' => 0.0,
                'maxPossibleScore' => 0.0,
                'insights' => ['No tasks found for evaluation period'],
                'recommendations' => []
            ];
        }

        $totalScore = 0.0;
        $maxPossibleScore = 0.0;

        foreach ($tasks as $task) {
            $taskScore = self::calculateTaskScore($task);

            // Accumulate scores
            $totalScore += $taskScore['weightedScore'];
            $maxPossibleScore += $taskScore['maxPossibleScore'];
        }

        // Calculate final performance score (0-100)
        $performanceScore = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;

        return [
            'overallScore' => round($performanceScore, 2),
            'totalTasks' => count($tasks),
            'performanceGrade' => self::getTaskPerformanceGrade($performanceScore),
            'rawScore' => round($totalScore, 2),
            'maxPossibleScore' => round($maxPossibleScore, 2),
            'insights' => [],
            'recommendations' => []
        ];
    }

    /**
     * Calculate score for individual task
     */
    private static function calculateTaskScore(Task $task): array
    {
        $priority = $task->getPriority()->value ?? TaskPriority::MEDIUM->value;
        $status = $task->getStatus()->value ?? WorkStatus::PENDING->value;
        $startDate = $task->getStartDateTime() ?: null;
        $completionDate = $task->getCompletionDateTime() ?: null;
        $actualCompletionDate = $task->getActualCompletionDateTime() ?: null;

        // Base score from priority weight
        $priorityWeight = self::PRIORITY_WEIGHTS[$priority] ?? 1.0;
        $statusMultiplier = self::STATUS_MULTIPLIERS[$status] ?? 0.0;

        // Calculate time performance
        $timeMultiplier = 1.0;
        $timePerformance = '';

        if ($actualCompletionDate && $completionDate && $status === WorkStatus::COMPLETED->value) {
            if ($actualCompletionDate < $completionDate) {
                $timeMultiplier = self::EARLY_COMPLETION_BONUS;
                $timePerformance = 'early';
            } elseif ($actualCompletionDate <= $completionDate->modify('+1 day')) {
                $timeMultiplier = self::ON_TIME_MULTIPLIER;
                $timePerformance = 'onTime';
            } else {
                $timeMultiplier = self::LATE_PENALTY;
                $timePerformance = 'late';
            }
        }

        $taskScore = $priorityWeight * $statusMultiplier * $timeMultiplier;
        $maxScore = $priorityWeight * 1.0 * self::EARLY_COMPLETION_BONUS; // Maximum possible score

        return [
            'taskId' => $task->getId() ?? 'unknown',
            'priority' => $priority,
            'status' => $status,
            'priorityWeight' => $priorityWeight,
            'statusMultiplier' => $statusMultiplier,
            'timeMultiplier' => $timeMultiplier,
            'timePerformance' => $timePerformance,
            'weightedScore' => $taskScore,
            'maxPossibleScore' => $maxScore
        ];
    }

    /**
     * Generate performance grade based on score
     */
    private static function getTaskPerformanceGrade(float $score): string
    {
        if ($score >= 90)
            return 'A+ (Exceptional)';
        if ($score >= 85)
            return 'A (Excellent)';
        if ($score >= 80)
            return 'B+ (Very Good)';
        if ($score >= 75)
            return 'B (Good)';
        if ($score >= 70)
            return 'C+ (Above Average)';
        if ($score >= 65)
            return 'C (Average)';
        if ($score >= 60)
            return 'D+ (Below Average)';
        if ($score >= 50)
            return 'D (Poor)';
        return 'F (Failing)';
    }
}

<?php

namespace App\Utility;

use App\Container\TaskContainer;
use App\Container\PhaseContainer;
use App\Enumeration\WorkStatus;
use App\Enumeration\TaskPriority;

require_once ENUM_PATH . 'work-status.php';
require_once ENUM_PATH . 'task-priority.php';

/**
 * Utility class for calculating project progress based on phases and task statuses/priorities
 * 
 * This calculator now accounts for the hierarchical structure:
 * Project → Phases → Tasks
 * 
 * Progress is calculated as:
 * 1. Phase-level progress (based on aggregate task completion within each phase)
 * 2. Weighted by task count to ensure larger phases have proportional impact
 * 3. Combined with direct task-level metrics for detailed insights
 */
class ProjectProgressCalculator
{
    // Weight multipliers for different priorities
    private const PRIORITY_WEIGHTS = [
        TaskPriority::HIGH->value => 3.0,
        TaskPriority::MEDIUM->value => 2.0,
        TaskPriority::LOW->value => 1.0
    ];

    // Completion percentage for each status
    private const STATUS_COMPLETION = [
        WorkStatus::PENDING->value => 0.0,
        WorkStatus::ON_GOING->value => 50.0,
        WorkStatus::COMPLETED->value => 100.0,
        WorkStatus::DELAYED->value => 25.0,  // Partial progress assumed
        WorkStatus::CANCELLED->value => 0.0  // No progress counted
    ];

    /**
     * Calculate overall project progress accounting for phases
     * 
     * @param PhaseContainer $phaseContainer Container with phases, where each phase contains a TaskContainer
     * @return array Progress data including phase breakdown, task breakdown, and insights
     */
    public static function calculate(PhaseContainer $phaseContainer): array
    {
        // Extract all tasks from all phases
        $phases = $phaseContainer->getItems();
        
        if (empty($phases)) {
            return [
                'progressPercentage' => 0.0,
                'totalTasks' => 0,
                'statusBreakdown' => [],
                'priorityBreakdown' => [],
                'weightedProgress' => 0.0,
                'phaseBreakdown' => [],
                'insights' => ['message' => 'No phases found in project']
            ];
        }

        // Initialize counters
        $statusCounts = [];
        $priorityCounts = [];
        $phaseData = [];
        $totalTasks = 0;
        $totalWeightedProgress = 0.0;
        $totalWeight = 0.0;

        // Process each phase and its tasks
        foreach ($phases as $phase) {
            $phaseId = $phase->getId();
            $phaseName = $phase->getName();
            $taskContainer = $phase->getTasks();
            
            // Initialize phase data
            $phaseData[$phaseId] = [
                'phaseName' => $phaseName,
                'totalTasks' => 0,
                'statusCounts' => [],
                'priorityCounts' => [],
                'totalWeightedProgress' => 0.0,
                'totalWeight' => 0.0
            ];

            // Process tasks within this phase
            if ($taskContainer && $taskContainer->count() > 0) {
                $tasks = $taskContainer->getItems();
                
                foreach ($tasks as $task) {
                    $status = $task->getStatus()->value;
                    $priority = $task->getPriority()->value;

                    // Count by status (overall)
                    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                    
                    // Count by priority (overall)
                    $priorityCounts[$priority] = ($priorityCounts[$priority] ?? 0) + 1;

                    // Track phase-specific data
                    $phaseData[$phaseId]['totalTasks']++;
                    $phaseData[$phaseId]['statusCounts'][$status] = ($phaseData[$phaseId]['statusCounts'][$status] ?? 0) + 1;
                    $phaseData[$phaseId]['priorityCounts'][$priority] = ($phaseData[$phaseId]['priorityCounts'][$priority] ?? 0) + 1;

                    // Calculate weighted progress
                    $weight = self::PRIORITY_WEIGHTS[$priority] ?? 1.0;
                    $completion = self::STATUS_COMPLETION[$status] ?? 0.0;
                    
                    $totalWeightedProgress += ($completion * $weight);
                    $totalWeight += $weight;
                    
                    $phaseData[$phaseId]['totalWeightedProgress'] += ($completion * $weight);
                    $phaseData[$phaseId]['totalWeight'] += $weight;
                    
                    $totalTasks++;
                }
            }
        }

        // Handle case where all phases have no tasks
        if ($totalTasks === 0) {
            return [
                'progressPercentage' => 0.0,
                'totalTasks' => 0,
                'statusBreakdown' => [],
                'priorityBreakdown' => [],
                'weightedProgress' => 0.0,
                'phaseBreakdown' => [],
                'insights' => ['message' => 'No tasks found in any phase']
            ];
        }

        // Calculate phase-level progress
        $phaseBreakdown = self::calculatePhaseBreakdown($phaseData);
        
        // Calculate final progress percentage - weighted average of phase progress
        $progressPercentage = self::calculatePhaseWeightedProgress($phaseBreakdown);
        $simpleProgress = self::calculateSimpleProgress($statusCounts, $totalTasks);

        // Calculate status×priority combinations
        $combinationBreakdown = self::calculateStatusPriorityCombinations($phases);

        return [
            'progressPercentage' => round($progressPercentage, 2),
            'simpleProgressPercentage' => round($simpleProgress, 2),
            'totalTasks' => $totalTasks,
            'statusBreakdown' => self::formatStatusBreakdown($statusCounts, $totalTasks),
            'priorityBreakdown' => self::formatPriorityBreakdown($priorityCounts, $totalTasks),
            'combinationBreakdown' => $combinationBreakdown,
            'phaseBreakdown' => $phaseBreakdown,
            'weightedProgress' => round($progressPercentage, 2),
            'insights' => self::generateInsights($statusCounts, $priorityCounts, $totalTasks, $progressPercentage)
        ];
    }

    /**
     * Calculate progress breakdown by phase
     * 
     * @param array $phaseData Phase data with task counts and progress metrics
     * @return array Phase-level progress information
     */
    private static function calculatePhaseBreakdown(array $phaseData): array
    {
        $phaseBreakdown = [];
        
        foreach ($phaseData as $phaseId => $data) {
            $phaseProgress = $data['totalWeight'] > 0 
                ? ($data['totalWeightedProgress'] / $data['totalWeight']) 
                : 0.0;
            
            $completedTasks = $data['statusCounts'][WorkStatus::COMPLETED->value] ?? 0;
            $denominator = $data['totalTasks'] - $data['statusCounts'][WorkStatus::CANCELLED->value];
            $simpleProgress = $denominator > 0 
                ? ($completedTasks / $denominator) * 100 
                : 0.0;
            
            $phaseBreakdown[$phaseId] = [
                'phaseName' => $data['phaseName'],
                'totalTasks' => $data['totalTasks'],
                'completedTasks' => $completedTasks,
                'weightedProgress' => round($phaseProgress, 2),
                'simpleProgress' => round($simpleProgress, 2),
                'statusBreakdown' => self::formatPhaseStatusBreakdown($data['statusCounts'], $data['totalTasks']),
                'priorityBreakdown' => self::formatPriorityBreakdown($data['priorityCounts'], $data['totalTasks'])  
            ];
        }
        
        return $phaseBreakdown;
    }

    /**
     * Calculate phase-weighted project progress
     * Weight each phase by its task count for proportional impact
     * 
     * @param array $phaseBreakdown Phase breakdown data
     * @return float Weighted progress percentage
     */
    private static function calculatePhaseWeightedProgress(array $phaseBreakdown): float
    {
        if (empty($phaseBreakdown)) {
            return 0.0;
        }
        
        $totalTaskCount = 0;
        $totalWeightedProgress = 0.0;
        
        foreach ($phaseBreakdown as $phase) {
            $taskCount = $phase['totalTasks'];
            $progress = $phase['weightedProgress'];
            
            $totalTaskCount += $taskCount;
            $totalWeightedProgress += ($progress * $taskCount);
        }
        
        return $totalTaskCount > 0 ? ($totalWeightedProgress / $totalTaskCount) : 0.0;
    }

    /**
     * Format status breakdown for a specific phase
     * 
     * @param array $statusCounts Status counts for the phase
     * @param int $totalTasks Total tasks in the phase
     * @return array Formatted status breakdown
     */
    private static function formatPhaseStatusBreakdown(array $statusCounts, int $totalTasks): array
    {
        $breakdown = [];
        
        foreach (WorkStatus::cases() as $status) {
            $count = $statusCounts[$status->value] ?? 0;
            $percentage = $totalTasks > 0 ? ($count / $totalTasks) * 100 : 0.0;
            
            $breakdown[$status->value] = [
                'count' => $count,
                'percentage' => round($percentage, 1)
            ];
        }
        
        return $breakdown;
    }

    /**
     * Calculate simple progress (completed tasks / total tasks)
     */
    private static function calculateSimpleProgress(array $statusCounts, int $totalTasks): float
    {
        $completedTasks = $statusCounts[WorkStatus::COMPLETED->value] ?? 0;
        return $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0.0;
    }

    /**
     * Format status breakdown with percentages
     */
    private static function formatStatusBreakdown(array $statusCounts, int $totalTasks): array
    {
        $breakdown = [];
        
        foreach (WorkStatus::cases() as $status) {
            $count = $statusCounts[$status->value] ?? 0;
            $percentage = $totalTasks > 0 ? ($count / $totalTasks) * 100 : 0.0;
            
            $breakdown[$status->value] = [
                'count' => $count,
                'percentage' => round($percentage, 1),
                'displayName' => $status->getDisplayName()
            ];
        }

        return $breakdown;
    }

    /**
     * Format priority breakdown with percentages
     */
    private static function formatPriorityBreakdown(array $priorityCounts, int $totalTasks): array
    {
        $breakdown = [];
        
        foreach (TaskPriority::cases() as $priority) {
            $count = $priorityCounts[$priority->value] ?? 0;
            $percentage = $totalTasks > 0 ? ($count / $totalTasks) * 100 : 0.0;
            
            $breakdown[$priority->value] = [
                'count' => $count,
                'percentage' => round($percentage, 1),
                'display_name' => $priority->getDisplayName(),
                'weight' => self::PRIORITY_WEIGHTS[$priority->value]
            ];
        }

        return $breakdown;
    }

    /**
     * Calculate status×priority combinations
     * Returns a matrix of counts for each status-priority pair
     * 
     * @param array $phases All phases with their tasks
     * @return array 2D array of combinations
     */
    private static function calculateStatusPriorityCombinations(array $phases): array
    {
        $combinations = [];
        $totalTasks = 0;
        
        // Initialize combination matrix
        foreach (WorkStatus::cases() as $status) {
            foreach (TaskPriority::cases() as $priority) {
                $combinations[$status->value][$priority->value] = [
                    'count' => 0,
                    'percentage' => 0
                ];
            }
        }
        
        // Count tasks for each combination
        foreach ($phases as $phase) {
            $taskContainer = $phase->getTasks();
            if ($taskContainer && $taskContainer->count() > 0) {
                $tasks = $taskContainer->getItems();
                
                foreach ($tasks as $task) {
                    $status = $task->getStatus()->value;
                    $priority = $task->getPriority()->value;
                    $combinations[$status][$priority]['count']++;
                    $totalTasks++;
                }
            }
        }
        
        // Calculate percentages
        if ($totalTasks > 0) {
            foreach (WorkStatus::cases() as $status) {
                foreach (TaskPriority::cases() as $priority) {
                    $count = $combinations[$status->value][$priority->value]['count'];
                    $percentage = ($count / $totalTasks) * 100;
                    $combinations[$status->value][$priority->value]['percentage'] = round($percentage, 2);
                }
            }
        }
        
        return $combinations;
    }

    /**
     * Generate project insights and recommendations including phase analysis
     */
    private static function generateInsights(array $statusCounts, array $priorityCounts, int $totalTasks, float $progress): array
    {
        $insights = [];
        
        // Progress status
        if ($progress >= 90) {
            $insights[] = "Project is near completion - excellent progress!";
        } elseif ($progress >= 70) {
            $insights[] = "Project is on track with good progress.";
        } elseif ($progress >= 50) {
            $insights[] = "Project is progressing steadily.";
        } elseif ($progress >= 25) {
            $insights[] = "Project needs attention to improve progress.";
        } else {
            $insights[] = "Project requires immediate attention - low progress.";
        }

        // Delayed tasks warning
        $delayedTasks = $statusCounts[WorkStatus::DELAYED->value] ?? 0;
        if ($delayedTasks > 0) {
            $delayedPercentage = ($delayedTasks / $totalTasks) * 100;
            $insights[] = "Warning: {$delayedTasks} tasks ({$delayedPercentage}%) are delayed.";
        }

        // Cancelled tasks notice
        $cancelledTasks = $statusCounts[WorkStatus::CANCELLED->value] ?? 0;
        if ($cancelledTasks > 0) {
            $insights[] = "Note: {$cancelledTasks} tasks have been cancelled.";
        }

        // High priority tasks status
        $highPriorityTasks = $priorityCounts[TaskPriority::HIGH->value] ?? 0;
        $completedTasks = $statusCounts[WorkStatus::COMPLETED->value] ?? 0;
        
        if ($highPriorityTasks > $completedTasks) {
            $insights[] = "Focus needed: {$highPriorityTasks} high-priority tasks require attention.";
        }

        // Pending tasks
        $pendingTasks = $statusCounts[WorkStatus::PENDING->value] ?? 0;
        if ($pendingTasks > ($totalTasks * 0.3)) {
            $insights[] = "Many tasks are still pending - consider resource allocation.";
        }

        return [
            'messages' => $insights,
            'recommendations' => self::generateRecommendations($statusCounts, $priorityCounts, $totalTasks)
        ];
    }

    /**
     * Generate actionable recommendations
     */
    private static function generateRecommendations(array $statusCounts, array $priorityCounts, int $totalTasks): array
    {
        $recommendations = [];
        
        $ongoingTasks = $statusCounts[WorkStatus::ON_GOING->value] ?? 0;
        $pendingTasks = $statusCounts[WorkStatus::PENDING->value] ?? 0;
        $delayedTasks = $statusCounts[WorkStatus::DELAYED->value] ?? 0;
        $highPriorityTasks = $priorityCounts[TaskPriority::HIGH->value] ?? 0;

        // Resource allocation
        if ($ongoingTasks > ($totalTasks * 0.6)) {
            $recommendations[] = "Consider if team capacity is sufficient for current workload.";
        }

        // Priority focus
        if ($highPriorityTasks > 0) {
            $recommendations[] = "Prioritize high-priority tasks for maximum impact.";
        }

        // Delayed task management
        if ($delayedTasks > 0) {
            $recommendations[] = "Review delayed tasks and reassign resources if necessary.";
        }

        // Pending task activation
        if ($pendingTasks > ($totalTasks * 0.4)) {
            $recommendations[] = "Activate pending tasks to maintain project momentum.";
        }

        return $recommendations;
    }
}

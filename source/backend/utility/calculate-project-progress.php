<?php

require_once ENUM_PATH . 'project-task-status.php';
require_once ENUM_PATH . 'task-priority.php';

/**
 * Utility class for calculating project progress based on task statuses and priorities
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
        ProjectTaskStatus::PENDING->value => 0.0,
        ProjectTaskStatus::ON_GOING->value => 50.0,
        ProjectTaskStatus::COMPLETED->value => 100.0,
        ProjectTaskStatus::DELAYED->value => 25.0,  // Partial progress assumed
        ProjectTaskStatus::CANCELLED->value => 0.0  // No progress counted
    ];

    /**
     * Calculate overall project progress
     * 
     * @param array $tasks Array of tasks with 'status' and 'priority' keys
     * @return array Progress data with percentage, breakdown, and insights
     */
    public static function calculateProjectProgress(TaskContainer $tasks): array
    {
        if (empty($tasks)) {
            return [
                'progressPercentage' => 0.0,
                'totalTasks' => 0,
                'statusBreakdown' => [],
                'priorityBreakdown' => [],
                'weightedProgress' => 0.0,
                'insights' => ['message' => 'No tasks found in project']
            ];
        }

        // Initialize counters
        $statusCounts = [];
        $priorityCounts = [];
        $totalWeightedProgress = 0.0;
        $totalWeight = 0.0;
        $totalTasks = count($tasks);

        // Process each task
        foreach ($tasks as $task) {
            $status = $task->getStatus()->value;
            $priority = $task->getPriority()->value;

            // Count by status
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            
            // Count by priority
            $priorityCounts[$priority] = ($priorityCounts[$priority] ?? 0) + 1;

            // Calculate weighted progress
            $weight = self::PRIORITY_WEIGHTS[$priority] ?? 1.0;
            $completion = self::STATUS_COMPLETION[$status] ?? 0.0;
            
            $totalWeightedProgress += ($completion * $weight);
            $totalWeight += $weight;
        }

        // Calculate final progress percentage
        $progressPercentage = $totalWeight > 0 ? ($totalWeightedProgress / $totalWeight) : 0.0;
        $simpleProgress = self::calculateSimpleProgress($statusCounts, $totalTasks);

        return [
            'progressPercentage' => round($progressPercentage, 2),
            'simpleProgressPercentage' => round($simpleProgress, 2),
            'totalTasks' => $totalTasks,
            'statusBreakdown' => self::formatStatusBreakdown($statusCounts, $totalTasks),
            'priorityBreakdown' => self::formatPriorityBreakdown($priorityCounts, $totalTasks),
            'weightedProgress' => round($progressPercentage, 2),
            'insights' => self::generateInsights($statusCounts, $priorityCounts, $totalTasks, $progressPercentage)
        ];
    }

    /**
     * Calculate simple progress (completed tasks / total tasks)
     */
    private static function calculateSimpleProgress(array $statusCounts, int $totalTasks): float
    {
        $completedTasks = $statusCounts[ProjectTaskStatus::COMPLETED->value] ?? 0;
        return $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0.0;
    }

    /**
     * Format status breakdown with percentages
     */
    private static function formatStatusBreakdown(array $statusCounts, int $totalTasks): array
    {
        $breakdown = [];
        
        foreach (ProjectTaskStatus::cases() as $status) {
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
     * Generate project insights and recommendations
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
        $delayedTasks = $statusCounts[ProjectTaskStatus::DELAYED->value] ?? 0;
        if ($delayedTasks > 0) {
            $delayedPercentage = ($delayedTasks / $totalTasks) * 100;
            $insights[] = "Warning: {$delayedTasks} tasks ({$delayedPercentage}%) are delayed.";
        }

        // Cancelled tasks notice
        $cancelledTasks = $statusCounts[ProjectTaskStatus::CANCELLED->value] ?? 0;
        if ($cancelledTasks > 0) {
            $insights[] = "Note: {$cancelledTasks} tasks have been cancelled.";
        }

        // High priority tasks status
        $highPriorityTasks = $priorityCounts[TaskPriority::HIGH->value] ?? 0;
        $completedTasks = $statusCounts[ProjectTaskStatus::COMPLETED->value] ?? 0;
        
        if ($highPriorityTasks > $completedTasks) {
            $insights[] = "Focus needed: {$highPriorityTasks} high-priority tasks require attention.";
        }

        // Pending tasks
        $pendingTasks = $statusCounts[ProjectTaskStatus::PENDING->value] ?? 0;
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
        
        $ongoingTasks = $statusCounts[ProjectTaskStatus::ON_GOING->value] ?? 0;
        $pendingTasks = $statusCounts[ProjectTaskStatus::PENDING->value] ?? 0;
        $delayedTasks = $statusCounts[ProjectTaskStatus::DELAYED->value] ?? 0;
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

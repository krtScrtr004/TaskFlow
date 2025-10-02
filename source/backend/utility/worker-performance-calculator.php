<?php

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

    // Time-based bonuses
    private const EARLY_COMPLETION_BONUS = 1.2;  // 20% bonus for early completion
    private const ON_TIME_MULTIPLIER = 1.0;      // Standard multiplier
    private const LATE_PENALTY = 0.8;            // 20% penalty for late completion

    /**
     * Calculate overall worker performance score
     * 
     * @param TaskContainer $tasks Container of tasks assigned to the worker
     * @return array Comprehensive performance data
     */
    public static function calculateWorkerPerformance(TaskContainer $tasks): array {
        if ($tasks->count() < 1) {
            return [
                'overallScore' => 0.0,
                'totalTasks' => 0,
                'performanceGrade' => 'N/A',
                'breakdown' => [],
                'insights' => ['No tasks found for evaluation period']
            ];
        }

        $totalScore = 0.0;
        $maxPossibleScore = 0.0;
        $taskBreakdown = [];
        $priorityStats = [];
        $statusStats = [];
        $timeStats = ['early' => 0, 'onTime' => 0, 'late' => 0];

        foreach ($tasks as $task) {
            $taskScore = self::calculateTaskScore($task);
            $taskBreakdown[] = $taskScore;
            
            $priority = $task->getPriority()->value ?? TaskPriority::MEDIUM->value;
            $status = $task->getStatus()->value ?? WorkStatus::PENDING->value;
            
            // Accumulate scores
            $totalScore += $taskScore['weightedScore'];
            $maxPossibleScore += $taskScore['maxPossibleScore'];
            
            // Priority statistics
            $priorityStats[$priority] = ($priorityStats[$priority] ?? 0) + 1;
            
            // Status statistics
            $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
            
            // Time performance statistics
            if (isset($taskScore['timePerformance'])) {
                $timeStats[$taskScore['timePerformance']]++;
            }
        }

        // Calculate final performance score (0-100)
        $performanceScore = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;

        return [
            'overallScore' => round($performanceScore, 2),
            'totalTasks' => count($tasks),
            'performanceGrade' => self::getPerformanceGrade($performanceScore),
            'rawScore' => round($totalScore, 2),
            'maxPossibleScore' => round($maxPossibleScore, 2),
            'insights' => self::generatePerformanceInsights($performanceScore, $priorityStats, $statusStats, $timeStats),
            'recommendations' => self::generateRecommendations($performanceScore, $taskBreakdown)
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
    private static function getPerformanceGrade(float $score): string
    {
        if ($score >= 90) return 'A+ (Exceptional)';
        if ($score >= 85) return 'A (Excellent)';
        if ($score >= 80) return 'B+ (Very Good)';
        if ($score >= 75) return 'B (Good)';
        if ($score >= 70) return 'C+ (Above Average)';
        if ($score >= 65) return 'C (Average)';
        if ($score >= 60) return 'D+ (Below Average)';
        if ($score >= 50) return 'D (Poor)';
        return 'F (Failing)';
    }

    /**
     * Generate performance insights
     */
    private static function generatePerformanceInsights(float $score, array $priorityStats, array $statusStats, array $timeStats): array
    {
        $insights = [];

        // Overall performance insight
        if ($score >= 85) {
            $insights[] = "Excellent performance! Worker consistently delivers high-quality work.";
        } elseif ($score >= 70) {
            $insights[] = "Good performance with room for improvement.";
        } elseif ($score >= 50) {
            $insights[] = "Average performance. Consider additional support or training.";
        } else {
            $insights[] = "Performance needs significant improvement. Immediate attention required.";
        }

        // Priority-based insights
        $highPriorityTasks = $priorityStats[TaskPriority::HIGH->value] ?? 0;
        if ($highPriorityTasks > 0) {
            $insights[] = "Worker has handled {$highPriorityTasks} high-priority tasks.";
        }

        // Status-based insights
        $completedTasks = $statusStats[WorkStatus::COMPLETED->value] ?? 0;
        $delayedTasks = $statusStats[WorkStatus::DELAYED->value] ?? 0;
        
        if ($delayedTasks > 0) {
            $insights[] = "Warning: {$delayedTasks} tasks are currently delayed.";
        }

        // Time performance insights
        $earlyCompletions = $timeStats['early'] ?? 0;
        $lateCompletions = $timeStats['late'] ?? 0;
        
        if ($earlyCompletions > $lateCompletions) {
            $insights[] = "Strong time management - frequently completes tasks early.";
        } elseif ($lateCompletions > 0) {
            $insights[] = "Time management needs improvement - some late completions.";
        }

        return $insights;
    }

    /**
     * Generate actionable recommendations
     */
    private static function generateRecommendations(float $score, array $taskBreakdown): array
    {
        $recommendations = [];

        if ($score < 70) {
            $recommendations[] = "Consider additional training or mentoring";
            $recommendations[] = "Review task assignment strategy";
        }

        // Check for patterns in low-performing tasks
        $lowPerformingTasks = array_filter($taskBreakdown, fn($task) => $task['weightedScore'] < 2.0);
        if (count($lowPerformingTasks) > 0) {
            $recommendations[] = "Focus on improving performance in lower-priority tasks";
        }

        // Check time performance
        $lateCount = count(array_filter($taskBreakdown, fn($task) => $task['timePerformance'] === 'late'));
        if ($lateCount > 0) {
            $recommendations[] = "Implement better time management strategies";
            $recommendations[] = "Consider breaking down complex tasks into smaller milestones";
        }

        if ($score >= 85) {
            $recommendations[] = "Consider assigning more high-priority tasks";
            $recommendations[] = "Potential candidate for leadership or mentoring roles";
        }

        return $recommendations;
    }
}
    
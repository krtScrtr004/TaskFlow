<?php

namespace App\Utility;

use App\Container\ProjectContainer;
use App\Enumeration\WorkStatus;

require_once ENUM_PATH . 'work-status.php';
require_once ENUM_PATH . 'task-priority.php';
require_once BE_UTILITY_PATH . 'project-progress-calculator.php';

/**
 * Utility class for calculating project manager performance based on managed projects
 */
class ProjectManagerPerformanceCalculator
{
    // Project status weights - higher weight for successful completion
    private const PROJECT_STATUS_WEIGHTS = [
        WorkStatus::COMPLETED->value => 1.0,    // Full credit
        WorkStatus::ON_GOING->value => 0.6,     // Partial credit for in-progress
        WorkStatus::DELAYED->value => 0.3,      // Reduced credit for delays
        WorkStatus::PENDING->value => 0.2,      // Minimal credit for pending
        WorkStatus::CANCELLED->value => -0.5    // Penalty for cancellations
    ];

    // Performance scoring weights for different metrics
    private const METRIC_WEIGHTS = [
        'projectCompletion' => 0.35,      // 35% - Project delivery success
        'timeManagement' => 0.30,         // 30% - On-time delivery
        'projectProgress' => 0.35         // 35% - Actual progress on ongoing projects
    ];

    // Time performance bonuses and penalties
    private const EARLY_DELIVERY_BONUS = 1.3;     // 30% bonus
    private const ON_TIME_MULTIPLIER = 1.0;       // Standard
    private const LATE_PENALTY = 0.7;             // 30% penalty
    private const SEVERELY_LATE_PENALTY = 0.4;    // 60% penalty (>20% overdue)

    /**
     * Calculate comprehensive project manager performance
     * 
     * @param ProjectContainer $projects Container of Project objects managed by the PM
     * @return array Detailed performance metrics and insights
     */
    public static function calculate(ProjectContainer $projects): array
    {
        if (empty($projects)) {
            return [
                'overallScore' => 0.0,
                'performanceGrade' => 'N/A',
                'totalProjects' => 0,
                'metrics' => [],
                'insights' => self::generateNoDataInsights()
            ];
        }

        $totalProjects = count($projects);
        
        // Calculate individual metrics
        $completionScore = self::calculateProjectCompletionScore($projects);
        $timeScore = self::calculateTimeManagementScore($projects);
        $progressScore = self::calculateProjectProgressScore($projects);

        // Calculate weighted overall score
        $overallScore = (
            $completionScore['score'] * self::METRIC_WEIGHTS['projectCompletion'] +
            $timeScore['score'] * self::METRIC_WEIGHTS['timeManagement'] +
            $progressScore['score'] * self::METRIC_WEIGHTS['projectProgress']
        );

        // Gather statistics
        $statistics = self::gatherProjectStatistics($projects);

        return [
            'overallScore' => round($overallScore, 2),
            'performanceGrade' => self::getPerformanceGrade($overallScore),
            'totalProjects' => $totalProjects,
            'metrics' => [
                'projectCompletion' => $completionScore,
                'timeManagement' => $timeScore,
                'projectProgress' => $progressScore,
            ],
            'statistics' => $statistics,
            'insights' => self::generateInsights($overallScore, $completionScore, $timeScore, $progressScore, $statistics),
            'recommendations' => self::generateRecommendations($completionScore, $timeScore, $progressScore, $statistics)
        ];
    }

    /**
     * Calculate project completion effectiveness score (0-100)
     */
    private static function calculateProjectCompletionScore(ProjectContainer $projects): array
    {
        $statusCounts = [];
        $totalWeightedScore = 0.0;
        $maxPossibleScore = 0.0;

        foreach ($projects as $project) {
            $status = $project->getStatus()->value;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            $weight = self::PROJECT_STATUS_WEIGHTS[$status] ?? 0.0;
            $totalWeightedScore += $weight;
            $maxPossibleScore += 1.0; // Maximum weight is 1.0 for completed
        }

        $score = $maxPossibleScore > 0 ? ($totalWeightedScore / $maxPossibleScore) * 100 : 0.0;
        
        return [
            'score' => round($score, 2),
            'statusBreakdown' => $statusCounts,
            'description' => 'Project delivery and completion effectiveness'
        ];
    }

    /**
     * Calculate project progress score based on actual task completion (0-100)
     * Uses ProjectProgressCalculator to assess real progress on each project
     */
    private static function calculateProjectProgressScore(ProjectContainer $projects): array
    {
        $totalProgressScore = 0.0;
        $evaluatedProjects = 0;
        $progressStats = [
            'highProgress' => 0,      // >= 75%
            'moderateProgress' => 0,  // 50-74%
            'lowProgress' => 0,       // 25-49%
            'minimalProgress' => 0    // < 25%
        ];
        
        foreach ($projects as $project) {
            $phases = $project->getPhases();
            
            // Skip projects with no phases or tasks
            if (!$phases || $phases->count() === 0) {
                continue;
            }
            
            // Calculate actual project progress
            $progressData = ProjectProgressCalculator::calculate($phases);
            $progressPercentage = $progressData['progressPercentage'];
            
            // Add to total score
            $totalProgressScore += $progressPercentage;
            $evaluatedProjects++;
            
            // Categorize progress
            if ($progressPercentage >= 75) {
                $progressStats['highProgress']++;
            } elseif ($progressPercentage >= 50) {
                $progressStats['moderateProgress']++;
            } elseif ($progressPercentage >= 25) {
                $progressStats['lowProgress']++;
            } else {
                $progressStats['minimalProgress']++;
            }
        }
        
        $score = $evaluatedProjects > 0 ? ($totalProgressScore / $evaluatedProjects) : 0.0;
        
        return [
            'score' => round($score, 2),
            'evaluatedProjects' => $evaluatedProjects,
            'progressDistribution' => $progressStats,
            'description' => 'Actual task completion progress across all managed projects'
        ];
    }

    /**
     * Calculate time management score (0-100)
     */
    private static function calculateTimeManagementScore(ProjectContainer $projects): array
    {
        $totalTimeScore = 0.0;
        $completedProjects = 0;
        $timeStats = [
            'earlyDelivery' => 0,
            'onTime' => 0,
            'late' => 0,
            'severelyLate' => 0
        ];

        foreach ($projects as $project) {
            $status = $project->getStatus()->value;
            $completionDate = $project->getCompletionDateTime();
            $actualCompletionDate = $project->getActualCompletionDateTime();

            // Only evaluate completed projects for time management
            if ($status !== WorkStatus::COMPLETED->value || !$actualCompletionDate) {
                continue;
            }

            $completedProjects++;
            $daysDifference = $actualCompletionDate->diff($completionDate)->days;
            $isLate = $actualCompletionDate > $completionDate;
            
            $plannedDuration = $project->getStartDateTime()->diff($completionDate)->days;
            $delayPercentage = $plannedDuration > 0 ? ($daysDifference / $plannedDuration) * 100 : 0;

            if (!$isLate) {
                // Delivered early
                $totalTimeScore += 100 * self::EARLY_DELIVERY_BONUS;
                $timeStats['earlyDelivery']++;
            } elseif ($daysDifference <= 2) {
                // On time (within 2 days grace period)
                $totalTimeScore += 100 * self::ON_TIME_MULTIPLIER;
                $timeStats['onTime']++;
            } elseif ($delayPercentage <= 20) {
                // Late but less than 20% overdue
                $totalTimeScore += 100 * self::LATE_PENALTY;
                $timeStats['late']++;
            } else {
                // Severely late (more than 20% overdue)
                $totalTimeScore += 100 * self::SEVERELY_LATE_PENALTY;
                $timeStats['severelyLate']++;
            }
        }

        $score = $completedProjects > 0 ? ($totalTimeScore / $completedProjects) : 0.0;

        return [
            'score' => round($score, 2),
            'completedProjects' => $completedProjects,
            'timePerformance' => $timeStats,
            'description' => 'On-time project delivery track record'
        ];
    }

    /**
     * Gather comprehensive project statistics
     */
    private static function gatherProjectStatistics(ProjectContainer $projects): array
    {
        $stats = [
            'total' => count($projects),
            'byStatus' => [],
            'averageBudget' => 0.0,
            'totalBudget' => 0.0,
            'averageTasksPerProject' => 0.0,
            'totalTasks' => 0
        ];

        $totalBudget = 0.0;
        $totalTasks = 0;
        $projectsWithBudget = 0;

        foreach ($projects as $project) {
            $status = $project->getStatus()->value;
            $stats['byStatus'][$status] = ($stats['byStatus'][$status] ?? 0) + 1;

            $budget = $project->getBudget();
            if ($budget > 0) {
                $totalBudget += $budget;
                $projectsWithBudget++;
            }

            $tasks = $project->getTasks();
            if ($tasks) {
                $totalTasks += $tasks->count();
            }
        }

        $stats['averageBudget'] = $projectsWithBudget > 0 ? round($totalBudget / $projectsWithBudget, 2) : 0.0;
        $stats['totalBudget'] = round($totalBudget, 2);
        $stats['averageTasksPerProject'] = count($projects) > 0 ? round($totalTasks / count($projects), 1) : 0.0;
        $stats['totalTasks'] = $totalTasks;

        return $stats;
    }

    /**
     * Generate performance grade based on overall score
     */
    private static function getPerformanceGrade(float $score): string
    {
        if ($score >= 95) return 'A+ (Outstanding)';
        if ($score >= 90) return 'A (Excellent)';
        if ($score >= 85) return 'B+ (Very Good)';
        if ($score >= 80) return 'B (Good)';
        if ($score >= 75) return 'C+ (Above Average)';
        if ($score >= 70) return 'C (Average)';
        if ($score >= 65) return 'D+ (Below Average)';
        if ($score >= 60) return 'D (Poor)';
        return 'F (Needs Improvement)';
    }

    /**
     * Generate performance insights
     */
    private static function generateInsights(
        float $overallScore,
        array $completionScore,
        array $timeScore,
        array $progressScore,
        array $statistics
    ): array {
        $insights = [];

        // Overall performance insight
        if ($overallScore >= 85) {
            $insights[] = "Exceptional project management performance! Consistently delivers high-quality projects.";
        } elseif ($overallScore >= 70) {
            $insights[] = "Good project management with solid track record. Some areas for improvement identified.";
        } elseif ($overallScore >= 50) {
            $insights[] = "Average performance with significant room for improvement in multiple areas.";
        } else {
            $insights[] = "Performance needs immediate attention. Critical improvement required.";
        }

        // Completion insights
        $completedCount = $completionScore['statusBreakdown'][WorkStatus::COMPLETED->value] ?? 0;
        $completionRate = ($completedCount / $statistics['total']) * 100;
        if ($completionRate >= 80) {
            $insights[] = "Strong project completion rate at {$completionRate}%.";
        } elseif ($completionRate < 50) {
            $insights[] = "Low project completion rate ({$completionRate}%) - focus on delivering projects.";
        }

        // Time management insights
        if ($timeScore['completedProjects'] > 0) {
            $onTimeCount = $timeScore['timePerformance']['onTime'] + $timeScore['timePerformance']['earlyDelivery'];
            if ($onTimeCount >= $timeScore['completedProjects'] * 0.7) {
                $insights[] = "Strong time management - majority of projects delivered on schedule.";
            }
            if ($timeScore['timePerformance']['severelyLate'] > 0) {
                $insights[] = "Concern: Some projects severely delayed. Review planning and resource allocation.";
            }
        }

        // Project progress insights
        if ($progressScore['evaluatedProjects'] > 0) {
            $avgProgress = $progressScore['score'];
            if ($avgProgress >= 80) {
                $insights[] = "Excellent progress tracking - projects are advancing steadily towards completion.";
            } elseif ($avgProgress >= 60) {
                $insights[] = "Good progress on active projects - maintain momentum to meet deadlines.";
            } elseif ($avgProgress < 40) {
                $insights[] = "Warning: Low average progress across projects. Consider resource reallocation.";
            }
            
            $highProgressCount = $progressScore['progressDistribution']['highProgress'];
            $minimalProgressCount = $progressScore['progressDistribution']['minimalProgress'];
            
            if ($minimalProgressCount > 0) {
                $insights[] = "{$minimalProgressCount} project(s) with minimal progress (<25%) - immediate attention required.";
            }
            if ($highProgressCount >= $progressScore['evaluatedProjects'] * 0.6) {
                $insights[] = "Strong execution - majority of projects showing high progress (≥75%).";
            }
        }

        return $insights;
    }

    /**
     * Generate actionable recommendations
     */
    private static function generateRecommendations(
        array $completionScore,
        array $timeScore,
        array $progressScore,
        array $statistics
    ): array {
        $recommendations = [];

        // Completion recommendations
        $cancelledCount = $completionScore['statusBreakdown'][WorkStatus::CANCELLED->value] ?? 0;
        if ($cancelledCount > 0) {
            $recommendations[] = "Investigate reasons for cancelled projects and implement preventive measures.";
        }

        $delayedCount = $completionScore['statusBreakdown'][WorkStatus::DELAYED->value] ?? 0;
        if ($delayedCount >= $statistics['total'] * 0.3) {
            $recommendations[] = "High number of delayed projects - review resource allocation and planning processes.";
        }

        // Time management recommendations
        if ($timeScore['score'] < 70) {
            $recommendations[] = "Enhance project scheduling and milestone tracking.";
            $recommendations[] = "Consider implementing agile methodologies for better time management.";
        }

        if ($timeScore['timePerformance']['late'] + $timeScore['timePerformance']['severelyLate'] > 0) {
            $recommendations[] = "Analyze causes of delays and implement corrective actions.";
            $recommendations[] = "Build buffer time into project schedules to accommodate unforeseen challenges.";
        }

        // Progress-based recommendations
        if ($progressScore['evaluatedProjects'] > 0) {
            $minimalCount = $progressScore['progressDistribution']['minimalProgress'];
            $lowCount = $progressScore['progressDistribution']['lowProgress'];
            
            if ($minimalCount > 0) {
                $recommendations[] = "Urgently address projects with minimal progress - identify blockers and reallocate resources.";
            }
            
            if ($progressScore['score'] < 50) {
                $recommendations[] = "Implement weekly progress reviews to identify and resolve bottlenecks early.";
                $recommendations[] = "Consider breaking down large tasks into smaller, manageable units for better tracking.";
            }
            
            if (($minimalCount + $lowCount) >= $progressScore['evaluatedProjects'] * 0.4) {
                $recommendations[] = "Review team capacity and consider hiring or reassigning resources.";
                $recommendations[] = "Evaluate if project scope needs adjustment or timeline extension.";
            }
        }

        // General recommendations
        if (count($recommendations) === 0) {
            $recommendations[] = "Continue maintaining high standards of project management.";
            $recommendations[] = "Share best practices across the organization.";
            $recommendations[] = "Consider mentoring other project managers.";
        }

        return $recommendations;
    }

    /**
     * Generate insights when no data is available
     */
    private static function generateNoDataInsights(): array
    {
        return [
            'No projects found for evaluation period.',
            'Start managing projects to build performance history.',
            'Performance metrics will be calculated as projects are completed.'
        ];
    }
}

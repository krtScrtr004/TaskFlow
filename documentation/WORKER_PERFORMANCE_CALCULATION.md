# Worker Performance Calculation System

## Overview

The TaskFlow Worker Performance Calculation System is a comprehensive, multi-dimensional scoring mechanism that evaluates worker productivity and efficiency across projects, phases, and tasks. The system operates within TaskFlow's hierarchical structure (**Project → Phase → Task**) and uses a weighted scoring algorithm that considers **task priority**, **completion status**, **deadline adherence**, **worker reliability**, and **project involvement** to generate a holistic performance score ranging from 0 to 100.

The system includes a **termination penalty mechanism** that deducts points for workers terminated from tasks or projects, ensuring accountability and reliability are factored into performance evaluations.

---

## Table of Contents

1. [Core Concepts](#core-concepts)
2. [Mathematical Foundation](#mathematical-foundation)
3. [Implementation Details](#implementation-details)
4. [Formula Breakdown](#formula-breakdown)
5. [Database Query Implementation](#database-query-implementation)
6. [PHP Class Implementation](#php-class-implementation)
7. [Scoring Examples](#scoring-examples)
8. [Performance Grading System](#performance-grading-system)
9. [Best Practices](#best-practices)

---

## Core Concepts

### 1. Four-Dimensional Scoring Model

The performance calculation is based on four key dimensions:

```
Worker Performance = f(Priority, Status, Timing, Reliability)
```

- **Priority Dimension**: Weight assigned based on task importance
- **Status Dimension**: Credit multiplier based on task completion state
- **Timing Dimension**: Bonus/penalty based on deadline adherence
- **Reliability Dimension**: Penalty deductions for terminations from tasks/projects

### 2. Weighted Aggregation

Individual task scores are aggregated across all tasks, normalized against the maximum possible score, and converted to a percentage scale (0-100).

---

## Mathematical Foundation

### Base Formula

The overall performance score is calculated using the following formula:

```
Base Score = (Σ Task Scores / Σ Max Possible Scores) × 100
Overall Score = max(0, Base Score - Total Penalties)
```

Where **Total Penalties** include:
- Task-level terminations: 15% per terminated task assignment
- Project-level terminations: 25% per terminated project assignment

And each **Task Score** is calculated as:

```
Task Score = Priority Weight × Status Multiplier × Time Multiplier
```

And each **Max Possible Score** (theoretical maximum) is:

```
Max Possible Score = Priority Weight × 1.0 × 1.2
```

The denominator represents the best-case scenario where all tasks are completed early with full credit.

---

## Formula Breakdown

### Component 1: Priority Weight (W_p)

Priority weights reflect the importance and complexity of tasks:

| Priority | Weight (W_p) | Rationale |
|----------|--------------|-----------|
| **High** | 5.0 | Critical tasks requiring immediate attention |
| **Medium** | 3.0 | Standard tasks with moderate importance |
| **Low** | 1.0 | Optional or less urgent tasks |

**Mathematical Representation:**

```
W_p = {
    5.0,  if priority = 'high'
    3.0,  if priority = 'medium'
    1.0,  if priority = 'low'
}
```

### Component 2: Status Multiplier (M_s)

Status multipliers determine how much credit a worker receives based on task completion state:

| Status | Multiplier (M_s) | Credit % | Description |
|--------|------------------|----------|-------------|
| **Completed** | 1.0 | 100% | Task finished and delivered |
| **On Going** | 0.5 | 50% | Work in progress, partial credit |
| **Delayed** | 0.3 | 30% | Behind schedule, reduced credit |
| **Pending** | 0.0 | 0% | Not started, no credit |
| **Cancelled** | 0.0 | 0% | Abandoned, no credit |

**Mathematical Representation:**

```
M_s = {
    1.0,  if status = 'completed'
    0.5,  if status = 'onGoing'
    0.3,  if status = 'delayed'
    0.0,  if status ∈ {'pending', 'cancelled'}
}
```

**Rationale:**
- Only completed tasks receive full credit
- Ongoing tasks receive 50% credit to acknowledge active work
- Delayed tasks receive 30% to reflect reduced efficiency
- No credit is given for tasks not yet started or abandoned

### Component 3: Time Multiplier (M_t)

Time multipliers apply bonuses or penalties based on deadline adherence:

| Condition | Multiplier (M_t) | Effect | Threshold |
|-----------|------------------|--------|-----------|
| **Early Completion** | 1.2 | +20% bonus | `actualCompletion < deadline` |
| **On-Time Completion** | 1.0 | Standard | `actualCompletion ≤ deadline + 1 day` |
| **Late Completion** | 0.8 | -20% penalty | `actualCompletion > deadline + 1 day` |

**Mathematical Representation:**

```
M_t = {
    1.2,  if T_actual < T_deadline              (Early)
    1.0,  if T_deadline ≤ T_actual ≤ T_deadline + 1d  (On-Time)
    0.8,  if T_actual > T_deadline + 1d         (Late)
    1.0,  if status ≠ 'completed'               (N/A)
}
```

Where:
- `T_actual` = Actual completion timestamp
- `T_deadline` = Planned completion timestamp
- `1d` = Grace period of 1 day

**Rationale:**
- Rewards proactive workers who finish ahead of schedule
- Standard credit for on-time delivery (including 1-day grace period)
- Penalizes late completions to encourage timely delivery
- Only applies to completed tasks

### Component 4: Termination Penalties (P_r)

Termination penalties are applied to workers removed from tasks or projects:

| Termination Level | Penalty | Description |
|------------------|---------|-------------|
| **Task Termination** | -15% | Worker removed from individual task assignment |
| **Project Termination** | -25% | Worker removed from entire project |

**Mathematical Representation:**

```
Total Penalty = (Task Terminations × 15%) + (Project Terminations × 25%)
Overall Score = max(0, Base Score - Total Penalty)
```

**Rationale:**
- **Task Termination (-15%)**: Indicates issues at the task level (missed deliverables, poor quality, conflicts)
- **Project Termination (-25%)**: More severe penalty for complete project removal (systematic issues, trust breakdown)
- **Cumulative**: Multiple terminations compound penalties, reflecting pattern of unreliability
- **Floor at Zero**: Score cannot go below 0% regardless of penalty magnitude

**Examples:**
- Base Score: 85%, 1 task termination → 85% - 15% = **70%**
- Base Score: 90%, 1 project termination → 90% - 25% = **65%**
- Base Score: 80%, 2 task + 1 project terminations → 80% - (30% + 25%) = **25%**
- Base Score: 30%, 3 project terminations → max(0, 30% - 75%) = **0%**

---

## Implementation Details

### Full Task Score Formula

Combining all three components, the score for task `i` is:

```
S_i = W_p(i) × M_s(i) × M_t(i)
```

### Maximum Possible Score for Task

The theoretical maximum score for task `i` (early completion with full credit):

```
S_max(i) = W_p(i) × 1.0 × 1.2
```

### Overall Performance Score

The final performance score across all `n` tasks, with reliability penalties:

```
Base Score = (Σ(i=1 to n) S_i / Σ(i=1 to n) S_max(i)) × 100
Total Penalty = (N_task_term × 15) + (N_proj_term × 25)
Overall Score = max(0, Base Score - Total Penalty)
```

Where:
- `N_task_term` = Number of task-level terminations
- `N_proj_term` = Number of project-level terminations

This normalization ensures:
- Scores are always between 0 and 100
- Workers with different task loads can be compared fairly
- Priority distribution is accounted for in the denominator
- Reliability issues (terminations) reduce final scores appropriately

---

## Database Query Implementation

The SQL implementation in `project-model.php` (method `topWorkersQuery`) calculates performance directly in the database for efficiency, including termination penalty tracking.

### SQL Formula Structure

```sql
-- Inner query calculates base score
ROUND(
    (
        SUM(/* Numerator: Actual Scores */) / 
        SUM(/* Denominator: Max Possible Scores */)
    ) * 100, 
    2
) as baseScore,

-- Count terminations
(/* Subquery for task terminations */) as taskTerminations,
(/* Subquery for project terminations */) as projectTerminations,

-- Calculate penalty
(taskTerminations * 15.0) + (projectTerminations * 25.0) as totalPenalty,

-- Outer query applies penalty
GREATEST(0, baseScore - totalPenalty) as overallScore
```

### Numerator: Actual Task Scores

```sql
SUM(
    CASE 
        -- COMPLETED TASKS: Full weight × time performance
        WHEN pt.status = 'completed' THEN
            CASE 
                WHEN pt.priority = 'high' THEN 5.0
                WHEN pt.priority = 'medium' THEN 3.0
                WHEN pt.priority = 'low' THEN 1.0
                ELSE 1.0
            END
            *
            CASE 
                WHEN pt.actualCompletionDateTime < pt.completionDateTime THEN 1.2  -- Early
                WHEN pt.actualCompletionDateTime <= DATE_ADD(pt.completionDateTime, INTERVAL 1 DAY) THEN 1.0  -- On-time
                ELSE 0.8  -- Late
            END
        
        -- ON-GOING TASKS: 50% of priority weight
        WHEN pt.status = 'onGoing' THEN
            CASE 
                WHEN pt.priority = 'high' THEN 5.0 * 0.5
                WHEN pt.priority = 'medium' THEN 3.0 * 0.5
                WHEN pt.priority = 'low' THEN 1.0 * 0.5
                ELSE 0.5
            END
        
        -- DELAYED TASKS: 30% of priority weight
        WHEN pt.status = 'delayed' THEN
            CASE 
                WHEN pt.priority = 'high' THEN 5.0 * 0.3
                WHEN pt.priority = 'medium' THEN 3.0 * 0.3
                WHEN pt.priority = 'low' THEN 1.0 * 0.3
                ELSE 0.3
            END
        
        -- PENDING/CANCELLED: No credit
        ELSE 0
    END
)
```

### Denominator: Maximum Possible Scores

```sql
SUM(
    CASE 
        WHEN pt.priority = 'high' THEN 5.0 * 1.2    -- 6.0
        WHEN pt.priority = 'medium' THEN 3.0 * 1.2  -- 3.6
        WHEN pt.priority = 'low' THEN 1.0 * 1.2     -- 1.2
        ELSE 1.2
    END
)
```

### Complete Query Example

```sql
SELECT 
    ws.id,
    ws.firstName,
    ws.lastName,
    ws.email,
    ws.totalTasks,
    ws.completedTasks,
    ws.baseScore,
    ws.taskTerminations,
    ws.projectTerminations,
    ws.totalPenalty,
    GREATEST(0, ws.baseScore - ws.totalPenalty) as overallScore
FROM (
    SELECT 
        u.id,
        u.firstName,
        u.lastName,
        u.email,
        COUNT(DISTINCT ptw.taskId) as totalTasks,
        (
            SELECT COUNT(DISTINCT pt2.id)
            FROM phaseTask AS pt2
            INNER JOIN phaseTaskWorker AS ptw2 ON pt2.id = ptw2.taskId
            WHERE pt2.status = 'completed'
            AND ptw2.workerId = u.id
        ) as completedTasks,
        -- Base performance score (before penalties)
        ROUND(
            (SUM(/* Task score calculation */) / SUM(/* Max score calculation */)) * 100,
            2
        ) as baseScore,
        -- Count task-level terminations
        (
            SELECT COUNT(*)
            FROM phaseTaskWorker AS ptw3
            INNER JOIN phaseTask AS pt3 ON ptw3.taskId = pt3.id
            INNER JOIN projectPhase AS pp3 ON pt3.phaseId = pp3.id
            WHERE ptw3.workerId = u.id
            AND pp3.projectId = p.id
            AND ptw3.status = 'terminated'
        ) as taskTerminations,
        -- Count project-level terminations
        (
            SELECT COUNT(*)
            FROM projectWorker AS pw2
            WHERE pw2.workerId = u.id
            AND pw2.projectId = p.id
            AND pw2.status = 'terminated'
        ) as projectTerminations,
        -- Calculate total penalty
        (
            (/* task termination count */) * 15.0
        ) + (
            (/* project termination count */) * 25.0
        ) as totalPenalty
    FROM user AS u
    INNER JOIN projectWorker AS pw ON u.id = pw.workerId
    INNER JOIN project AS p ON pw.projectId = p.id
    INNER JOIN projectPhase AS pp ON p.id = pp.projectId
    INNER JOIN phaseTask AS pt ON pp.id = pt.phaseId
    INNER JOIN phaseTaskWorker AS ptw ON pt.id = ptw.taskId AND u.id = ptw.workerId
    WHERE u.deletedAt IS NULL
      AND p.id = :projectId
    GROUP BY u.id, u.firstName, u.lastName, u.email, p.id
    HAVING totalTasks > 0
) AS ws
ORDER BY overallScore DESC
LIMIT 10
```

**Key Implementation Details:**

1. **Two-Tier Query Structure**: 
   - Inner query calculates `baseScore` once and counts terminations
   - Outer query applies penalty: `GREATEST(0, baseScore - totalPenalty)`
   - This prevents rounding discrepancies from recalculating baseScore

2. **Hierarchical Joins**: 
   - The structure requires joining through `projectPhase` before accessing `phaseTask`
   - Ensures all tasks within project phases are included

3. **Termination Tracking**:
   - Separate subqueries count task and project terminations
   - Penalties calculated as: `(taskTerminations × 15.0) + (projectTerminations × 25.0)`
   - Worker status checked via `phaseTaskWorker.status` and `projectWorker.status`

4. **GROUP BY Clause**:
   - Must include `p.id` to prevent aggregation conflicts across multiple projects
   - Groups by: `u.id, u.firstName, u.lastName, u.email, p.id`

---

## PHP Class Implementation

The `WorkerPerformanceCalculator` class in `source/backend/utility/worker-performance-calculator.php` provides an object-oriented approach.

### Class Constants

```php
// Priority weights
private const PRIORITY_WEIGHTS = [
    TaskPriority::HIGH->value => 5.0,
    TaskPriority::MEDIUM->value => 3.0,
    TaskPriority::LOW->value => 1.0
];

// Status multipliers
private const STATUS_MULTIPLIERS = [
    WorkStatus::COMPLETED->value => 1.0,
    WorkStatus::ON_GOING->value => 0.5,
    WorkStatus::DELAYED->value => 0.3,
    WorkStatus::PENDING->value => 0.0,
    WorkStatus::CANCELLED->value => 0.0
];

// Time-based multipliers
private const EARLY_COMPLETION_BONUS = 1.2;
private const ON_TIME_MULTIPLIER = 1.0;
private const LATE_PENALTY = 0.8;

// Worker status penalties (deducted from overall score)
private const WORKER_STATUS_PENALTIES = [
    WorkerStatus::ASSIGNED->value => 0.0,      // No penalty
    WorkerStatus::TERMINATED->value => 15.0    // -15% penalty for task termination
];

// Project-level termination penalty (greater than task termination)
private const PROJECT_TERMINATION_PENALTY = 25.0;  // -25% penalty for project termination
```

### Core Calculation Method

```php
private static function calculateTaskScore(Task $task): array
{
    $priority = $task->getPriority()->value;
    $status = $task->getStatus()->value;
    
    // Get base weights
    $priorityWeight = self::PRIORITY_WEIGHTS[$priority] ?? 1.0;
    $statusMultiplier = self::STATUS_MULTIPLIERS[$status] ?? 0.0;
    
    // Calculate time performance
    $timeMultiplier = 1.0;
    if ($status === WorkStatus::COMPLETED->value) {
        $actual = $task->getActualCompletionDateTime();
        $deadline = $task->getCompletionDateTime();
        
        if ($actual < $deadline) {
            $timeMultiplier = self::EARLY_COMPLETION_BONUS;  // 1.2
        } elseif ($actual <= $deadline->modify('+1 day')) {
            $timeMultiplier = self::ON_TIME_MULTIPLIER;      // 1.0
        } else {
            $timeMultiplier = self::LATE_PENALTY;            // 0.8
        }
    }
    
    // Calculate final scores
    $taskScore = $priorityWeight * $statusMultiplier * $timeMultiplier;
    $maxScore = $priorityWeight * 1.0 * self::EARLY_COMPLETION_BONUS;
    
    return [
        'weightedScore' => $taskScore,
        'maxPossibleScore' => $maxScore
    ];
}
```

### Hierarchical Task Aggregation

The calculator handles the **Project → Phase → Task** hierarchy by iterating through phases:

```php
foreach ($projects as $project) {
    // Get phases from project
    $phases = $project->getPhases();
    
    if ($phases && $phases->count() > 0) {
        // Iterate through phases to get tasks
        foreach ($phases as $phase) {
            $tasks = $phase->getTasks();
            
            if ($tasks && $tasks->count() > 0) {
                foreach ($tasks as $task) {
                    // Calculate performance for each task
                    $taskScore = self::calculateTaskScore($task);
                    $allTasks->add($task);
                }
            }
        }
    }
}
```

**Key Points:**
- Tasks are no longer accessed directly from projects
- The system must iterate through phases first: `$project->getPhases()` → `$phase->getTasks()`
- This ensures all tasks across all phases are included in performance calculations
- Phase names are tracked in penalty breakdowns for better context

### Termination Penalty Calculation

The calculator tracks terminations at both project and task levels:

```php
public static function calculate(ProjectContainer $projects): array
{
    $statusPenalties = [
        'taskTerminations' => 0,
        'projectTerminations' => 0,
        'totalPenalty' => 0.0,
        'penaltyBreakdown' => []
    ];
    
    foreach ($projects as $project) {
        $workerStatus = $project->getAdditionalInfo('workerStatus');
        
        // Track project-level terminations
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
        
        // Track task-level terminations
        foreach ($phases as $phase) {
            foreach ($tasks as $task) {
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
    
    // Apply penalties to base score
    $baseScore = $taskPerformance['overallScore'];
    $penalizedScore = max(0, $baseScore - $statusPenalties['totalPenalty']);
    
    return [
        'overallScore' => round($penalizedScore, 2),
        'baseScore' => round($baseScore, 2),
        'statusPenalties' => $statusPenalties,
        // ... other metrics
    ];
}
```

**Return Structure:**
```php
[
    'overallScore' => 68.5,        // Final score after penalties
    'baseScore' => 83.5,           // Score before penalties
    'totalTasks' => 12,
    'totalProjects' => 2,
    'statusPenalties' => [
        'taskTerminations' => 1,          // Number of task terminations
        'projectTerminations' => 0,        // Number of project terminations
        'totalPenalty' => 15.0,           // Total percentage deducted
        'penaltyBreakdown' => [           // Detailed breakdown
            [
                'taskName' => 'API Integration',
                'projectName' => 'Mobile App',
                'phaseName' => 'Development',
                'type' => 'task',
                'penalty' => 15.0,
                'reason' => 'Terminated from task'
            ]
        ]
    ]
]
```

---

## Scoring Examples

### Example 1: Perfect Performance

**Scenario:**
- Worker completes 3 high-priority tasks early
- All tasks delivered ahead of schedule

**Calculation:**

| Task | Priority | Status | Timing | W_p | M_s | M_t | Score | Max |
|------|----------|--------|--------|-----|-----|-----|-------|-----|
| A | High | Completed | Early | 5.0 | 1.0 | 1.2 | 6.0 | 6.0 |
| B | High | Completed | Early | 5.0 | 1.0 | 1.2 | 6.0 | 6.0 |
| C | High | Completed | Early | 5.0 | 1.0 | 1.2 | 6.0 | 6.0 |

```
Overall Score = (18.0 / 18.0) × 100 = 100.0%
Grade: A+ (Exceptional)
```

### Example 2: Mixed Performance

**Scenario:**
- 2 high-priority completed (1 early, 1 late)
- 1 medium-priority ongoing
- 1 low-priority delayed

**Calculation:**

| Task | Priority | Status | Timing | W_p | M_s | M_t | Score | Max |
|------|----------|--------|--------|-----|-----|-----|-------|-----|
| A | High | Completed | Early | 5.0 | 1.0 | 1.2 | 6.0 | 6.0 |
| B | High | Completed | Late | 5.0 | 1.0 | 0.8 | 4.0 | 6.0 |
| C | Medium | On Going | N/A | 3.0 | 0.5 | 1.0 | 1.5 | 3.6 |
| D | Low | Delayed | N/A | 1.0 | 0.3 | 1.0 | 0.3 | 1.2 |

```
Overall Score = (11.8 / 16.8) × 100 = 70.24%
Grade: C+ (Above Average)
```

### Example 3: Poor Performance

**Scenario:**
- 1 high-priority completed late
- 2 medium-priority pending
- 1 low-priority cancelled

**Calculation:**

| Task | Priority | Status | Timing | W_p | M_s | M_t | Score | Max |
|------|----------|--------|--------|-----|-----|-----|-------|-----|
| A | High | Completed | Late | 5.0 | 1.0 | 0.8 | 4.0 | 6.0 |
| B | Medium | Pending | N/A | 3.0 | 0.0 | 1.0 | 0.0 | 3.6 |
| C | Medium | Pending | N/A | 3.0 | 0.0 | 1.0 | 0.0 | 3.6 |
| D | Low | Cancelled | N/A | 1.0 | 0.0 | 1.0 | 0.0 | 1.2 |

```
Overall Score = (4.0 / 14.4) × 100 = 27.78%
Grade: F (Failing)
```

---

## Performance Grading System

The system assigns letter grades based on the calculated score:

| Score Range | Grade | Classification |
|-------------|-------|----------------|
| 90-100 | A+ | Exceptional |
| 85-89 | A | Excellent |
| 80-84 | B+ | Very Good |
| 75-79 | B | Good |
| 70-74 | C+ | Above Average |
| 65-69 | C | Average |
| 60-64 | D+ | Below Average |
| 50-59 | D | Poor |
| 0-49 | F | Failing |

### Interpretation Guidelines

- **90-100 (Exceptional)**: Consistently delivers high-quality work ahead of schedule
- **80-89 (Excellent/Very Good)**: Reliable performance with strong deadline adherence
- **70-79 (Good/Above Average)**: Solid contributor with room for improvement
- **60-69 (Average/Below Average)**: Meets minimum expectations but needs support
- **50-59 (Poor)**: Struggling to meet deadlines and quality standards
- **0-49 (Failing)**: Significant performance issues requiring intervention

---

## Best Practices

### For System Administrators

1. **Regular Calibration**: Review weight and multiplier constants quarterly
2. **Threshold Adjustment**: Adjust grace period (1 day) based on project types
3. **Priority Auditing**: Ensure managers assign priorities consistently
4. **Performance Reviews**: Use scores as one input, not the sole factor

### For Project Managers

1. **Fair Priority Assignment**: Don't over-assign high priorities
2. **Realistic Deadlines**: Set achievable completion dates
3. **Status Updates**: Ensure workers update task status promptly
4. **Balanced Workload**: Distribute priority mix fairly across workers
5. **Phase Organization**: Structure phases logically to track progress effectively
6. **Cross-Phase Visibility**: Monitor worker performance across all project phases

### For Workers

1. **Early Completion**: Aim to finish before deadlines for bonus points
2. **Status Accuracy**: Keep task status current (pending → ongoing → completed)
3. **Priority Focus**: Prioritize high-weight tasks for maximum impact
4. **Communication**: Report delays early to update deadlines

### For Data Analysts

1. **Trend Analysis**: Track scores over time, not just snapshots
2. **Outlier Investigation**: Investigate scores below 50 or above 95
3. **Context Matters**: Consider project difficulty and external factors
4. **Comparative Analysis**: Compare scores within similar project types

---

## Advanced Considerations

### 1. Handling Division by Zero

When a worker has no tasks or all max scores are zero:

```php
$performanceScore = $maxPossibleScore > 0 
    ? ($totalScore / $maxPossibleScore) * 100 
    : 0;
```

### 2. NULL Value Handling

Tasks missing priority or status default to lowest values:

```php
$priorityWeight = self::PRIORITY_WEIGHTS[$priority] ?? 1.0;
$statusMultiplier = self::STATUS_MULTIPLIERS[$status] ?? 0.0;
```

### 3. Grace Period Rationale

The 1-day grace period accommodates:
- Time zone differences
- End-of-day vs. exact timestamp comparison
- Minor delays due to external dependencies
- Human factors (illness, emergencies)

### 4. Future Enhancements

Potential additions to the formula:

- **Complexity Factor**: Adjust weights based on task complexity
- **Team Size**: Normalize scores for collaborative vs. individual tasks
- **Quality Metrics**: Incorporate defect rates or revision counts
- **Velocity Trending**: Weight recent performance more heavily
- **Skill Matching**: Bonus for tasks outside core competency

---

## Summary

The Worker Performance Calculation System provides a **fair, transparent, and mathematically rigorous** method for evaluating worker productivity. By combining priority, status, and timing dimensions with weighted aggregation, the system:

- ✅ Rewards quality and timeliness
- ✅ Accounts for task importance
- ✅ Provides partial credit for work-in-progress
- ✅ Normalizes scores across different workloads
- ✅ Generates actionable insights for improvement

The dual implementation (SQL for efficiency, PHP for flexibility) ensures the system can scale while maintaining accuracy and consistency across the application.

---

**Document Version:** 1.2  
**Last Updated:** November 24, 2025  
**Maintained By:** TaskFlow Development Team  
**Changelog:**
- v1.2 (Nov 24, 2025): Added termination penalty system (-15% task, -25% project); updated SQL implementation with two-tier query structure and penalty tracking
- v1.1 (Nov 23, 2025): Updated for hierarchical Project → Phase → Task structure
- v1.0 (Nov 15, 2025): Initial documentation

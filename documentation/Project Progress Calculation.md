# Project Progress Calculation System

## Overview

The TaskFlow Project Progress Calculation System is a sophisticated, hierarchical evaluation framework that measures project completion and health across a three-tier structure: **Project → Phases → Tasks**. The system employs a **weighted scoring algorithm** that considers **task status**, **task priority**, and **phase distribution** to generate comprehensive progress metrics ranging from 0% to 100%.

This multi-dimensional approach ensures accurate progress tracking by accounting for task importance, partial completion states, and phase-level granularity, providing project managers with actionable insights for decision-making.

---

## Table of Contents

1. [Core Concepts](#core-concepts)
2. [Mathematical Foundation](#mathematical-foundation)
3. [Implementation Details](#implementation-details)
4. [Formula Breakdown](#formula-breakdown)
5. [Progress Calculation Methods](#progress-calculation-methods)
6. [PHP Class Implementation](#php-class-implementation)
7. [Calculation Examples](#calculation-examples)
8. [Insights and Recommendations](#insights-and-recommendations)
9. [Best Practices](#best-practices)

---

## Core Concepts

### 1. Hierarchical Progress Model

The progress calculation respects the project hierarchy:

```
Project Progress = f(Phase₁, Phase₂, ..., Phaseₙ)
Phase Progress = f(Task₁, Task₂, ..., Taskₘ)
Task Contribution = f(Priority, Status)
```

- **Project Level**: Aggregate of all phase progress
- **Phase Level**: Weighted average of task completion within the phase
- **Task Level**: Individual task contribution based on priority and status

### 2. Dual Progress Metrics

The system calculates two complementary metrics:

1. **Weighted Progress**: Accounts for task priority and partial completion
   ```
   Weighted Progress = (Σ(Priority Weight × Status Completion) / Σ Priority Weights) × 100
   ```

2. **Simple Progress**: Straightforward completion ratio
   ```
   Simple Progress = (Completed Tasks / Total Non-Cancelled Tasks) × 100
   ```

### 3. Phase-Weighted Aggregation

Larger phases (with more tasks) have proportionally greater impact on overall project progress:

```
Project Progress = Σ(Phase Progress × Phase Task Count) / Total Tasks
```

---

## Mathematical Foundation

### Base Formula

The overall project progress is calculated using phase-weighted aggregation:

```
P_project = (Σⁿᵢ₌₁ (P_phaseᵢ × T_phaseᵢ)) / Σⁿᵢ₌₁ T_phaseᵢ
```

Where:
- `P_project` = Overall project progress (0-100)
- `P_phaseᵢ` = Progress of phase `i` (0-100)
- `T_phaseᵢ` = Number of tasks in phase `i`
- `n` = Total number of phases

### Task-Level Formula

Each task contributes to phase progress based on:

```
Task Contribution = W_priority × C_status
```

Where:
- `W_priority` = Priority weight multiplier
- `C_status` = Status completion percentage

### Phase Progress Formula

Progress for a single phase:

```
P_phase = (Σᵐⱼ₌₁ (W_priorityⱼ × C_statusⱼ)) / Σᵐⱼ₌₁ W_priorityⱼ
```

Where:
- `m` = Number of tasks in the phase
- Sum of weighted contributions divided by sum of weights

---

## Formula Breakdown

### Component 1: Priority Weights (W_priority)

Priority weights determine task importance in progress calculations:

| Priority | Weight (W_priority) | Impact | Rationale |
|----------|---------------------|--------|-----------|
| **High** | 3.0 | 3x impact | Critical tasks requiring immediate focus |
| **Medium** | 2.0 | 2x impact | Standard tasks with moderate importance |
| **Low** | 1.0 | 1x impact | Optional or less urgent tasks |

**Mathematical Representation:**

```
W_priority = {
    3.0,  if priority = 'high'
    2.0,  if priority = 'medium'
    1.0,  if priority = 'low'
}
```

**Rationale:**
- High-priority tasks have 3× the impact of low-priority tasks
- Ensures critical tasks drive progress more significantly
- Reflects real-world project management priorities
- Prevents "gaming" progress with many low-priority completions

### Component 2: Status Completion Percentages (C_status)

Status completion percentages reflect how much credit a task receives:

| Status | Completion % (C_status) | Credit | Rationale |
|--------|-------------------------|--------|-----------|
| **Completed** | 100.0 | Full credit | Task finished and delivered |
| **On Going** | 50.0 | Partial credit | Work in progress |
| **Delayed** | 25.0 | Reduced credit | Behind schedule, minimal credit |
| **Pending** | 0.0 | No credit | Not yet started |
| **Cancelled** | 0.0 | No credit | Task abandoned |

**Mathematical Representation:**

```
C_status = {
    100.0,  if status = 'completed'
    50.0,   if status = 'onGoing'
    25.0,   if status = 'delayed'
    0.0,    if status ∈ {'pending', 'cancelled'}
}
```

**Rationale:**
- Only completed tasks receive full credit toward progress
- Ongoing tasks get 50% to acknowledge active work
- Delayed tasks get 25% to reflect reduced efficiency
- Pending and cancelled tasks contribute nothing to progress
- **Cancelled tasks are excluded from simple progress denominator** to avoid artificially deflating progress

---

## Progress Calculation Methods

### Method 1: Weighted Progress (Primary Metric)

The **weighted progress** accounts for both priority and status:

#### Formula

```
Weighted Progress = (Σ(W_priority × C_status) / Σ W_priority) × 100
```

#### Step-by-Step Calculation

1. **For each task**, calculate contribution:
   ```
   Task Contribution = Priority Weight × Status Completion %
   ```

2. **Sum all contributions**:
   ```
   Total Weighted Progress = Σ Task Contributions
   ```

3. **Sum all priority weights** (theoretical maximum):
   ```
   Total Weight = Σ Priority Weights
   ```

4. **Calculate percentage**:
   ```
   Progress % = (Total Weighted Progress / Total Weight) × 100
   ```

#### Example

**Tasks:**
- Task A: High priority (3.0), Completed (100%) → Contribution = 3.0 × 100 = 300
- Task B: Medium priority (2.0), On Going (50%) → Contribution = 2.0 × 50 = 100
- Task C: Low priority (1.0), Pending (0%) → Contribution = 1.0 × 0 = 0

**Calculation:**
```
Total Weighted Progress = 300 + 100 + 0 = 400
Total Weight = 3.0 + 2.0 + 1.0 = 6.0

Weighted Progress = (400 / 6.0) × 100 = 66.67%
```

### Method 2: Simple Progress (Secondary Metric)

The **simple progress** is a straightforward completion ratio:

#### Formula

```
Simple Progress = (Completed Tasks / (Total Tasks - Cancelled Tasks)) × 100
```

**Note:** Cancelled tasks are excluded from the denominator to prevent artificially low progress percentages.

#### Example

**Tasks:**
- 3 Completed
- 2 On Going
- 1 Pending
- 1 Cancelled

**Calculation:**
```
Completed = 3
Total Non-Cancelled = 7 - 1 = 6

Simple Progress = (3 / 6) × 100 = 50.0%
```

### Method 3: Phase-Weighted Project Progress

The **overall project progress** weights each phase by its task count:

#### Formula

```
Project Progress = Σ(Phase Progress × Phase Task Count) / Total Tasks
```

#### Step-by-Step Calculation

1. **Calculate progress for each phase** using weighted formula
2. **Multiply phase progress by its task count**:
   ```
   Phase Contribution = Phase Progress × Task Count
   ```
3. **Sum all phase contributions**
4. **Divide by total tasks across all phases**:
   ```
   Project Progress = Σ Phase Contributions / Total Tasks
   ```

#### Example

**Phases:**
- Phase 1: 70% progress, 10 tasks → Contribution = 70 × 10 = 700
- Phase 2: 50% progress, 5 tasks → Contribution = 50 × 5 = 250
- Phase 3: 90% progress, 15 tasks → Contribution = 90 × 15 = 1350

**Calculation:**
```
Total Contribution = 700 + 250 + 1350 = 2300
Total Tasks = 10 + 5 + 15 = 30

Project Progress = 2300 / 30 = 76.67%
```

**Impact:** Phase 3 has the most tasks (15) and high progress (90%), so it dominates the overall progress calculation. This reflects reality: larger phases should have more influence.

---

## Implementation Details

### Task Contribution Calculation

For each task in the system:

```
1. Get task priority → W_priority
2. Get task status → C_status
3. Calculate: Contribution = W_priority × C_status
4. Add to phase totals
```

### Phase Progress Calculation

For each phase:

```
1. Sum all task contributions in phase → Total Weighted Progress
2. Sum all task priority weights in phase → Total Weight
3. Calculate: Phase Progress = (Total Weighted Progress / Total Weight) × 100
4. Track task count for phase weighting
```

### Project Progress Calculation

Across all phases:

```
1. For each phase: Calculate Phase Progress × Task Count
2. Sum all phase contributions
3. Divide by total tasks across all phases
4. Result: Overall Project Progress %
```

### Flow Diagram

```
┌─────────────────────────────────────────────────┐
│ Step 1: Process All Tasks                      │
│  - Extract from all phases                      │
│  - Calculate individual contributions           │
│  - Track status and priority counts             │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│ Step 2: Calculate Phase-Level Progress         │
│  - For each phase: weighted average             │
│  - Store phase progress and task count          │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│ Step 3: Calculate Project-Level Progress       │
│  - Weight phases by task count                  │
│  - Aggregate to overall project progress        │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│ Step 4: Generate Insights & Recommendations    │
│  - Analyze status distribution                  │
│  - Identify risks (delays, cancellations)       │
│  - Provide actionable recommendations           │
└─────────────────────────────────────────────────┘
```

---

## PHP Class Implementation

The `ProjectProgressCalculator` class in `source/backend/utility/project-progress-calculator.php` provides the implementation.

### Class Constants

```php
// Priority weights
private const PRIORITY_WEIGHTS = [
    TaskPriority::HIGH->value => 3.0,
    TaskPriority::MEDIUM->value => 2.0,
    TaskPriority::LOW->value => 1.0
];

// Status completion percentages
private const STATUS_COMPLETION = [
    WorkStatus::PENDING->value => 0.0,
    WorkStatus::ON_GOING->value => 50.0,
    WorkStatus::COMPLETED->value => 100.0,
    WorkStatus::DELAYED->value => 25.0,
    WorkStatus::CANCELLED->value => 0.0
];
```

### Main Calculation Method

```php
public static function calculate(PhaseContainer $phaseContainer): array
{
    $phases = $phaseContainer->getItems();
    
    if (empty($phases)) {
        return [
            'progressPercentage' => 0.0,
            'totalTasks' => 0,
            'statusBreakdown' => [],
            'priorityBreakdown' => [],
            'phaseBreakdown' => [],
            'insights' => ['message' => 'No phases found in project']
        ];
    }

    // Initialize counters
    $statusCounts = [];
    $priorityCounts = [];
    $phaseData = [];
    $totalTasks = 0;

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

                // Count by status and priority
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                $priorityCounts[$priority] = ($priorityCounts[$priority] ?? 0) + 1;

                // Track phase-specific data
                $phaseData[$phaseId]['totalTasks']++;
                $phaseData[$phaseId]['statusCounts'][$status] = 
                    ($phaseData[$phaseId]['statusCounts'][$status] ?? 0) + 1;
                $phaseData[$phaseId]['priorityCounts'][$priority] = 
                    ($phaseData[$phaseId]['priorityCounts'][$priority] ?? 0) + 1;

                // Calculate weighted progress
                $weight = self::PRIORITY_WEIGHTS[$priority] ?? 1.0;
                $completion = self::STATUS_COMPLETION[$status] ?? 0.0;
                
                $phaseData[$phaseId]['totalWeightedProgress'] += ($completion * $weight);
                $phaseData[$phaseId]['totalWeight'] += $weight;
                
                $totalTasks++;
            }
        }
    }

    // Calculate phase-level progress
    $phaseBreakdown = self::calculatePhaseBreakdown($phaseData);
    
    // Calculate final progress percentage
    $progressPercentage = self::calculatePhaseWeightedProgress($phaseBreakdown);
    $simpleProgress = self::calculateSimpleProgress($statusCounts, $totalTasks);

    return [
        'progressPercentage' => round($progressPercentage, 2),
        'simpleProgressPercentage' => round($simpleProgress, 2),
        'totalTasks' => $totalTasks,
        'statusBreakdown' => self::formatStatusBreakdown($statusCounts, $totalTasks),
        'priorityBreakdown' => self::formatPriorityBreakdown($priorityCounts, $totalTasks),
        'phaseBreakdown' => $phaseBreakdown,
        'insights' => self::generateInsights($statusCounts, $priorityCounts, $totalTasks, $progressPercentage)
    ];
}
```

### Phase Breakdown Method

```php
private static function calculatePhaseBreakdown(array $phaseData): array
{
    $phaseBreakdown = [];
    
    foreach ($phaseData as $phaseId => $data) {
        // Weighted progress
        $phaseProgress = $data['totalWeight'] > 0 
            ? ($data['totalWeightedProgress'] / $data['totalWeight']) 
            : 0.0;
        
        // Simple progress (excludes cancelled tasks)
        $completedTasks = $data['statusCounts'][WorkStatus::COMPLETED->value] ?? 0;
        $cancelledTasks = $data['statusCounts'][WorkStatus::CANCELLED->value] ?? 0;
        $denominator = $data['totalTasks'] - $cancelledTasks;
        
        $simpleProgress = $denominator > 0 
            ? ($completedTasks / $denominator) * 100 
            : 0.0;
        
        $phaseBreakdown[$phaseId] = [
            'phaseName' => $data['phaseName'],
            'totalTasks' => $data['totalTasks'],
            'completedTasks' => $completedTasks,
            'weightedProgress' => round($phaseProgress, 2),
            'simpleProgress' => round($simpleProgress, 2),
            'statusBreakdown' => self::formatPhaseStatusBreakdown(
                $data['statusCounts'], 
                $data['totalTasks']
            ),
            'priorityBreakdown' => self::formatPriorityBreakdown(
                $data['priorityCounts'], 
                $data['totalTasks']
            )
        ];
    }
    
    return $phaseBreakdown;
}
```

### Phase-Weighted Progress Method

```php
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
    
    return $totalTaskCount > 0 
        ? ($totalWeightedProgress / $totalTaskCount) 
        : 0.0;
}
```

### Simple Progress Method

```php
private static function calculateSimpleProgress(
    array $statusCounts, 
    int $totalTasks
): float {
    $completedTasks = $statusCounts[WorkStatus::COMPLETED->value] ?? 0;
    $cancelledTasks = $statusCounts[WorkStatus::CANCELLED->value] ?? 0;
    
    // Exclude cancelled tasks from denominator
    $denominator = $totalTasks - $cancelledTasks;
    
    return $denominator > 0 
        ? ($completedTasks / $denominator) * 100 
        : 0.0;
}
```

---

## Calculation Examples

### Example 1: Single-Phase Project

**Scenario:**
- 1 phase with 4 tasks
- Task distribution:
  - Task A: High priority, Completed
  - Task B: Medium priority, On Going
  - Task C: Low priority, Completed
  - Task D: Medium priority, Pending

**Task Contributions:**

| Task | Priority | Weight | Status | Completion % | Contribution |
|------|----------|--------|--------|--------------|--------------|
| A | High | 3.0 | Completed | 100 | 3.0 × 100 = 300 |
| B | Medium | 2.0 | On Going | 50 | 2.0 × 50 = 100 |
| C | Low | 1.0 | Completed | 100 | 1.0 × 100 = 100 |
| D | Medium | 2.0 | Pending | 0 | 2.0 × 0 = 0 |

**Weighted Progress:**
```
Total Weighted Progress = 300 + 100 + 100 + 0 = 500
Total Weight = 3.0 + 2.0 + 1.0 + 2.0 = 8.0

Weighted Progress = (500 / 8.0) × 100 = 62.5%
```

**Simple Progress:**
```
Completed = 2 (Tasks A, C)
Total = 4
Cancelled = 0

Simple Progress = (2 / 4) × 100 = 50.0%
```

**Result:** Weighted progress (62.5%) is higher than simple progress (50%) because high-priority tasks are weighted more heavily.

### Example 2: Multi-Phase Project

**Scenario:**
- 3 phases with varying task counts

**Phase 1 (Design):** 5 tasks, 80% weighted progress
**Phase 2 (Development):** 15 tasks, 60% weighted progress
**Phase 3 (Testing):** 5 tasks, 40% weighted progress

**Phase-Weighted Progress:**
```
Phase 1 Contribution = 80 × 5 = 400
Phase 2 Contribution = 60 × 15 = 900
Phase 3 Contribution = 40 × 5 = 200

Total Contribution = 400 + 900 + 200 = 1500
Total Tasks = 5 + 15 + 5 = 25

Project Progress = 1500 / 25 = 60.0%
```

**Analysis:** Development (Phase 2) has the most tasks (15) and drives the overall progress. Even though Design has higher progress (80%), it has fewer tasks (5), so its impact is proportionally smaller.

### Example 3: Project with Cancelled Tasks

**Scenario:**
- 1 phase with 5 tasks
- Task distribution:
  - 2 Completed
  - 1 On Going
  - 1 Pending
  - 1 Cancelled

**Simple Progress Calculation:**

**Without Cancelled Exclusion (Incorrect):**
```
Simple Progress = (2 / 5) × 100 = 40.0%
```

**With Cancelled Exclusion (Correct):**
```
Completed = 2
Total Non-Cancelled = 5 - 1 = 4

Simple Progress = (2 / 4) × 100 = 50.0%
```

**Rationale:** Excluding cancelled tasks prevents artificially deflated progress. If a task is cancelled, it shouldn't count against the project's completion percentage.

---

## Insights and Recommendations

### Automated Insight Generation

The system generates context-aware insights based on progress metrics:

```php
private static function generateInsights(
    array $statusCounts, 
    array $priorityCounts, 
    int $totalTasks, 
    float $progress
): array {
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
        $insights[] = "Warning: {$delayedTasks} tasks (" . 
            round($delayedPercentage, 1) . "%) are delayed.";
    }

    // Cancelled tasks notice
    $cancelledTasks = $statusCounts[WorkStatus::CANCELLED->value] ?? 0;
    if ($cancelledTasks > 0) {
        $insights[] = "Note: {$cancelledTasks} tasks have been cancelled.";
    }

    // High priority focus
    $highPriorityTasks = $priorityCounts[TaskPriority::HIGH->value] ?? 0;
    $completedTasks = $statusCounts[WorkStatus::COMPLETED->value] ?? 0;
    
    if ($highPriorityTasks > $completedTasks) {
        $insights[] = "Focus needed: {$highPriorityTasks} " . 
            "high-priority tasks require attention.";
    }

    // Pending tasks alert
    $pendingTasks = $statusCounts[WorkStatus::PENDING->value] ?? 0;
    if ($pendingTasks > ($totalTasks * 0.3)) {
        $insights[] = "Many tasks are still pending - " . 
            "consider resource allocation.";
    }

    return [
        'messages' => $insights,
        'recommendations' => self::generateRecommendations(
            $statusCounts, 
            $priorityCounts, 
            $totalTasks
        )
    ];
}
```

### Actionable Recommendations

The system provides targeted recommendations:

```php
private static function generateRecommendations(
    array $statusCounts, 
    array $priorityCounts, 
    int $totalTasks
): array {
    $recommendations = [];
    
    $ongoingTasks = $statusCounts[WorkStatus::ON_GOING->value] ?? 0;
    $pendingTasks = $statusCounts[WorkStatus::PENDING->value] ?? 0;
    $delayedTasks = $statusCounts[WorkStatus::DELAYED->value] ?? 0;
    $highPriorityTasks = $priorityCounts[TaskPriority::HIGH->value] ?? 0;

    // Resource allocation
    if ($ongoingTasks > ($totalTasks * 0.6)) {
        $recommendations[] = "Consider if team capacity is " . 
            "sufficient for current workload.";
    }

    // Priority focus
    if ($highPriorityTasks > 0) {
        $recommendations[] = "Prioritize high-priority tasks " . 
            "for maximum impact.";
    }

    // Delayed task management
    if ($delayedTasks > 0) {
        $recommendations[] = "Review delayed tasks and " . 
            "reassign resources if necessary.";
    }

    // Pending task activation
    if ($pendingTasks > ($totalTasks * 0.4)) {
        $recommendations[] = "Activate pending tasks to " . 
            "maintain project momentum.";
    }

    return $recommendations;
}
```

---

## Best Practices

### For System Administrators

1. **Weight Calibration**: Review priority weights quarterly based on organizational priorities
2. **Status Percentage Tuning**: Adjust completion percentages (e.g., change "On Going" from 50% to 60%) based on project types
3. **Cancelled Task Handling**: Ensure cancelled tasks are properly excluded from denominator calculations
4. **Performance Monitoring**: Track calculation performance for large projects (1000+ tasks)

### For Project Managers

1. **Realistic Prioritization**: Don't over-assign high priorities; maintain a balanced distribution
2. **Status Updates**: Ensure team members update task statuses regularly for accurate progress
3. **Phase Planning**: Distribute tasks evenly across phases to avoid skewed progress
4. **Cancelled Task Management**: Only cancel tasks when truly necessary; consider "delayed" status first
5. **Progress Interpretation**: Use weighted progress as the primary metric; simple progress as a sanity check

### For Development Teams

1. **Status Accuracy**: Update task status promptly (pending → on going → completed)
2. **Priority Understanding**: Understand how priority affects progress calculations
3. **Communication**: Report delays early to allow for status updates
4. **Task Granularity**: Break large tasks into smaller units for more granular progress tracking

### For Stakeholders

1. **Trend Analysis**: Track progress over time rather than relying on single snapshots
2. **Phase Context**: Consider phase-level progress when evaluating overall project health
3. **Risk Identification**: Pay attention to delayed task warnings and recommendations
4. **Resource Allocation**: Use insights to make informed decisions about resource distribution

---

## Advanced Considerations

### 1. Handling Edge Cases

**No Phases:**
```php
if (empty($phases)) {
    return [
        'progressPercentage' => 0.0,
        'totalTasks' => 0,
        'insights' => ['message' => 'No phases found in project']
    ];
}
```

**No Tasks in Phases:**
```php
if ($totalTasks === 0) {
    return [
        'progressPercentage' => 0.0,
        'totalTasks' => 0,
        'insights' => ['message' => 'No tasks found in any phase']
    ];
}
```

**Division by Zero Protection:**
```php
$progress = $totalWeight > 0 
    ? ($totalWeightedProgress / $totalWeight) 
    : 0.0;
```

### 2. Cancelled Task Rationale

**Why Exclude from Denominator?**

Consider a project with 10 tasks:
- 5 completed
- 3 on going
- 2 cancelled

**Without Exclusion:**
```
Simple Progress = (5 / 10) × 100 = 50%
```

**With Exclusion:**
```
Simple Progress = (5 / 8) × 100 = 62.5%
```

The second calculation is more accurate because cancelled tasks are no longer part of the project scope and shouldn't count against progress.

### 3. Priority Weight Impact

**High-Priority Dominance:**

A project with:
- 1 high-priority task (weight 3.0), completed → contribution = 300
- 3 low-priority tasks (weight 1.0 each), pending → contribution = 0

```
Weighted Progress = 300 / 6.0 = 50%
Simple Progress = 1 / 4 = 25%
```

The weighted progress (50%) reflects the real impact: the critical task is done, even though only 25% of tasks by count are completed.

### 4. Phase Weighting Importance

**Scenario:** 
- Phase A: 90% progress, 2 tasks
- Phase B: 30% progress, 18 tasks

**Without Phase Weighting:**
```
Average Progress = (90 + 30) / 2 = 60%
```

**With Phase Weighting:**
```
Weighted Progress = (90 × 2 + 30 × 18) / 20 = (180 + 540) / 20 = 36%
```

Phase weighting (36%) is more accurate because Phase B has 90% of the tasks and drives the overall project progress.

### 5. Future Enhancements

Potential additions to the formula:

- **Time-Based Weighting**: Apply decay factor for overdue tasks
  ```
  Time Factor = max(0.5, 1.0 - (Days Overdue / 30))
  Adjusted Contribution = W_priority × C_status × Time Factor
  ```

- **Dependency Chains**: Weight tasks based on critical path
  ```
  Critical Path Multiplier = 1.5 for critical path tasks
  ```

- **Quality Metrics**: Incorporate defect rates or review status
  ```
  Quality Factor = (Approved / Total) for completed tasks
  ```

- **Team Velocity**: Adjust progress based on historical velocity
  ```
  Projected Progress = Current Progress + (Velocity × Days Remaining)
  ```

- **Risk-Adjusted Progress**: Apply risk factors to uncertain tasks
  ```
  Risk Factor = 0.8 for high-risk tasks
  ```

---

## Status × Priority Combination Matrix

The system also tracks the distribution of tasks across **status-priority combinations**:

### Matrix Structure

|  | High | Medium | Low |
|---|------|--------|-----|
| **Completed** | Count, % | Count, % | Count, % |
| **On Going** | Count, % | Count, % | Count, % |
| **Delayed** | Count, % | Count, % | Count, % |
| **Pending** | Count, % | Count, % | Count, % |
| **Cancelled** | Count, % | Count, % | Count, % |

### Use Cases

1. **Risk Identification**: High priority + Delayed = Critical risk
2. **Resource Allocation**: High priority + Pending = Needs immediate attention
3. **Performance Analysis**: Low priority + Completed = Potential priority mis-assignment
4. **Planning Insights**: Distribution patterns inform future sprint planning

### Implementation

```php
private static function calculateStatusPriorityCombinations(array $phases): array
{
    $combinations = [];
    $totalTasks = 0;
    
    // Initialize matrix
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
            foreach ($taskContainer->getItems() as $task) {
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
                $combinations[$status->value][$priority->value]['percentage'] = 
                    round($percentage, 2);
            }
        }
    }
    
    return $combinations;
}
```

---

## Summary

The Project Progress Calculation System provides a **comprehensive, hierarchical, and mathematically rigorous** method for tracking project health across multiple dimensions. By combining priority-weighted task contributions with phase-level aggregation, the system:

- ✅ Respects project hierarchy (Project → Phases → Tasks)
- ✅ Accounts for task importance through priority weighting
- ✅ Provides partial credit for work-in-progress
- ✅ Weights phases proportionally by task count
- ✅ Excludes cancelled tasks from simple progress calculations
- ✅ Generates actionable insights and recommendations
- ✅ Tracks status×priority combinations for detailed analysis
- ✅ Supports data-driven project management decisions

The dual-metric approach (weighted + simple progress) provides both sophisticated priority-aware tracking and straightforward completion ratios, ensuring project managers have complete visibility into project health.

---

**Document Version:** 1.0  
**Last Updated:** November 16, 2025  
**Maintained By:** TaskFlow Development Team

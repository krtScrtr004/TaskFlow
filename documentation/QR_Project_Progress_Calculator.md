# Quick Reference: Project Progress Calculator

## Method Signature

```php
public static function calculate(PhaseContainer $phaseContainer): array
```

## Input Structure

```
PhaseContainer
├── Phase 1
│   └── TaskContainer (tasks for Phase 1)
├── Phase 2
│   └── TaskContainer (tasks for Phase 2)
└── Phase 3
    └── TaskContainer (tasks for Phase 3)
```

## How to Get It

```php
// From a Project entity
$project = ProjectModel::findById($projectId);
$phaseContainer = $project->getPhases();

// Or directly query phases
$phases = PhaseModel::findByProject($projectId);
$phaseContainer = new PhaseContainer($phases);

// Calculate progress
$progress = ProjectProgressCalculator::calculate($phaseContainer);
```

## What You Get Back

| Key | Type | Description |
|-----|------|-------------|
| `progressPercentage` | float | Phase-weighted progress (0-100) |
| `simpleProgressPercentage` | float | Simple progress: completed/total |
| `totalTasks` | int | Total tasks across all phases |
| `statusBreakdown` | array | Overall task status distribution |
| `priorityBreakdown` | array | Overall task priority distribution |
| `phaseBreakdown` | array | Per-phase metrics (see below) |
| `insights` | array | Messages and recommendations |

## Phase Breakdown Format

For each phase in `phaseBreakdown[phaseId]`:

```php
[
    'phaseName' => 'Phase 1',
    'totalTasks' => 10,
    'completedTasks' => 5,
    'weightedProgress' => 65.5,      // Weighted by priority
    'simpleProgress' => 50.0,         // Completed/total
    'statusBreakdown' => [
        'completed' => ['count' => 5, 'percentage' => 50.0],
        'onGoing' => ['count' => 3, 'percentage' => 30.0],
        'pending' => ['count' => 2, 'percentage' => 20.0],
        'delayed' => ['count' => 0, 'percentage' => 0.0],
        'cancelled' => ['count' => 0, 'percentage' => 0.0]
    ]
]
```

## Common Use Cases

### 1. Get Overall Project Progress
```php
$progress = ProjectProgressCalculator::calculate($phaseContainer);
echo $progress['progressPercentage'] . '%';  // 65.5%
```

### 2. Find Bottleneck Phase
```php
$minProgress = PHP_FLOAT_MAX;
$bottleneckPhase = null;

foreach ($progress['phaseBreakdown'] as $phaseId => $phase) {
    if ($phase['weightedProgress'] < $minProgress) {
        $minProgress = $phase['weightedProgress'];
        $bottleneckPhase = $phase['phaseName'];
    }
}

echo "Slowest phase: $bottleneckPhase ($minProgress%)";
```

### 3. Compare Simple vs Weighted Progress
```php
$simple = $progress['simpleProgressPercentage'];
$weighted = $progress['progressPercentage'];

if ($weighted > $simple) {
    echo "High-priority tasks are ahead!";
} else {
    echo "Low-priority tasks are consuming time.";
}
```

### 4. List All Task Statuses by Phase
```php
foreach ($progress['phaseBreakdown'] as $phase) {
    echo "Phase: {$phase['phaseName']}\n";
    foreach ($phase['statusBreakdown'] as $status => $breakdown) {
        echo "  $status: {$breakdown['count']}\n";
    }
}
```

### 5. Check if Any Phase is Empty
```php
foreach ($progress['phaseBreakdown'] as $phaseId => $phase) {
    if ($phase['totalTasks'] === 0) {
        echo "Phase {$phaseId} has no tasks";
    }
}
```

## Key Differences from Old Implementation

| Aspect | Before | After |
|--------|--------|-------|
| **Input** | `TaskContainer` (flat list) | `PhaseContainer` (hierarchical) |
| **Organization** | Tasks mixed together | Tasks organized by phase |
| **Phase Info** | Extracted from task property | Direct from Phase entity |
| **Phase Data** | Inferred per-phase | Explicit in response |
| **Use Case** | Single phase progress | Multi-phase project progress |

## Error Handling

```php
// No phases
if (!$phaseContainer || $phaseContainer->count() === 0) {
    $progress = ProjectProgressCalculator::calculate($phaseContainer);
    // Result: progressPercentage = 0, totalTasks = 0
}

// Phase with no tasks (automatically handled)
// Empty phases still contribute to breakdown

// All phases empty
// Result: progressPercentage = 0, totalTasks = 0
```

## Integration Checklist

- [ ] Update method calls to pass `PhaseContainer` instead of `TaskContainer`
- [ ] Update controller/endpoint to use `getPhases()` instead of `getTasks()`
- [ ] Handle the new `phaseBreakdown` in response templates
- [ ] Update UI/API clients to display phase-level metrics
- [ ] Test with projects having multiple phases
- [ ] Test with projects having phases with no tasks

## Example Response

```json
{
  "progressPercentage": 65.50,
  "simpleProgressPercentage": 62.50,
  "totalTasks": 20,
  "statusBreakdown": {
    "completed": {"count": 12, "percentage": 60.0},
    "onGoing": {"count": 5, "percentage": 25.0},
    "pending": {"count": 3, "percentage": 15.0}
  },
  "phaseBreakdown": {
    "1": {
      "phaseName": "Phase 1: Design",
      "totalTasks": 8,
      "completedTasks": 8,
      "weightedProgress": 100.0,
      "simpleProgress": 100.0
    },
    "2": {
      "phaseName": "Phase 2: Development",
      "totalTasks": 10,
      "completedTasks": 4,
      "weightedProgress": 45.0,
      "simpleProgress": 40.0
    },
    "3": {
      "phaseName": "Phase 3: Testing",
      "totalTasks": 2,
      "completedTasks": 0,
      "weightedProgress": 0.0,
      "simpleProgress": 0.0
    }
  }
}
```

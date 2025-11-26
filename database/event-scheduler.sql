-- ============================================================================
-- TaskFlow Automated Status Update Event Scheduler
-- ============================================================================
-- This script creates MySQL event schedulers to automatically update the 
-- status of projects, phases, and tasks based on their date fields.
-- 
-- Status Transition Rules:
-- 1. pending → onGoing: When current date/time >= startDateTime
-- 2. onGoing → delayed: When current date/time > completionDateTime 
--                       AND actualCompletionDateTime IS NULL
-- 3. delayed → completed: When actualCompletionDateTime IS NOT NULL
--                         (handled by existing triggers)
--
-- Prerequisites:
-- - Event scheduler must be enabled: SET GLOBAL event_scheduler = ON;
-- - Run this script after creating the database tables
-- ============================================================================

-- Enable event scheduler (requires SUPER privilege)
SET GLOBAL event_scheduler = ON;

-- ============================================================================
-- EVENT 1: Update Task Status Daily
-- ============================================================================
-- Updates phaseTask status based on date conditions
-- Runs daily at 00:05 AM (after midnight to catch all date changes)
-- ============================================================================

DROP EVENT IF EXISTS `updateTaskStatusDaily`;

DELIMITER $$
CREATE EVENT `updateTaskStatusDaily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 5 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates task status based on startDateTime and completionDateTime'
DO
BEGIN
    -- Step 1: Update pending tasks to onGoing when start date is reached
    UPDATE `phaseTask`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`startDateTime`) <= CURRENT_DATE
      AND DATE(`completionDateTime`) > CURRENT_DATE;
    
    -- Step 2: Update onGoing tasks to delayed when completion date has passed
    UPDATE `phaseTask`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completionDateTime`) < CURRENT_DATE
      AND (DATE(`actualCompletionDateTime`)) IS NULL;
    
    -- Step 3: Update pending tasks directly to delayed if both dates have passed
    -- (for cases where tasks were never started and are already late)
    UPDATE `phaseTask`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`startDateTime`) <= CURRENT_DATE
      AND DATE(`completionDateTime`) < CURRENT_DATE
      AND (DATE(`actualCompletionDateTime`)) IS NULL;
      
    -- Log the number of updated records (optional, for debugging)
    -- SELECT CONCAT('Task status updated at ', CURRENT_DATE) AS log_message;
END$$
DELIMITER ;

-- ============================================================================
-- EVENT 2: Update Phase Status Daily
-- ============================================================================
-- Updates projectPhase status based on date conditions
-- Runs daily at 00:10 AM (5 minutes after task updates)
-- ============================================================================

DROP EVENT IF EXISTS `updatePhaseStatusDaily`;

DELIMITER $$
CREATE EVENT `updatePhaseStatusDaily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 10 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates phase status based on startDateTime and completionDateTime'
DO
BEGIN
    -- Step 1: Update pending phases to onGoing when start date is reached
    UPDATE `projectPhase`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`startDateTime`) <= CURRENT_DATE
      AND DATE(`completionDateTime`) > CURRENT_DATE;
    
    -- Step 2: Update onGoing phases to delayed when completion date has passed
    UPDATE `projectPhase`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completionDateTime`) < CURRENT_DATE
      AND (DATE(`actualCompletionDateTime`)) IS NULL;
    
    -- Step 3: Update pending phases directly to delayed if both dates have passed
    UPDATE `projectPhase`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`startDateTime`) <= CURRENT_DATE
      AND DATE(`completionDateTime`) < CURRENT_DATE
      AND (DATE(`actualCompletionDateTime`)) IS NULL;
    
    -- Step 4: Auto-complete phases when all tasks are completed
    UPDATE `projectPhase` AS pp
    SET `status` = 'completed',
        `actualCompletionDateTime` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actualCompletionDateTime`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `phaseTask` AS pt
          WHERE pt.phaseId = pp.id
            AND pt.status != 'completed'
            AND pt.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `phaseTask` AS pt2
          WHERE pt2.phaseId = pp.id
            AND pt2.status = 'completed'
      );
END$$
DELIMITER ;

-- ============================================================================
-- EVENT 3: Update Project Status Daily
-- ============================================================================
-- Updates project status based on date conditions
-- Runs daily at 00:15 AM (5 minutes after phase updates)
-- ============================================================================

DROP EVENT IF EXISTS `updateProjectStatusDaily`;

DELIMITER $$
CREATE EVENT `updateProjectStatusDaily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 15 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates project status based on startDateTime and completionDateTime'
DO
BEGIN
    -- Step 1: Update pending projects to onGoing when start date is reached
    UPDATE `project`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`startDateTime`) <= CURRENT_DATE
      AND DATE(`completionDateTime`) > CURRENT_DATE;
    
    -- Step 2: Update onGoing projects to delayed when completion date has passed
    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completionDateTime`) < CURRENT_DATE
      AND (DATE(`actualCompletionDateTime`)) IS NULL;
    
    -- Step 3: Update pending projects directly to delayed if both dates have passed
    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`startDateTime`) <= CURRENT_DATE
      AND DATE(`completionDateTime`) < CURRENT_DATE
      AND (DATE(`actualCompletionDateTime`)) IS NULL;
    
    -- Step 4: Auto-complete projects when all phases are completed
    UPDATE `project` AS p
    SET `status` = 'completed',
        `actualCompletionDateTime` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actualCompletionDateTime`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `projectPhase` AS pp
          WHERE pp.projectId = p.id
            AND pp.status != 'completed'
            AND pp.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `projectPhase` AS pp2
          WHERE pp2.projectId = p.id
            AND pp2.status = 'completed'
      );
END$$
DELIMITER ;

-- ============================================================================
-- EVENT 4: Update Task Status Hourly (Optional - More Responsive)
-- ============================================================================
-- For more immediate status updates during business hours
-- Runs every hour from 6 AM to 10 PM
-- Comment out if daily updates are sufficient
-- ============================================================================

DROP EVENT IF EXISTS `updateTaskStatusHourly`;

DELIMITER $$
CREATE EVENT `updateTaskStatusHourly`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_DATE + INTERVAL 6 HOUR
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Hourly task status updates during business hours for more immediate feedback'
DO
BEGIN
    -- Only run between 6 AM and 10 PM
    IF HOUR(CURRENT_DATE) BETWEEN 6 AND 22 THEN
        -- Update pending tasks to onGoing
        UPDATE `phaseTask`
        SET `status` = 'onGoing'
        WHERE `status` = 'pending'
          AND `startDateTime` <= CURRENT_DATE
          AND `completionDateTime` > CURRENT_DATE;
        
        -- Update onGoing tasks to delayed
        UPDATE `phaseTask`
        SET `status` = 'delayed'
        WHERE `status` = 'onGoing'
          AND `completionDateTime` < CURRENT_DATE
          AND `actualCompletionDateTime` IS NULL;
        
        -- Update pending tasks to delayed (overdue before start)
        UPDATE `phaseTask`
        SET `status` = 'delayed'
        WHERE `status` = 'pending'
          AND `startDateTime` <= CURRENT_DATE
          AND `completionDateTime` < CURRENT_DATE
          AND `actualCompletionDateTime` IS NULL;
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- Verification Queries
-- ============================================================================
-- Run these queries to verify that events were created successfully

-- Check if event scheduler is enabled
-- SHOW VARIABLES LIKE 'event_scheduler';

-- List all created events
-- SHOW EVENTS FROM taskflow;

-- View detailed event information
-- SELECT * FROM information_schema.EVENTS 
-- WHERE EVENT_SCHEMA = 'taskflow';

-- ============================================================================
-- Manual Testing
-- ============================================================================
-- To manually trigger an event for testing purposes, use:
-- CALL <event_logic>;
-- 
-- Or update dates on test records and wait for the event to run:
-- UPDATE phaseTask SET startDateTime = CURRENT_DATE - INTERVAL 1 DAY WHERE id = <test_id>;

-- ============================================================================
-- Disable/Enable Events
-- ============================================================================
-- To temporarily disable an event:
-- ALTER EVENT updateTaskStatusDaily DISABLE;
-- 
-- To re-enable:
-- ALTER EVENT updateTaskStatusDaily ENABLE;
--
-- To disable all events:
-- SET GLOBAL event_scheduler = OFF;

-- ============================================================================
-- Drop All Events (Cleanup)
-- ============================================================================
-- Uncomment to remove all events:
-- DROP EVENT IF EXISTS updateTaskStatusDaily;
-- DROP EVENT IF EXISTS updatePhaseStatusDaily;
-- DROP EVENT IF EXISTS updateProjectStatusDaily;
-- DROP EVENT IF EXISTS updateTaskStatusHourly;

-- ============================================================================
-- End of Event Scheduler Configuration
-- ============================================================================

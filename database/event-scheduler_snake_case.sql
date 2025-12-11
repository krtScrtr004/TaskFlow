-- ============================================================================
-- TaskFlow Automated Status Update Event Scheduler (snake_case version)
-- ============================================================================
-- This script creates MySQL event schedulers to automatically update the 
-- status of projects, phases, and tasks based on their date fields.
-- 
-- Status Transition Rules:
-- 1. pending → onGoing: When current date/time >= start_date_time
-- 2. onGoing → delayed: When current date/time > completion_date_time 
--                       AND actual_completion_date_time IS NULL
-- 3. delayed → completed: When actual_completion_date_time IS NOT NULL
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
-- Updates phase_task status based on date conditions
-- Runs daily at 00:05 AM (after midnight to catch all date changes)
-- ============================================================================

DROP EVENT IF EXISTS `update_task_status_daily`;

DELIMITER $$
CREATE EVENT `update_task_status_daily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 5 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates task status based on start_date_time and completion_date_time'
DO
BEGIN
    -- Step 1: Update pending tasks to onGoing when start date is reached
    UPDATE `phase_task`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;
    
    -- Step 2: Update onGoing tasks to delayed when completion date has passed
    UPDATE `phase_task`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    -- Step 3: Update pending tasks directly to delayed if both dates have passed
    -- (for cases where tasks were never started and are already late)
    UPDATE `phase_task`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
      
    -- Log the number of updated records (optional, for debugging)
    -- SELECT CONCAT('Task status updated at ', CURRENT_DATE) AS log_message;
END$$
DELIMITER ;

-- ============================================================================
-- EVENT 2: Update Phase Status Daily
-- ============================================================================
-- Updates project_phase status based on date conditions
-- Runs daily at 00:10 AM (5 minutes after task updates)
-- ============================================================================

DROP EVENT IF EXISTS `update_phase_status_daily`;

DELIMITER $$
CREATE EVENT `update_phase_status_daily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 10 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates phase status based on start_date_time and completion_date_time'
DO
BEGIN
    -- Step 1: Update pending phases to onGoing when start date is reached
    UPDATE `project_phase`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;
    
    -- Step 2: Update onGoing phases to delayed when completion date has passed
    UPDATE `project_phase`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    -- Step 3: Update pending phases directly to delayed if both dates have passed
    UPDATE `project_phase`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    -- Step 4: Auto-complete phases when all tasks are completed
    UPDATE `project_phase` AS pp
    SET `status` = 'completed',
        `actual_completion_date_time` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actual_completion_date_time`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `phase_task` AS pt
          WHERE pt.phase_id = pp.id
            AND pt.status != 'completed'
            AND pt.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `phase_task` AS pt2
          WHERE pt2.phase_id = pp.id
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

DROP EVENT IF EXISTS `update_project_status_daily`;

DELIMITER $$
CREATE EVENT `update_project_status_daily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 15 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates project status based on start_date_time and completion_date_time'
DO
BEGIN
    -- Step 1: Update pending projects to onGoing when start date is reached
    UPDATE `project`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;
    
    -- Step 2: Update onGoing projects to delayed when completion date has passed
    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    -- Step 3: Update pending projects directly to delayed if both dates have passed
    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    -- Step 4: Auto-complete projects when all phases are completed
    UPDATE `project` AS p
    SET `status` = 'completed',
        `actual_completion_date_time` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actual_completion_date_time`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `project_phase` AS pp
          WHERE pp.project_id = p.id
            AND pp.status != 'completed'
            AND pp.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `project_phase` AS pp2
          WHERE pp2.project_id = p.id
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

DROP EVENT IF EXISTS `update_task_status_hourly`;

DELIMITER $$
CREATE EVENT `update_task_status_hourly`
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
        UPDATE `phase_task`
        SET `status` = 'onGoing'
        WHERE `status` = 'pending'
          AND `start_date_time` <= CURRENT_DATE
          AND `completion_date_time` > CURRENT_DATE;
        
        -- Update onGoing tasks to delayed
        UPDATE `phase_task`
        SET `status` = 'delayed'
        WHERE `status` = 'onGoing'
          AND `completion_date_time` < CURRENT_DATE
          AND `actual_completion_date_time` IS NULL;
        
        -- Update pending tasks to delayed (overdue before start)
        UPDATE `phase_task`
        SET `status` = 'delayed'
        WHERE `status` = 'pending'
          AND `start_date_time` <= CURRENT_DATE
          AND `completion_date_time` < CURRENT_DATE
          AND `actual_completion_date_time` IS NULL;
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
-- UPDATE phase_task SET start_date_time = CURRENT_DATE - INTERVAL 1 DAY WHERE id = <test_id>;

-- ============================================================================
-- Disable/Enable Events
-- ============================================================================
-- To temporarily disable an event:
-- ALTER EVENT update_task_status_daily DISABLE;
-- 
-- To re-enable:
-- ALTER EVENT update_task_status_daily ENABLE;
--
-- To disable all events:
-- SET GLOBAL event_scheduler = OFF;

-- ============================================================================
-- Drop All Events (Cleanup)
-- ============================================================================
-- Uncomment to remove all events:
-- DROP EVENT IF EXISTS update_task_status_daily;
-- DROP EVENT IF EXISTS update_phase_status_daily;
-- DROP EVENT IF EXISTS update_project_status_daily;
-- DROP EVENT IF EXISTS update_task_status_hourly;

-- ============================================================================
-- End of Event Scheduler Configuration
-- ============================================================================

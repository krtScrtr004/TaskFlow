-- TaskFlow Migration: Rename core tables
--
-- Renames:
-- - project_phase        -> phase
-- - project_phase_budget -> phase_budget
-- - phase_task           -> task
-- - phase_task_budget    -> task_budget
-- - phase_task_worker    -> task_worker
-- - task_resource        -> resource
-- - user_job_title       -> job_title
--
-- Notes:
-- - Table renames preserve records.
-- - MySQL automatically updates foreign key references on RENAME TABLE.
-- - Triggers move with tables during rename, but trigger bodies/events that
--   reference old table names must be recreated.

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

RENAME TABLE
  `project_phase` TO `phase`,
  `project_phase_budget` TO `phase_budget`,
  `phase_task` TO `task`,
  `phase_task_budget` TO `task_budget`,
  `phase_task_worker` TO `task_worker`,
  `task_resource` TO `resource`,
  `user_job_title` TO `job_title`;

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- --------------------------------------------------------------------------
-- Recreate triggers that reference renamed tables
-- --------------------------------------------------------------------------

DROP TRIGGER IF EXISTS `cancel_project`;
DELIMITER $$
CREATE TRIGGER `cancel_project` AFTER UPDATE ON `project` FOR EACH ROW
BEGIN
    IF NEW.status = 'cancelled' AND OLD.status <> 'cancelled' THEN
        UPDATE `phase`
        SET status = 'cancelled'
        WHERE project_id = NEW.id;

        UPDATE `task`
        SET status = 'cancelled'
        WHERE phase_id IN (
            SELECT id FROM `phase` WHERE project_id = NEW.id
        );
    END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `terminate_worker_tasks_on_project_worker_termination`;
DELIMITER $$
CREATE TRIGGER `terminate_worker_tasks_on_project_worker_termination` AFTER UPDATE ON `project_worker` FOR EACH ROW
BEGIN
    IF NEW.status = 'terminated' AND OLD.status <> 'terminated' THEN
        UPDATE `task_worker` AS tw
        INNER JOIN `task` AS t ON tw.task_id = t.id
        INNER JOIN `phase` AS p ON t.phase_id = p.id
        SET tw.status = 'terminated'
        WHERE tw.worker_id = NEW.worker_id
          AND p.project_id = NEW.project_id
          AND tw.status = 'assigned';
    END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `delete_job_titles_on_user_delete`;
DELIMITER $$
CREATE TRIGGER `delete_job_titles_on_user_delete` AFTER UPDATE ON `user` FOR EACH ROW
BEGIN
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        DELETE FROM `job_title` WHERE user_id = NEW.id;
    END IF;
END$$
DELIMITER ;

-- --------------------------------------------------------------------------
-- Recreate events that reference renamed tables
-- --------------------------------------------------------------------------

DROP EVENT IF EXISTS `update_task_status_daily`;
DROP EVENT IF EXISTS `update_phase_status_daily`;
DROP EVENT IF EXISTS `update_project_status_daily`;
DROP EVENT IF EXISTS `update_task_status_hourly`;

DELIMITER $$
CREATE EVENT `update_task_status_daily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 5 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates task status based on start_date_time and completion_date_time'
DO
BEGIN
    UPDATE `task`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;

    UPDATE `task`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;

    UPDATE `task`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT `update_phase_status_daily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 10 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates phase status based on start_date_time and completion_date_time'
DO
BEGIN
    UPDATE `phase`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;

    UPDATE `phase`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;

    UPDATE `phase`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;

    UPDATE `phase` AS p
    SET `status` = 'completed',
        `actual_completion_date_time` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actual_completion_date_time`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `task` AS t
          WHERE t.phase_id = p.id
            AND t.status != 'completed'
            AND t.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `task` AS t2
          WHERE t2.phase_id = p.id
            AND t2.status = 'completed'
      );
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT `update_project_status_daily`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 15 MINUTE
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Automatically updates project status based on start_date_time and completion_date_time'
DO
BEGIN
    UPDATE `project`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;

    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;

    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;

    UPDATE `project` AS p
    SET `status` = 'completed',
        `actual_completion_date_time` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actual_completion_date_time`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `phase` AS ph
          WHERE ph.project_id = p.id
            AND ph.status != 'completed'
            AND ph.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `phase` AS ph2
          WHERE ph2.project_id = p.id
            AND ph2.status = 'completed'
      );
END$$
DELIMITER ;

DELIMITER $$
CREATE EVENT `update_task_status_hourly`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_DATE + INTERVAL 6 HOUR
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Hourly task status updates during business hours for more immediate feedback'
DO
BEGIN
    IF HOUR(CURRENT_DATE) BETWEEN 6 AND 22 THEN
        UPDATE `task`
        SET `status` = 'onGoing'
        WHERE `status` = 'pending'
          AND `start_date_time` <= CURRENT_DATE
          AND `completion_date_time` > CURRENT_DATE;

        UPDATE `task`
        SET `status` = 'delayed'
        WHERE `status` = 'onGoing'
          AND `completion_date_time` < CURRENT_DATE
          AND `actual_completion_date_time` IS NULL;

        UPDATE `task`
        SET `status` = 'delayed'
        WHERE `status` = 'pending'
          AND `start_date_time` <= CURRENT_DATE
          AND `completion_date_time` < CURRENT_DATE
          AND `actual_completion_date_time` IS NULL;
    END IF;
END$$
DELIMITER ;

-----------------------+++++++++++++++++++++++++++++++-----------------------

---- if event is not on then run the below command ----
SET GLOBAL event_scheduler = ON;

---- query to check event scheduler on or off ----
SHOW VARIABLES WHERE VARIABLE_NAME = 'event_scheduler'

---- query to check user status ----
SELECT * FROM `boxes` WHERE  `edit_user_status` = '1' AND DATE_ADD(edited_at,INTERVAL 10 MINUTE);
CALL PR_Update_Edit_Status();


---- CREATE STORED PROCEDURE TO UPDATE IDLE STATUS ----

DELIMITER $$

DROP PROCEDURE IF EXISTS `PR_Update_Edit_Status`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `PR_Update_Edit_Status`()
BEGIN
DECLARE boxId BIGINT(20);
DECLARE v_finished INTEGER UNSIGNED;

DECLARE cur1 CURSOR FOR

SELECT `id` FROM `boxes` WHERE  `edit_user_status` = '1' AND DATE_ADD(edited_at,INTERVAL 10 MINUTE);

DECLARE CONTINUE HANDLER FOR NOT FOUND SET  v_finished = 1;
OPEN cur1;

read_loop: LOOP

FETCH cur1 INTO boxId;

IF v_finished = 1 THEN
LEAVE read_loop;
END IF;

UPDATE boxes SET edit_user_id='0', edit_user_status='0', edited_at=NULL WHERE id=boxId;

SET v_finished= v_finished-1;

END LOOP read_loop;
CLOSE cur1;

END$$

DELIMITER ;

---- CREATE EVENT TO CALL THE SP ----

DELIMITER $$

CREATE DEFINER=`root`@`localhost` EVENT `Evt_Box_Session_Status_Update` ON SCHEDULE EVERY 5 MINUTE STARTS '2015-09-15 09:02:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN

CALL PR_Update_Edit_Status();

END$$

DELIMITER ;

/*
SQLyog Community
MySQL - 5.7.33 
*********************************************************************
*/
/*!40101 SET NAMES utf8 */;

create table `fw_notifications` (
	`id` bigint (20),
	`title` varchar (765),
	`description` varchar (765),
	`link` text ,
	`user_id` int (11),
	`is_seen` tinyint (1),
	`is_read` tinyint (1),
	`created_at` timestamp ,
	`updated_at` timestamp 
); 

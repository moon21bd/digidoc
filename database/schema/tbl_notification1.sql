/*
SQLyog Community v13.1.9 (64 bit)
MySQL - 5.7.33 : Database - digi_doc
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`digi_doc` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `digi_doc`;

/*Table structure for table `fw_notifications` */

DROP TABLE IF EXISTS `fw_notifications`;

CREATE TABLE `fw_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `link` text COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_seen` tinyint(1) NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `fw_notifications` */

insert  into `fw_notifications`(`id`,`title`,`description`,`link`,`user_id`,`is_seen`,`is_read`,`created_at`,`updated_at`) values 
(1,'Pusher.js Test Notifications 46','Very little is needed to make a happy life. - Marcus Aurelius','http://digidoc.oo:90/admin/auth/users',1,1,0,'2022-03-06 04:22:40','2022-03-07 16:29:59'),
(2,'Pusher.js Test Notifications 96','Waste no more time arguing what a good man should be, be one. - Marcus Aurelius','http://digidoc.oo:90/admin/auth/users',1,1,0,'2022-03-06 04:23:04','2022-03-07 16:29:59'),
(3,'Pusher.js Test Notifications 61','The only way to do great work is to love what you do. - Steve Jobs','http://digidoc.oo:90/admin/auth/users',1,0,0,'2022-03-07 16:30:05','2022-03-07 16:30:05'),
(4,'Pusher.js Test Notifications 27','Simplicity is the essence of happiness. - Cedric Bledsoe','http://digidoc.oo:90/admin/auth/users',1,0,0,'2022-03-07 16:30:33','2022-03-07 16:30:33'),
(5,'Pusher.js Test Notifications 88','Simplicity is the essence of happiness. - Cedric Bledsoe','http://digidoc.oo:90/admin/auth/users',1,0,0,'2022-03-07 16:31:03','2022-03-07 16:31:03'),
(6,'Pusher.js Test Notifications 88','Simplicity is the essence of happiness. - Cedric Bledsoe','http://digidoc.oo:90/admin/auth/users',1,0,0,'2022-03-07 16:31:03','2022-03-07 16:31:03'),
(7,'Pusher.js Test Notifications 88','Simplicity is the essence of happiness. - Cedric Bledsoe','http://digidoc.oo:90/admin/auth/users',1,0,0,'2022-03-07 16:31:03','2022-03-07 16:31:03'),
(8,'Pusher.js Test Notifications 88','Simplicity is the essence of happiness. - Cedric Bledsoe','http://digidoc.oo:90/admin/auth/users',1,0,0,'2022-03-07 16:31:03','2022-03-07 16:31:03');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

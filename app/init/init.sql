-- Table structure for table `otp_codes`
DROP TABLE IF EXISTS `otp_codes`;
CREATE TABLE `otp_codes` (
                             `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                             `user_id` int(11) NOT NULL,
                             `code` char(6) NOT NULL,
                             `created_at` datetime NOT NULL,
                             PRIMARY KEY (`id`),
                             KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- Table structure for table `service_attempts`

DROP TABLE IF EXISTS `service_attempts`;
CREATE TABLE `service_attempts` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `queue_id` int(11) NOT NULL,
                                    `result` varchar(20) NOT NULL,
                                    `created_at` datetime NOT NULL,
                                    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- Table structure for table `service_queue`

DROP TABLE IF EXISTS `service_queue`;
CREATE TABLE `service_queue` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `service` varchar(255) NOT NULL,
                                 `status` tinyint(4) NOT NULL,
                                 `params` varchar(255) NOT NULL,
                                 `last_attempt` datetime NOT NULL,
                                 `created_at` datetime NOT NULL,
                                 `count_retries` int(11) NOT NULL DEFAULT '0',
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- Table structure for table `validation_attempts`

DROP TABLE IF EXISTS `validation_attempts`;
CREATE TABLE `validation_attempts` (
                                       `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                       `user_id` int(11) NOT NULL,
                                       `is_valid` tinyint(1) NOT NULL,
                                       `created_at` datetime NOT NULL,
                                       PRIMARY KEY (`id`),
                                       KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;

-- Table structure for table `user`

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `email` varchar(255) NOT NULL,
                        `phone` varchar(255) NOT NULL,
                        `password` varchar(255) NOT NULL,
                        `validated` tinyint(1) NOT NULL DEFAULT '0',
                        `created_at` datetime NOT NULL,
                        PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

-- Constraints for dumped tables

ALTER TABLE `otp_codes`
    ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `validation_attempts`
    ADD CONSTRAINT `fk_validation_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
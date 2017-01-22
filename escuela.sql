DROP TABLE IF EXISTS _escuela_teacher;
CREATE TABLE _escuela_teacher(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	 email varchar(255),
        PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS _escuela_course;
CREATE TABLE _escuela_course(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`content` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
	`teacher` int(11),
	 email varchar(255),
	 active tinyint(1) NOT NULL DEFAULT 0,
	 FOREIGN KEY (`teacher`) REFERENCES `_escuela_teacher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	 PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS _escuela_chapter;
CREATE TABLE _escuela_chapter(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title`  varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`content` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
	`course` int(11),
	`xtype` ENUM('CAPITULO', 'PRUEBA') DEFAULT 'CAPITULO',
	`xorder` int(11),
	 PRIMARY KEY (`id`),
	 FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

DROP TABLE IF EXISTS _escuela_question;
CREATE TABLE _escuela_question(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title`  varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`chapter` int(11),
        `course` int(11),
	`xorder` int(11),
	`answer` int(11) NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

DROP TABLE IF EXISTS _escuela_answer;
CREATE TABLE _escuela_answer(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`xorder` int(11),
	`right_choosen` tinyint(1) NOT NULL DEFAULT 0,
        `question` int(11) NOT NULL,
        `chapter` int(11),
        `course` int(11),
	PRIMARY KEY (`id`),
	FOREIGN KEY (`question`) REFERENCES `_escuela_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

DROP TABLE IF EXISTS _escuela_answer_choosen;
CREATE TABLE _escuela_answer_choosen(
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `answer` int(11) NOT NULL,
  `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `chapter` int(11) NOT NULL,
  `question` int(11) NOT NULL,
   PRIMARY KEY (`email`,`answer`),
   FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`question`) REFERENCES `_escuela_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

DROP TABLE IF EXISTS _escuela_images;
CREATE TABLE _escuela_images(
   id varchar(50) NOT NULL,
   filename varchar(255) COLLATE utf8_unicode_ci NOT NULL,
   mime_type varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `chapter` int(11) NOT NULL,
  `course` int(11) NOT NULL,
   PRIMARY KEY (id),
   FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
); 
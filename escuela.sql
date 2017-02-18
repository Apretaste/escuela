DROP TABLE IF EXISTS _escuela_chapter_viewed;
DROP TABLE IF EXISTS _escuela_answer_choosen;
DROP TABLE IF EXISTS _escuela_images;
DROP TABLE IF EXISTS _escuela_answer;
DROP TABLE IF EXISTS _escuela_question;
DROP TABLE IF EXISTS _escuela_chapter;
DROP TABLE IF EXISTS _escuela_course;
DROP TABLE IF EXISTS _escuela_teacher;
DROP TABLE IF EXISTS _escuela_feedback;
DROP TABLE IF EXISTS _escuela_feedback_received;

CREATE TABLE _escuela_teacher(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	 email varchar(255),
        PRIMARY KEY (`id`)
);

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

CREATE TABLE _escuela_answer_choosen(
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
   `answer` int(11) NOT NULL,
   `question` int(11) NOT NULL,
   `chapter` int(11),
   `course` int(11),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`answer`) REFERENCES `_escuela_answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`question`) REFERENCES `_escuela_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE (`email`,`question`)
);

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

CREATE TABLE _escuela_chapter_viewed(
   `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
   `chapter` int(11),
   `course` int(11) NOT NULL,
   `date_viewed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   PRIMARY KEY (email, chapter)
);

drop table if exists _escuela_feedback;
create table _escuela_feedback(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	 text varchar(255) NOT NULL,
	 answers varchar(255) NOT NULL, -- comma separated phrases, first = 1, last = N
	 PRIMARY KEY (id)
);

drop table if exists _escuela_feedback_received;
create table _escuela_feedback_received(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`feedback` int(11) NOT NULL,
	`course` int(11) NOT NULL,
	`email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	 answer varchar(255),
	 FOREIGN KEY (`feedback`) REFERENCES `_escuela_feedback` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	 FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	 PRIMARY KEY (id)
);

ALTER TABLE _escuela_course ADD COLUMN popularity float; --?

DELETE FROM _escuela_feedback;
INSERT INTO _escuela_feedback (id,text,answers) VALUES (1,'C&oacute;mo eval&uacute;a en general este curso?','malo,regular,bueno,excelente');
INSERT INTO _escuela_feedback (id,text,answers) VALUES (2,'Cu&aacute;nto ha aprendido de este curso?','nada,algo,mucho,cantidad');
INSERT INTO _escuela_feedback (id,text,answers) VALUES (3,'Cuan f&aacute;cil de entender es el contenido?','enredado,complejo,le&iacute;ble,simple');
INSERT INTO _escuela_feedback (id,text,answers) VALUES (4,'Recomendar&iacute;a este curso a otros?','nunca,tal vez,seguro');

DROP TABLE IF EXISTS _escuela_chapter_viewed;
DROP TABLE IF EXISTS _escuela_answer_choosen;
DROP TABLE IF EXISTS _escuela_answer;
DROP TABLE IF EXISTS _escuela_question;
DROP TABLE IF EXISTS _escuela_chapter;
DROP TABLE IF EXISTS _escuela_course;
DROP TABLE IF EXISTS _escuela_teacher;
DROP TABLE IF EXISTS _escuela_feedback_received;
DROP TABLE IF EXISTS _escuela_feedback;

CREATE TABLE _escuela_teacher(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`title` varchar(255) NOT NULL,
	email varchar(255),
		PRIMARY KEY (`id`)
);

CREATE TABLE _escuela_course(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title` varchar(255) NOT NULL,
	`content` varchar(1024) NOT NULL,
	`teacher` int(11),
	email varchar(255),
	active tinyint(1) NOT NULL DEFAULT 0,
	popularity int(11) NOT NULL DEFAULT 0,
	category enum('SOCIEDAD','NEGOCIOS','MEDICINA','INFORMATICA','INGENIERIA','LETRAS','ARTES','FILOSOFIA','SALUD','POLITICA','TECNICA','OTRO'),
	FOREIGN KEY (`teacher`) REFERENCES `_escuela_teacher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (`id`)
);

CREATE TABLE _escuela_chapter(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title`  varchar(255) NOT NULL,
	`content` varchar(1024) NOT NULL,
	`course` int(11),
	`xtype` ENUM('CAPITULO', 'PRUEBA') DEFAULT 'CAPITULO',
	`xorder` int(11),
	PRIMARY KEY (`id`),
	FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE _escuela_question(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title`  varchar(255) NOT NULL,
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
	`title` varchar(255) NOT NULL,
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
	`email` varchar(255) NOT NULL,
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

CREATE TABLE _escuela_chapter_viewed(
	`email` varchar(255) NOT NULL,
	`chapter` int(11),
	`course` int(11) NOT NULL,
	`date_viewed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (`chapter`) REFERENCES `_escuela_chapter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (email, chapter)
);

create table _escuela_feedback(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	text varchar(255) NOT NULL,
	answers varchar(255) NOT NULL, -- comma separated phrases (pairs value:caption), first = 1, last = N
	PRIMARY KEY (id)
);

create table _escuela_feedback_received(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`feedback` int(11) NOT NULL,
	`course` int(11) NOT NULL,
	`email` varchar(255) NOT NULL,
	`date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	answer varchar(255),
	FOREIGN KEY (`feedback`) REFERENCES `_escuela_feedback` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`course`) REFERENCES `_escuela_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

DELETE FROM _escuela_feedback;
INSERT INTO _escuela_feedback (id,text,answers) VALUES (1,'Cómo evalúa en general este curso?','malo,regular,bueno,excelente');
INSERT INTO _escuela_feedback (id,text,answers) VALUES (2,'Cuánto ha aprendido de este curso?','nada,algo,mucho,cantidad');
INSERT INTO _escuela_feedback (id,text,answers) VALUES (3,'Cuan fácil de entender es el contenido?','enredado,complejo,leible:Leíble,simple');
INSERT INTO _escuela_feedback (id,text,answers) VALUES (4,'Recomendaría este curso a otros?','nunca,tal vez,seguro');

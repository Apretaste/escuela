-- para guardar los campos especificos del perfil en escuela que no estan en el core (table person)
CREATE TABLE _escuela_profile (
	person_id int(11) primary key references person(id) on delete cascade on update cascade,
	level enum('PRINCIPIANTE','LITERADO','ESTUDIOSO','EDUCADO','EXPERTO','MAESTRO','GURU')
);

-- para guardar que medalla se adquiere por cada curso
ALTER TABLE _escuela_course ADD COLUMN medal varchar(30);

CREATE TABLE _escuela_stars (
	course int(11) references _escuela_course(id) on delete cascade on update cascade,
	person_id int(11) references person(id) on delete cascade on update cascade,
	stars int(1) default 0,
	primary key (course, person_id)
);
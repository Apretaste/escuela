<?php

use Phalcon\DI\FactoryDefault;

class Service
{

	/** @var Response */
	public $response = null;

	/** @var Request */
	public $request = null;

	/** @var array */
	private $files = [];

	/**
	 * Database query
	 *
	 * @param        $sql
	 * @param string $set_charset
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public static function query($sql, $set_charset = 'utf8mb4')
	{
		return Connection::query($sql, true, $set_charset);
	}


	/**
	 * Main function
	 *
	 * @author salvipascual
	 *
	 */
	public function _main()
	{
		$person_id = $this->request->person->id;
		$person = Utils::getPerson($person_id);
		$this->setLevel();

		// get the most popular courses
		$courses = self::query("
		  SELECT * FROM (
				SELECT A.id, A.title, A.content, A.popularity, A.category, B.name AS 'professor',
				A.teacher, COALESCE((SELECT AVG(stars) FROM _escuela_stars WHERE course = A.id), 0) AS stars,
				(select count(*) from _escuela_chapter_viewed where A.id = _escuela_chapter_viewed.course and person_id = '$person_id') as viewed,
				(select count(*) from _escuela_question where A.id = _escuela_question.course) as questions,
				(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
				(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course AND _escuela_chapter.xtype = 'PRUEBA') as tests,
				(select count(*) from _escuela_answer where A.id = _escuela_answer.course) as answers,
				(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = '$person_id') as answers_choosen
				FROM _escuela_course A
				JOIN _escuela_teacher B
				ON A.teacher = B.id
				WHERE A.active = 1
				) subq
				WHERE viewed < chapters - tests or answers_choosen < questions -- no se han visto todos, no se ha respondido todas
				ORDER BY viewed/nullif(chapters,0) desc,  answers_choosen/nullif(answers,0) desc, popularity DESC
			LIMIT 10");

		// remove extrange chars
		foreach ($courses as $k => $c) {
			$course       = $this->getCourse($c->id, $this->request->person->id);
			$c->progress  = $course->progress;
			$c->title     = htmlspecialchars($c->title);
			$c->content   = htmlspecialchars($c->content);
			$c->professor = htmlspecialchars($c->professor);
			$c->author    = $c->professor;
			$c->stars     = intval($c->stars);
			$courses[$k]  = $c;
		}

		$level = 'PRINCIPIANTE';
		$r     = self::query("SELECT level FROM _escuela_profile WHERE person_id = '{$this->request->person->id}'");
		if (isset($r[0])) {
			$level = $r[0]->level;
		}

		$this->setFontFiles();

		// setup response
		$this->response->setLayout('escuela.ejs');
		$this->response->setTemplate('home.ejs', [
			"max_stars" => 5,
			"courses"   => $courses,
			// si no ha completado el nombre en el perfil debe decir solo Bienvenido
			"name"      => $person->first_name ? $person->first_name : '',
			"level"     => $level,
			"completed" => $this->getTotalCompletedCourses($person_id),
		], [], $this->files);

		$this->response->setCache(60);
	}

	/**
	 * Buscar cursos
	 *
	 */
	public function _buscar()
	{
		$where = '';
		$data  = null;
		if (isset($this->request->input->data->query)) {
			$data = $this->request->input->data->query;
			if (isset($data->category)
				|| isset($data->author)
				|| isset($data->raiting)
				|| isset($data->title)
			) {
				$where = ' ';
				if (isset($data->category)) {
					if ($data->category !== 'ALL') {
						$where .= " AND category = '{$data->category}'";
					}
				}
				if (isset($data->author)) {
					if ($data->author !== 'ALL') {
						$where .= " AND teacher = '{$data->author}'";
					}
				}
				if (isset($data->raiting)) {
					if ($data->raiting !== '0') {
						$where .= " AND stars >= '{$data->raiting}'";
					}
				}
				if (isset($data->title)) {
					if ($data->title !== '') {
						$where .= " AND title LIKE '%{$data->title}%'";
					}
				}
			}
		}

		$courses = [];
		if (!empty(trim($where))) {
			$courses = self::query("
			SELECT * FROM (
			SELECT A.id, A.title, A.content, A.popularity, A.category, B.name AS 'professor', A.teacher,
			COALESCE((SELECT AVG(stars) FROM _escuela_stars WHERE course = A.id), 0) AS stars,
			(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
			(select count(*) from _escuela_answer where A.id = _escuela_answer.course) as answers,
			(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = '{$this->request->person->id}') as answers_choosen
			FROM _escuela_course A
			JOIN _escuela_teacher B
			ON A.teacher = B.id
			WHERE A.active = 1) subq
			WHERE 1 $where ORDER BY popularity DESC LIMIT 10");
		}

		if (! is_array($courses)) {
			$courses = [];
		}

		$noResults = empty($courses);
		$noSearch  = empty(trim($where));

		// remove estrange chars
		foreach ($courses as $k => $c) {
			$course       = $this->getCourse($c->id, $this->request->person->id);
			$c->progress  = $course->progress;
			$c->title     = htmlspecialchars($c->title);
			$c->content   = htmlspecialchars($c->content);
			$c->professor = htmlspecialchars($c->professor);
			$c->author    = $c->professor;
			$c->stars     = intval($c->stars);
			$courses[$k]  = $c;
		}

		$this->setFontFiles();

		// display the course
		$this->response->setLayout('escuela.ejs');
		$this->response->setTemplate('search.ejs', [
			"categories" => [
				'SOCIEDAD'    => 'Sociedad',
				'NEGOCIOS'    => 'Negocios',
				'MEDICINA'    => 'Medicina',
				'INFORMATICA' => html_entity_decode('Inform&aacute;tica'),
				'INGENIERIA'  => html_entity_decode('Ingenier&iacute;a'),
				'LETRAS'      => 'Letras',
				'ARTES'       => 'Artes',
				'FILOSOFIA'   => html_entity_decode('Filosof&iacute;a'),
				'SALUD'       => 'Salud',
				'POLITICA'    => html_entity_decode('Pol&iacute;tica'),
				'TECNICA'     => html_entity_decode('T&eacute;cnica'),
				'OTRO'        => 'Otros',
			],
			"authors"    => $this->getTeachers(),
			"courses"    => $courses,
			"data"       => $data,
			"noResults"  => $noResults,
			"noSearch"   => $noSearch,
			"max_stars"  => 5,
		], [], $this->files);

		$this->response->setCache('month');
	}

	/**
	 * Retrieve a course
	 *
	 *
	 * @author  kuma
	 * @example ESCUELA CURSO 2
	 *
	 */
	public function _curso()
	{
		// get the course details
		$id     = intval($this->request->input->data->query);
		$course = $this->getCourse($id, $this->request->person->id);

		// if course cannot be found
		if (empty($course)) {
			$this->response->setLayout('escuela.ejs');
			$this->response->setTemplate('text.ejs', [
				"title" => "Curso no encontrado",
				"body"  => "No encontramos el curso que usted pidio",
			], [], $this->files);

			return;
		}

		$this->setFontFiles();

		// display the course
		$this->response->setLayout('escuela.ejs');
		$this->response->setTemplate('course.ejs', ['course' => $course], [], $this->files);
	}

	/**
	 * Subservice CAPITULO
	 *
	 *
	 * @author  kuma
	 * @example ESCUELA CAPITULO 3
	 *
	 */
	public function _capitulo()
	{
		$id      = intval($this->request->input->data->query);
		$chapter = $this->getChapter($id, $this->request->person->id);

		$this->setFontFiles();

		if ($chapter) {
			$beforeAfter      = $this->getBeforeAfter($chapter);
			$images           = $this->getChapterImages($id);
			$chapter->content = Utils::putInlineImagesToHTML($chapter->content, $images, 'cid:');

			$course = $this->getCourse($chapter->course, $this->request->person->id);
			$terminated = $course->terminated;

			// Log the visit to this chapter
			if ($chapter->xtype == 'CAPITULO') {
				self::query("INSERT IGNORE INTO _escuela_chapter_viewed (person_id, email, chapter, course) VALUES ('{$this->request->person->id}','{$this->request->person->email}', '{$id}', '{$chapter->course}');");
			}

			// get the code inside the <body> tag
			if (stripos($chapter->content, '<body>') !== false) {
				$ini              = strpos($chapter->content, '<body>') + 6;
				$end              = strpos($chapter->content, '</body>');
				$chapter->content = substr($chapter->content, $ini, $end - $ini);
			}

			// check if the course is terminated
			$course = $this->getCourse($chapter->course, $this->request->person->id);

			if (!$terminated && $course->terminated) { // si el status terminated del curso cambio de false a true
				Challenges::complete("complete-course", $this->request->person->id);

				// add the experience if profile is completed
				Level::setExperience('FINISH_COURSE', $this->request->person->id);
			}

			// send response to the view

			$this->response->setLayout('escuela.ejs');
			$this->response->setTemplate('chapter.ejs', [
				'chapter' => $chapter,
				'course'  => $course,
				'before'  => $beforeAfter['before'],
				'after'   => $beforeAfter['after'],
			], $images, $this->files);

			return;
		}

		$this->response->setLayout('escuela.ejs');
		$this->response->setTemplate('text.ejs', ['title' => 'Lo Sentimos', 'body' => 'Capitulo no encontrado'], [], $this->files);
	}

	/**
	 * Subservice PRUEBA
	 *
	 * @example ESCUELA PRUEBA 2
	 */
	public function _prueba()
	{
		$this->_capitulo();
	}

	/**
	 * Records the answer for a question and resturns an empty response
	 */
	public function _responder()
	{
		// pull the answer selected
		$answers = $this->request->input->data->answers;
		// check if the course is terminated


		foreach ($answers as $id) {
			$res = self::query("SELECT * FROM _escuela_answer WHERE id=$id");

			// do not let pass invalid answers
			if (empty($res)) {
				continue;
			} else {
				$answer = $res[0];
			}

			$course = $this->getCourse($answer->course, $this->request->person->id);
			$terminated =  $course->terminated;

			// save the answer in the database

			self::query("
			INSERT IGNORE INTO _escuela_answer_choosen (person_id, email, answer, chapter, question, course)
			VALUES ('{$this->request->person->id}','{$this->request->person->email}','$id', '{$answer->chapter}', '{$answer->question}', '{$answer->course}')");

			$course = $this->getCourse($answer->course, $this->request->person->id);

			if (!$terminated && $course->terminated) { // si el status terminated del curso cambio de false a true
				Challenges::complete("complete-course", $this->request->person->id);

				// add the experience if profile is completed
				Level::setExperience('FINISH_COURSE', $this->request->person->id);
			}

			$this->setLevel();
		}
	}

	/**
	 * Set level
	 *
	 */
	public function setLevel()
	{
		$resume = $this->getResume($this->request->person->id);
		$total  = 0;
		foreach ($resume as $item) {
			if ($item->answers > 0) {
				if ($item->right_answers / $item->answers >= 0.8) {
					$total++;
				}
			}
		}

		$level = 'PRINCIPIANTE';

		if ($total >= 1) {
			$level = 'LITERADO';
		}
		if ($total >= 3) {
			$level = 'ESTUDIOSO';
		}
		if ($total >= 6) {
			$level = 'EDUCADO';
		}
		if ($total >= 10) {
			$level = 'EXPERTO';
		}
		if ($total >= 15) {
			$level = 'MAESTRO';
		}
		if ($total >= 30) {
			$level = 'GURU';
		}

		// update user level
		self::query("UPDATE _escuela_profile SET level = '$level' WHERE person_id = '{$this->request->person->id}';");
	}

	/**
	 * Rate course
	 */
	public function _calificar()
	{
		$course_id = $this->request->input->data->query->course;
		$stars     = $this->request->input->data->query->stars;
		$stars     = $stars > 5 ? 5 : $stars;

		self::query("INSERT IGNORE INTO _escuela_stars (course, person_id, stars) VALUES ('$course_id', '{$this->request->person->id}', '$stars');");
		self::query("UPDATE _escuela_stars SET stars = $stars WHERE course = $course_id AND person_id = {$this->request->person->id};");
	}

	/**
	 * Subservice OPINAR
	 *
	 */
	public function _opinar()
	{
		// expecting: course_id feedback_id answer
		$q = trim($this->request->input->data->query);

		Utils::clearStr($q);
		$feed = explode(' ', $q);

		if (!isset($feed[0]) || !isset($feed[1]) || !isset($feed[2])) {
			return;
		}

		$course_id   = intval($feed[0]);
		$feedback_id = intval($feed[1]);
		$answer      = trim(strtolower(($feed[2])));

		$course = $this->getCourse($course_id, $this->request->person->id);

		if ($course !== false) {
			$feedback = self::query("SELECT id, text, answers FROM _escuela_feedback WHERE id = $feedback_id;");
			if (isset($feedback[0])) {
				$feedback       = $feedback[0];
				$answers        = $feedback->answers;
				$feedback_where = " person_id = '{$this->request->person->id}' AND feedback = $feedback_id AND course = $course_id;";

				// get last answer, and decrease popularity of the course
				$last_answer = false;
				$r           = self::query("SELECT answer FROM _escuela_feedback_received WHERE $feedback_where;");
				if (isset($r[0])) {
					$last_answer = $r[0]->answer;
				}

				if ($last_answer !== false) {
					$popularity = $this->getAnswerValue($answers, $last_answer);
					self::query("DELETE FROM _escuela_feedback_received WHERE $feedback_where");

					if ($popularity !== false) {
						self::query("UPDATE _escuela_course SET popularity = popularity - $popularity WHERE id = $course_id;");
					}
				}

				// analyze current answer && increase popularity of the course
				$popularity = $this->getAnswerValue($answers, $answer);
				if ($popularity !== false) {
					self::query("INSERT INTO _escuela_feedback_received (feedback, course, person_id, email, answer) VALUES ($feedback_id, $course_id, '{$this->request->person->id}', '{$this->request->person->email}', '$answer');");
					self::query("UPDATE _escuela_course SET popularity = popularity + $popularity WHERE id = $course_id;");
				}
			}
		}
	}

	/**
	 * Repeats a test for a course
	 *
	 * @author kuma
	 */
	public function _repetir()
	{
		// remove the previous answers
		self::query("DELETE FROM _escuela_chapter_viewed WHERE course='{$this->request->input->data->query}' AND person_id='{$this->request->person->id}'");
		self::query("DELETE FROM _escuela_answer_choosen WHERE course='{$this->request->input->data->query}' AND person_id='{$this->request->person->id}'");

		// load the test again
		$this->_curso();

		// change response content
		//TODO: improve this feature in the core
		$data = @json_decode($this->response->json);

		if (is_array($data)) {
			$data = (object)$data;
		}

		if (!is_object($data)) {
			if (isset($data->course)) {
				$data->course->repeated = true;
				$this->response->json   = json_encode($data, JSON_UNESCAPED_UNICODE);
			}
		}
	}

	/**
	 * Perfil de escuela
	 *
	 */
	public function _perfil()
	{

		// save profile
		if (isset($this->request->input->data->save)) {
			$fields = [
				'first_name',
				'last_name',
				'date_of_birth',
				'gender',
				'province',
				'city',
				'highest_school_level',
				'occupation',
				'country',
				'usstate',
			];

			// get the JSON with the bulk
			$pieces = [];
			foreach ($this->request->input->data->query as $key => $value) {
				if ($key == 'date_of_birth') {
					$value = DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d');
				}

				if (in_array($key, $fields)) {
					$pieces[] = "$key='$value'";
				}
			}

			// save changes on the database
			if (!empty($pieces)) {
				self::query("UPDATE person SET " . implode(",", $pieces) . " WHERE id={$this->request->person->id}");
			}

			return;
		}

		// show profile
		$resume         = $this->getResume($this->request->person->id);
		$profile        = Utils::getPerson($this->request->person->id);
		$profile->level = 'PRINCIPIANTE';
		$r              = self::query("SELECT * FROM _escuela_profile WHERE person_id = '{$this->request->person->id}'");
		if (!isset($r[0])) {
			self::query("INSERT INTO _escuela_profile (person_id, level) VALUES ('{$this->request->person->id}','PRINCIPIANTE');");
		} else {
			$profile->level = $r[0]->level;
		}

		$r = self::query("SELECT COLUMN_TYPE AS result
				FROM information_schema.COLUMNS
				WHERE TABLE_NAME = '_escuela_profile'
							AND COLUMN_NAME = 'level';");

		$this->setFontFiles();

		$levels = explode(",", str_replace(["'", "enum(", ")"], "", $r[0]->result));
		$this->response->setLayout('escuela.ejs');
		$this->response->setTemplate("profile.ejs", [
			"resume"  => $resume,
			"profile" => $profile,
			"levels"  => $levels,
		], [], $this->files);
	}

	/**
	 * Cursos terminados
	 *
	 */
	public function _terminados()
	{
		$id  = $this->request->person->id;
		$person = Utils::getPerson($id);
		$this->setLevel();

		// get the most popular courses
		$courses = self::query("
		  SELECT *, right_answers / nullif(questions,0) * 100 as calification FROM (
				SELECT A.id, A.title, A.content, A.popularity, A.category, B.name AS 'professor',
				A.teacher, COALESCE((SELECT AVG(stars) FROM _escuela_stars WHERE course = A.id), 0) AS stars,
				(select count(*) from _escuela_chapter_viewed where A.id = _escuela_chapter_viewed.course and person_id = '$id') as viewed,
				(select count(*) from _escuela_question where A.id = _escuela_question.course) as questions,
				(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
				(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course AND _escuela_chapter.xtype = 'PRUEBA') as tests,
				(select count(*) from _escuela_answer where A.id = _escuela_answer.course) as answers,
				(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = '$id') as answers_choosen,
				(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course
					AND _escuela_answer_choosen.person_id = '$id'
					AND (SELECT count(*) as right_choose FROM _escuela_question WHERE _escuela_question.answer = _escuela_answer_choosen.answer) > 0) as right_answers
				FROM _escuela_course A
				JOIN _escuela_teacher B
				ON A.teacher = B.id
				WHERE A.active = 1
				) subq
				WHERE viewed >= (chapters - tests) and answers_choosen >= questions
				ORDER BY calification DESC;");

		$this->setFontFiles();

		if (empty($courses)) {
			$content = [
				"header"=>"¡Sin resultados!",
				"icon"=>"sentiment_very_dissatisfied",
				"text" => "Usted no tiene cursos terminados. Vaya al inicio y escoja un curso para empezar a estudiar.",
				"button" => ["href"=>"ESCUELA", "caption"=>"Ver cursos"]];

			$this->response->setLayout('escuela.ejs');
			$this->response->setTemplate("text.ejs", $content, [], $this->files);
			return;
		}

		$this->response->setLayout('escuela.ejs');
		$this->response->setTemplate("terminated.ejs", [
			"courses"   => is_array($courses) ? $courses : [],
			"profile"   => $person,
			"max_stars" => 5,
		], [], $this->files);
	}

	/**
	 * Return a resume of courses filtered by person_id and course id
	 *
	 * @param      $id
	 * @param null $course_id
	 *
	 * @return array|mixed
	 */
	private function getResume($id, $course_id = null)
	{
		$r = self::query("
			SELECT id, medal, title,
				(select count(*) from _escuela_chapter_viewed where A.id = _escuela_chapter_viewed.course and person_id = '$id') as viewed,
				(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
				(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course AND _escuela_chapter.xtype = 'PRUEBA') as tests,
				(select count(*) from _escuela_question where A.id = _escuela_question.course) as questions,
				(select count(*) from _escuela_answer where A.id = _escuela_answer.course) as answers,
				(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = '$id') as answers_choosen,
				(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course
					AND _escuela_answer_choosen.person_id = '$id'
					AND (SELECT right_choosen FROM _escuela_answer WHERE _escuela_answer.id = _escuela_answer_choosen.answer) = 1) as right_answers,
					(select MAX(_escuela_answer_choosen.date_choosen) FROM _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = '$id') as answer_date
			FROM _escuela_course A
			" . (is_null($course_id) ? "" : " WHERE id = $course_id ") . ";");

		return $r;
	}

	/**
	 * Return feedbacks
	 *
	 * @return array
	 */

	// TODO: metodo no se usa

	/*private function getFeedbacks()
	{
		$feedback = self::query("SELECT id, text, answers FROM _escuela_feedback;");
		foreach ($feedback as $k => $fb) {
			$fb->answers = explode(',', $fb->answers);

			$new_answers = [];
			foreach ($fb->answers as $ans) {
				$value   = $ans;
				$caption = trim(ucfirst(strtolower($ans)));
				if (strpos($ans, ":") !== false) {
					$arr     = explode(":", $ans);
					$value   = trim($arr[0]);
					$caption = trim($arr[1]);
				}

				$new_answers[] = ['value' => $value, 'caption' => $caption];
			}

			$feedback[$k]->answers = $new_answers;
		}

		return $feedback;
	}*/

	/**
	 * Get answer
	 *
	 * @param $answers
	 * @param $answer
	 *
	 * @return bool|int
	 */
	private function getAnswerValue($answers, $answer)
	{
		$answers = explode(",", $answers);

		$i = 0;
		foreach ($answers as $ans) {
			$ans = trim($ans);
			$i++;

			$value = $ans;

			if (strpos($ans, ":") !== false) {
				$arr   = explode(":", $ans);
				$value = trim($arr[0]);
			}

			if ($value == $answer) {
				return $i;
			}
		}

		return false;
	}

	/**
	 * Return previous and next chapter
	 *
	 * @param $chapter
	 *
	 * @return array
	 * @author kuma
	 *
	 */
	private function getBeforeAfter($chapter)
	{
		$before = false;
		$after  = false;

		$r = self::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder - 1) . ";");
		if (isset($r[0])) {
			$before = $r[0];
		}

		$r = self::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder + 1) . ";");
		if (isset($r[0])) {
			$after = $r[0];
		}

		return [
			'before' => $before,
			'after'  => $after,
		];
	}

	/**
	 * Get course
	 *
	 * @param integer $id
	 * @param string  $person_id
	 *
	 * @return object|bool
	 */
	private function getCourse($id, $person_id = '')
	{
		$res = self::query("	SELECT *,
				(SELECT name FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) AS teacher_name,
				(SELECT title FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) AS teacher_title,
				((SELECT count(*) FROM _escuela_stars WHERE _escuela_stars.person_id = (SELECT id FROM person WHERE person.id = '$person_id') AND _escuela_stars.course = _escuela_course.id) > 0) as rated
			FROM _escuela_course
			WHERE id= '$id'
			AND active=1", true, 'latin1');

		if (!isset($res[0])) {
			return false;
		}

		$course           = $res[0];
		$course->chapters = $this->getChapters($id, $person_id);

		$calification             = 0;
		$course->total_tests      = 0;
		$course->total_seen       = 0;
		$course->total_answered   = 0;
		$course->total_terminated = 0;
		$course->total_questions  = 0;
		$course->total_childs     = count($course->chapters);
		$course->total_right      = 0;
		$course->repeated         = false;

		foreach ($course->chapters as $chapter) {
			if ($chapter->seen) {
				$course->total_seen++;
			}
			if ($chapter->answered) {
				$course->total_answered++;
			}
			if ($chapter->terminated) {
				$course->total_terminated++;
			}
			if ($chapter->xtype == 'PRUEBA') {
				$course->total_tests++;
			}
			$course->total_right     += $chapter->total_right;
			$course->total_questions += $chapter->total_questions;
			$calification            += $chapter->calification;
		}

		$course->total_chapters = $course->total_childs - $course->total_tests;
		$course->terminated     = $course->total_terminated == $course->total_childs;

		$course->calification = 0;

		/*// 40% por leer
		if ($course->total_childs > 0) {
			$course->calification = $course->total_seen / $course->total_chapters * 40;
		}*/

		// 100% por responder bien
		if ($course->total_questions > 0) {
			$course->calification += $course->total_right / $course->total_questions * 100;
		}

		$course->calification = intval($course->calification);

		$course->progress = 0;
		if ($course->total_childs > 0) {
			$course->progress = number_format($course->total_terminated / $course->total_childs * 100, 2) * 1;
		}

		$course->all_chapters_finished = $course->total_chapters == $course->total_seen;

		return $course;
	}

	/**
	 * Return a list of chapter's images paths
	 *
	 * @param $chapter
	 *
	 * @return array
	 * @internal param int $chapter_id
	 *
	 */
	private function getChapterImages($chapter)
	{
		// get course and content
		$chapterText = self::query("SELECT content, course FROM _escuela_chapter WHERE id=$chapter");
		$content     = $chapterText[0]->content;

		$tidy    = new tidy();
		$content = $tidy->repairString($content, [
			'output-xhtml' => true,
		], 'utf8');

		$course = $chapterText[0]->course;

		// get all images from the content
		$dom = new DOMDocument();
		$dom->loadHTML($content);
		$imgs = $dom->getElementsByTagName('img');

		// get path to root folder
		$di      = FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get full path to the image
		$images = [];
		/** @var \DOMElement $img */
		foreach ($imgs as $img) {
			$src               = $img->getAttribute('src');
			$filename          = str_replace("cid:", "", $src);
			$images[$filename] = "/var/www/shared/public/courses/$course/$chapter/$filename";
		}

		return $images;
	}

	/**
	 * Get chapter entity
	 *
	 * @param integer $id
	 *
	 * @param string  $person_id
	 * @param string  $answer_order
	 *
	 * @return object
	 */
	private function getChapter($id, $person_id = '', $answer_order = 'rand()')
	{
		$chapter = false;

		$r = self::query("SELECT * FROM _escuela_chapter WHERE id = '$id';", true, 'latin1');

		if (isset($r[0])) {
			$chapter = $r[0];
		}

		$chapter->questions = $this->getChapterQuestions($id, $person_id, $answer_order);

		$total_questions = count($chapter->questions);
		$total_right     = 0;

		foreach ($chapter->questions as $i => $q) {
			if ($q->is_right) {
				$total_right++;
			}
		}

		$chapter->total_right     = $total_right;
		$chapter->total_questions = $total_questions;
		$chapter->calification    = 0;

		if ($total_questions > 0) {
			$chapter->calification = intval($total_right / $total_questions * 100);
		}

		$chapter->seen       = $this->isChapterSeen($person_id, $id);
		$chapter->answered   = $this->isTestTerminated($person_id, $id) && $chapter->xtype == 'PRUEBA';
		$chapter->terminated = $chapter->answered || $chapter->seen;

		$chapter->content = $this->clearHtml($chapter->content);

		return $chapter;
	}

	/**
	 * Get list of chapters
	 *
	 * @param integer $course
	 * @param string  $person_id
	 * @param bool    $terminated
	 *
	 * @return array
	 */
	private function getChapters($course, $person_id = '', $terminated = null)
	{
		// get chapters
		$r = self::query("SELECT id FROM _escuela_chapter WHERE course = '$course' ORDER BY xorder;");

		$chapters = [];
		if ($r) {
			foreach ($r as $row) {
				$c = $this->getChapter($row->id, $person_id);
				if ($c->terminated == $terminated || is_null($terminated)) {
					$chapters[] = $c;
				}
			}
		}

		return $chapters;
	}

	private function getChapterQuestions($test_id, $person_id = '', $answer_order = 'rand()')
	{
		$questions = [];
		$rows      = self::query("SELECT id FROM _escuela_question WHERE chapter = '$test_id' ORDER BY xorder;", true, 'latin1');
		if (!is_array($questions)) {
			$questions = [];
		}

		foreach ($rows as $i => $q) {
			$questions[] = $this->getQuestion($q->id, $person_id, $answer_order);
		}

		return $questions;
	}

	/**
	 * Return question object
	 *
	 * @param        $question_id
	 * @param string $person_id
	 * @param string $answer_order
	 *
	 * @return bool
	 */
	private function getQuestion($question_id, $person_id = '', $answer_order = 'rand()')
	{
		$row = self::query("SELECT * FROM _escuela_question WHERE id = '$question_id';", true, 'latin1');
		if (isset($row[0])) {
			$q             = $row[0];
			$q->answers    = $this->getAnswers($question_id, $answer_order);
			$t             = $this->isQuestionTerminated($person_id, $question_id);
			$q->terminated = $t;

			$q->answer_choosen = -1;
			$a                 = self::query("SELECT answer FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND question = '$question_id'");
			if (isset($a[0])) {
				$q->answer_choosen = intval($a[0]->answer);
			}

			$q->is_right = $q->answer_choosen == $q->answer;

			return $q;
		}

		return false;
	}

	/**
	 * Return answers of a question
	 *
	 * @param        $question_id
	 * @param string $orderby
	 *
	 * @return array
	 */
	private function getAnswers($question_id, $orderby = 'rand()')
	{
		$answers = self::query("SELECT * FROM _escuela_answer WHERE question = '{$question_id}' ORDER BY $orderby;");
		if (!is_array($answers)) {
			$answers = [];
		}

		return $answers;
	}

	/**
	 * Return the total of chapter's questions
	 *
	 * @param $chapter_id
	 *
	 * @return int
	 */
	private function getTotalQuestionsOf($chapter_id)
	{
		$r = self::query("SELECT count(id) as t FROm _escuela_question WHERE chapter = '$chapter_id';");

		return intval($r[0]->t);
	}

	/**
	 * Return the total of chapter's responses
	 *
	 * @param $person_id
	 * @param $chapter_id
	 *
	 * @return int
	 */
	private function getTotalResponsesOf($person_id, $chapter_id)
	{
		$r = self::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND chapter = '$chapter_id';");

		return intval($r[0]->t);
	}

	/**
	 * Check if user finish the test
	 *
	 * @param string  $person_id
	 * @param integer $test_id
	 *
	 * @return boolean
	 */
	private function isTestTerminated($person_id, $test_id)
	{
		$total_questions = $this->getTotalQuestionsOf($test_id);
		$total_responses = $this->getTotalResponsesOf($person_id, $test_id);

		return $total_questions == $total_responses;
	}

	/**
	 * Return TRUE if a chapter was seen by the user
	 *
	 * @param $person_id
	 * @param $chapter_id
	 *
	 * @return bool
	 */
	private function isChapterSeen($person_id, $chapter_id)
	{
		$r = self::query("SELECT count(person_id) as t FROM _escuela_chapter_viewed WHERE person_id ='$person_id' AND chapter = '$chapter_id';");

		return $r[0]->t * 1 > 0;
	}

	/**
	 * Return TRUE if a question is terminated by the user
	 *
	 * @param $person_id
	 * @param $question_id
	 *
	 * @return bool
	 */
	private function isQuestionTerminated($person_id, $question_id)
	{
		$r = self::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND question = '$question_id';");

		return intval($r[0]->t) > 0;
	}

	private function getTotalCompletedCourses($person_id)
	{
		$r = self::query("
			select count(*) as t from (
				select course,
						count(*) as total,
						(select count(*)
						from _escuela_chapter_viewed
						where _escuela_chapter.course = _escuela_chapter_viewed.course
						and person_id = '$person_id') as viewed
				from  _escuela_chapter
				group by course
    		) subq
    		where subq.total = subq.viewed");

		return intval($r[0]->t);
	}

	private function getTeachers()
	{
		$r = self::query('SELECT * FROM _escuela_teacher');
		if (!is_array($r)) {
			return [];
		}

		return $r;
	}

	/**
	 * Helper for clear HTML code
	 *
	 * @param $html
	 *
	 * @return mixed
	 */
	private function clearHtml($html)
	{
		$html = str_replace('&nbsp;', ' ', $html);

		do {
			$tmp  = $html;
			$html = preg_replace('#<([^ >]+)[^>]*>[[:space:]]*</\1>#', '', $html);
		} while ($html !== $tmp);

		return $html;
	}

	/**
	 * Set font files
	 */
	private function setFontFiles()
	{
		$this->files = [
			Utils::getPathToService("escuela") . "/resources/Roboto-Bold.ttf",
			Utils::getPathToService("escuela") . "/resources/Roboto-Regular.ttf",
		];
	}
}

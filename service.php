<?php

use Apretaste\Challenges;
use Apretaste\Person;
use Framework\Alert;
use Framework\Database;
use Apretaste\Request;
use Apretaste\Response;
use Framework\Images;
use Apretaste\Level;

class Service
{
	/** @var array */
	private $files = [];

	/**
	 * Database query
	 *
	 * @param $sql
	 * @param string $set_charset
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function query($sql, $set_charset = 'utf8mb4')
	{
		return Database::query($sql, true, $set_charset);
	}


	/**
	 * Main function
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Alert
	 * @author salvipascual
	 */
	public function _main(Request $request, Response &$response)
	{
		$person_id = $request->person->id;
		$person = Person::find($person_id);
		$this->setLevel($request);

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
			$course = $this->getCourse($c->id, $request->person->id);
			$c->progress = $course->progress;
			$c->title = htmlspecialchars($c->title);
			$c->content = htmlspecialchars($c->content);
			$c->professor = htmlspecialchars($c->professor);
			$c->author = $c->professor;
			$c->stars = (int)$c->stars;
			$courses[$k] = $c;
		}

		$level = 'PRINCIPIANTE';
		$r = self::query("SELECT level FROM _escuela_profile WHERE person_id = '{$request->person->id}'");
		if (isset($r[0])) {
			$level = $r[0]->level;
		}

		$this->setFontFiles();

		// create content for the view
		$content = [
			'max_stars' => 5,
			'courses' => $courses,
			'name' => $person->firstName ? $person->firstName : '',
			'level' => $level,
			'completed' => $this->getTotalCompletedCourses($person_id)
		];

		// setup response
		$response->setCache(60);
		$response->setLayout('escuela.ejs');
		$response->setTemplate('home.ejs', $content, [], $this->files);
	}

	/**
	 * Buscar cursos
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Alert
	 */
	public function _buscar(Request $request, Response &$response)
	{
		$where = '';
		$data = null;
		if (isset($request->input->data->query)) {
			$data = $request->input->data->query;
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
			(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = '{$request->person->id}') as answers_choosen
			FROM _escuela_course A
			JOIN _escuela_teacher B
			ON A.teacher = B.id
			WHERE A.active = 1) subq
			WHERE 1 $where ORDER BY popularity DESC LIMIT 10");
		}

		if (!is_array($courses)) {
			$courses = [];
		}

		$noResults = empty($courses);
		$noSearch = empty(trim($where));

		// remove estrange chars
		foreach ($courses as $k => $c) {
			$course = $this->getCourse($c->id, $request->person->id);
			$c->progress = $course->progress;
			$c->title = htmlspecialchars($c->title);
			$c->content = htmlspecialchars($c->content);
			$c->professor = htmlspecialchars($c->professor);
			$c->author = $c->professor;
			$c->stars = intval($c->stars);
			$courses[$k] = $c;
		}

		$this->setFontFiles();

		// display the course
		$response->setLayout('escuela.ejs');
		$response->setTemplate('search.ejs', [
			'categories' => [
				'SOCIEDAD' => 'Sociedad',
				'NEGOCIOS' => 'Negocios',
				'MEDICINA' => 'Medicina',
				'INFORMATICA' => html_entity_decode('Inform&aacute;tica'),
				'INGENIERIA' => html_entity_decode('Ingenier&iacute;a'),
				'LETRAS' => 'Letras',
				'ARTES' => 'Artes',
				'FILOSOFIA' => html_entity_decode('Filosof&iacute;a'),
				'SALUD' => 'Salud',
				'POLITICA' => html_entity_decode('Pol&iacute;tica'),
				'TECNICA' => html_entity_decode('T&eacute;cnica'),
				'OTRO' => 'Otros',
			],
			'authors' => $this->getTeachers(),
			'courses' => $courses,
			'data' => $data,
			'noResults' => $noResults,
			'noSearch' => $noSearch,
			'max_stars' => 5,
		], [], $this->files);

		$response->setCache('month');
	}

	/**
	 * Retrieve a course
	 *
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Alert
	 * @example ESCUELA CURSO 2
	 * @author  kuma
	 */
	public function _curso(Request $request, Response &$response)
	{
		// get the course details
		$id = intval($request->input->data->query);
		$course = $this->getCourse($id, $request->person->id);

		// if course cannot be found
		if (empty($course)) {
			$response->setLayout('escuela.ejs');
			$response->setTemplate('text.ejs', [
				'title' => 'Curso no encontrado',
				'body' => 'No encontramos el curso que usted pidio',
			], [], $this->files);

			return;
		}

		$this->setFontFiles();

		// display the course
		$response->setLayout('escuela.ejs');
		$response->setTemplate('course.ejs', ['course' => $course], [], $this->files);
	}

	/**
	 * Subservice CAPITULO
	 *
	 *
	 * @param  Request  $request
	 * @param  Response  $response
	 *
	 * @return \Apretaste\Response
	 * @throws Alert
	 * @example ESCUELA CAPITULO 3
	 * @author  kuma
	 */
	public function _capitulo(Request $request, Response &$response)
	{
		$id = (int)$request->input->data->query;
		$chapter = $this->getChapter($id, $request->person->id);

		$this->setFontFiles();

		if (empty($chapter)) {
			$response->setLayout('escuela.ejs');
			return $response->setTemplate('text.ejs', ['title' => 'Lo Sentimos', 'body' => 'Capítulo no encontrado'], [], $this->files);
		}

		$beforeAfter = $this->getBeforeAfter($chapter);
		$images = $this->getChapterImages($id);
		$chapter->content = Parsedown::instance()->parse($chapter->content);
		$chapter->content = str_replace('/school/image?guid=', '{{APP_IMAGE_PATH}}/', $chapter->content);
		$course = $this->getCourse($chapter->course, $request->person->id);
		$terminated = $course->terminated;

		// Log the visit to this chapter
		if ($chapter->xtype === 'CAPITULO') {
			self::query("INSERT IGNORE INTO _escuela_chapter_viewed (person_id, email, chapter, course) VALUES ('{$request->person->id}','{$request->person->email}', '{$id}', '{$chapter->course}');");
		}

		// get the code inside the <body> tag
		if (stripos($chapter->content, '<body>') !== false) {
			$ini = strpos($chapter->content, '<body>') + 6;
			$end = strpos($chapter->content, '</body>');
			$chapter->content = substr($chapter->content, $ini, $end - $ini);
		}

		// check if the course is terminated
		$course = $this->getCourse($chapter->course, $request->person->id);

		if (!$terminated && $course->terminated) { // si el status terminated del curso cambio de false a true
			Challenges::complete('complete-course', $request->person->id);

			// add the experience if profile is completed
			Level::setExperience('FINISH_COURSE', $request->person->id);
		}

		// create content for the view
		$content = [
			'chapter' => $chapter,
			'course' => $course,
			'before' => $beforeAfter['before'],
			'after' => $beforeAfter['after']
		];

		// send response to the view
		$response->setLayout('escuela.ejs');
		$response->setTemplate('chapter.ejs', $content, $images, $this->files);
	}

	/**
	 * Subservice PRUEBA
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Alert
	 * @example ESCUELA PRUEBA 2
	 */
	public function _prueba(Request $request, Response &$response)
	{
		$this->_capitulo($request, $response);
	}

	/**
	 * Records the answer for a question and resturns an empty response
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Exception
	 */
	public function _responder(Request $request, Response &$response)
	{
		// pull the answer selected
		$answers = $request->input->data->answers;
		// check if the course is terminated


		foreach ($answers as $id) {
			$res = self::query("SELECT * FROM _escuela_answer WHERE id=$id");

			// do not let pass invalid answers
			if (empty($res)) {
				continue;
			} else {
				$answer = $res[0];
			}

			$course = $this->getCourse($answer->course, $request->person->id);
			$terminated = $course->terminated;

			// save the answer in the database

			self::query("
			INSERT IGNORE INTO _escuela_answer_choosen (person_id, email, answer, chapter, question, course)
			VALUES ('{$request->person->id}','{$request->person->email}','$id', '{$answer->chapter}', '{$answer->question}', '{$answer->course}')");

			$course = $this->getCourse($answer->course, $request->person->id);

			if (!$terminated && $course->terminated) { // si el status terminated del curso cambio de false a true
				Challenges::complete('complete-course', $request->person->id);

				// add the experience if profile is completed
				Level::setExperience('FINISH_COURSE', $request->person->id);
			}

			$this->setLevel($request);
		}
	}

	/**
	 * Set level
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function setLevel(Request $request)
	{
		$resume = $this->getResume($request->person->id);
		$total = 0;
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
		self::query("UPDATE _escuela_profile SET level = '$level' WHERE person_id = '{$request->person->id}';");
	}

	/**
	 * Rate course
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Exception
	 */
	public function _calificar(Request $request, Response &$response)
	{
		$course_id = $request->input->data->query->course;
		$stars = $request->input->data->query->stars;
		$stars = $stars > 5 ? 5 : $stars;

		self::query("INSERT IGNORE INTO _escuela_stars (course, person_id, stars) VALUES ('$course_id', '{$request->person->id}', '$stars');");
		self::query("UPDATE _escuela_stars SET stars = $stars WHERE course = $course_id AND person_id = {$request->person->id};");
	}

	/**
	 * Clear string
	 *
	 * @param String $name
	 *
	 * @return String
	 */
	public static function clearStr($name, $extra_chars = '', $chars = 'abcdefghijklmnopqrstuvwxyz')
	{
		$l = strlen($name);
		$newname = '';
		$chars .= $extra_chars;

		for ($i = 0; $i < $l; $i++) {
			$ch = $name[$i];
			if (stripos($chars, $ch) !== false) {
				$newname .= $ch;
			}
		}

		return $newname;
	}

	/**
	 * Subservice OPINAR
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Exception
	 */
	public function _opinar(Request $request, Response &$response)
	{
		// expecting: course_id feedback_id answer
		$q = trim($request->input->data->query);

		self::clearStr($q);
		$feed = explode(' ', $q);

		if (!isset($feed[0]) || !isset($feed[1]) || !isset($feed[2])) {
			return;
		}

		$course_id = (int)$feed[0];
		$feedback_id = (int)$feed[1];
		$answer = strtolower(trim(($feed[2])));

		$course = $this->getCourse($course_id, $request->person->id);

		if ($course !== false) {
			$feedback = self::query("SELECT id, text, answers FROM _escuela_feedback WHERE id = $feedback_id;");
			if (isset($feedback[0])) {
				$feedback = $feedback[0];
				$answers = $feedback->answers;
				$feedback_where = " person_id = '{$request->person->id}' AND feedback = $feedback_id AND course = $course_id;";

				// get last answer, and decrease popularity of the course
				$last_answer = false;
				$r = self::query("SELECT answer FROM _escuela_feedback_received WHERE $feedback_where;");
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
					self::query("INSERT INTO _escuela_feedback_received (feedback, course, person_id, email, answer) VALUES ($feedback_id, $course_id, '{$request->person->id}', '{$request->person->email}', '$answer');");
					self::query("UPDATE _escuela_course SET popularity = popularity + $popularity WHERE id = $course_id;");
				}
			}
		}
	}

	/**
	 * Repeats a test for a course
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Exception
	 * @author kuma
	 */
	public function _repetir(Request $request, Response &$response)
	{
		// remove the previous answers
		self::query("DELETE FROM _escuela_chapter_viewed WHERE course='{$request->input->data->query}' AND person_id='{$request->person->id}'");
		self::query("DELETE FROM _escuela_answer_choosen WHERE course='{$request->input->data->query}' AND person_id='{$request->person->id}'");

		// load the test again
		$this->_curso($request, $response);

		// change response content
		//TODO: improve this feature in the core
		$data = @json_decode($response->json);

		if (is_array($data)) {
			$data = (object)$data;
		}

		if (!is_object($data)) {
			if (isset($data->course)) {
				$data->course->repeated = true;
				$response->json = json_encode($data, JSON_UNESCAPED_UNICODE);
			}
		}
	}

	/**
	 * Perfil de escuela
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Alert
	 */
	public function _perfil(Request $request, Response &$response)
	{

		// save profile
		if (isset($request->input->data->save)) {
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
			foreach ($request->input->data->query as $key => $value) {
				if ($key === 'date_of_birth') {
					$value = DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d');
				}

				if (in_array($key, $fields, true)) {
					$pieces[] = "$key='$value'";
				}
			}

			// save changes on the database
			if (!empty($pieces)) {
				self::query('UPDATE person SET ' . implode(',', $pieces) . " WHERE id={$request->person->id}");
			}


			self::query("UPDATE person SET year_of_birth = YEAR(date_of_birth), month_of_birth = MONTH(date_of_birth), day_of_birth = DAY(date_of_birth) WHERE id={$request->person->id}");

			return;
		}

		// show profile
		$resume = $this->getResume($request->person->id);

		$profile = Person::find($request->person->id);

		if ($profile)
		{
			$person = Database::query("SELECT date_of_birth FROM person WHERE id = {$request->person->id}")[0];
			$profile->date_of_birth = $person->date_of_birth;
		}

		$profile->level = 'PRINCIPIANTE';
		$r = self::query("SELECT * FROM _escuela_profile WHERE person_id = '{$request->person->id}'");
		if (!isset($r[0])) {
			self::query("INSERT INTO _escuela_profile (person_id, level) VALUES ('{$request->person->id}','PRINCIPIANTE');");
		} else {
			$profile->level = $r[0]->level;
		}

		$r = self::query("SELECT COLUMN_TYPE AS result
				FROM information_schema.COLUMNS
				WHERE TABLE_NAME = '_escuela_profile'
							AND COLUMN_NAME = 'level';");

		$this->setFontFiles();

		$levels = explode(',', str_replace(["'", 'enum(', ')'], '', $r[0]->result));
		$response->setLayout('escuela.ejs');
		$response->setTemplate('profile.ejs', [
			'resume' => $resume,
			'profile' => $profile,
			'levels' => $levels,
		], [], $this->files);
	}

	/**
	 * Cursos terminados
	 *
	 */
	public function _terminados(Request $request, Response &$response)
	{
		$id = $request->person->id;
		$person = Person::find($id);
		$this->setLevel($request);

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
				'header' => '¡Sin resultados!',
				'icon' => 'sentiment_very_dissatisfied',
				'text' => 'Usted no tiene cursos terminados. Vaya al inicio y escoja un curso para empezar a estudiar.',
				'button' => ['href' => 'ESCUELA', 'caption' => 'Ver cursos']];

			$response->setLayout('escuela.ejs');
			$response->setTemplate('text.ejs', $content, [], $this->files);
			return;
		}

		$response->setLayout('escuela.ejs');
		$response->setTemplate('terminated.ejs', [
			'courses' => is_array($courses) ? $courses : [],
			'profile' => $person,
			'max_stars' => 5,
		], [], $this->files);
	}

	/**
	 * Return a resume of courses filtered by person_id and course id
	 *
	 * @param $id
	 * @param null $course_id
	 *
	 * @return array|mixed
	 * @throws Exception
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
			" . (is_null($course_id) ? '' : " WHERE id = $course_id ") . ';');

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
		$answers = explode(',', $answers);

		$i = 0;
		foreach ($answers as $ans) {
			$ans = trim($ans);
			$i++;

			$value = $ans;

			if (strpos($ans, ':') !== false) {
				$arr = explode(':', $ans);
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
	 * @throws Exception
	 * @author kuma
	 */
	private function getBeforeAfter($chapter)
	{
		$before = false;
		$after = false;

		$r = self::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder - 1) . ';');
		if (isset($r[0])) {
			$before = $r[0];
		}

		$r = self::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder + 1) . ';');
		if (isset($r[0])) {
			$after = $r[0];
		}

		return [
			'before' => $before,
			'after' => $after,
		];
	}

	/**
	 * Get course
	 *
	 * @param integer $id
	 * @param string $person_id
	 *
	 * @return object|bool
	 * @throws Exception
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

		$course = $res[0];
		$course->chapters = $this->getChapters($id, $person_id);

		$calification = 0;
		$course->total_tests = 0;
		$course->total_seen = 0;
		$course->total_answered = 0;
		$course->total_terminated = 0;
		$course->total_questions = 0;
		$course->total_childs = count($course->chapters);
		$course->total_right = 0;
		$course->repeated = false;

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
			if ($chapter->xtype === 'PRUEBA') {
				$course->total_tests++;
			}
			$course->total_right += $chapter->total_right;
			$course->total_questions += $chapter->total_questions;
			$calification += $chapter->calification;
		}

		$course->total_chapters = $course->total_childs - $course->total_tests;
		$course->terminated = $course->total_terminated == $course->total_childs;

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
	 * @throws Exception
	 * @internal param int $chapter_id
	 */
	private function getChapterImages($chapter)
	{
		// get course and content
		$chapterText = self::query("SELECT content, course FROM _escuela_chapter WHERE id=$chapter");
		$content = $chapterText[0]->content;
		$images = [];

		$p = -1;
		do {
			$p = stripos($content, '/school/image?guid=', $p + 1);
			if ($p !== false) {
				$p1 = strpos($content, ')', $p);
				$guid = substr($content, $p + 19, $p1 - $p - 19);
				$images[$guid] = SHARED_PUBLIC_PATH . "/courses/$guid";
			}
		} while ($p !== false);

		return $images;
	}

	/**
	 * Get chapter entity
	 *
	 * @param integer $id
	 *
	 * @param string $person_id
	 * @param string $answer_order
	 *
	 * @return object
	 * @throws Exception
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
		$total_right = 0;

		foreach ($chapter->questions as $i => $q) {
			if ($q->is_right) {
				$total_right++;
			}
		}

		$chapter->total_right = $total_right;
		$chapter->total_questions = $total_questions;
		$chapter->calification = 0;

		if ($total_questions > 0) {
			$chapter->calification = intval($total_right / $total_questions * 100);
		}

		$chapter->seen = $this->isChapterSeen($person_id, $id);
		$chapter->answered = $this->isTestTerminated($person_id, $id) && $chapter->xtype == 'PRUEBA';
		$chapter->terminated = $chapter->answered || $chapter->seen;

		$chapter->content = $this->clearHtml($chapter->content);

		return $chapter;
	}

	/**
	 * Get list of chapters
	 *
	 * @param integer $course
	 * @param string $person_id
	 * @param bool $terminated
	 *
	 * @return array
	 * @throws Exception
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

	/**
	 * Get questions of chapter
	 *
	 * @param $test_id
	 * @param string $person_id
	 * @param string $answer_order
	 *
	 * @return array
	 * @throws Exception
	 */
	private function getChapterQuestions($test_id, $person_id = '', $answer_order = 'rand()')
	{
		$questions = [];
		$rows = self::query("SELECT id FROM _escuela_question WHERE chapter = '$test_id' ORDER BY xorder;", true, 'latin1');
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
	 * @param $question_id
	 * @param string $person_id
	 * @param string $answer_order
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function getQuestion($question_id, $person_id = '', $answer_order = 'rand()')
	{
		$row = self::query("SELECT * FROM _escuela_question WHERE id = '$question_id';", true, 'latin1');
		if (isset($row[0])) {
			$q = $row[0];
			$q->answers = $this->getAnswers($question_id, $answer_order);
			$t = $this->isQuestionTerminated($person_id, $question_id);
			$q->terminated = $t;

			$q->answer_choosen = -1;
			$a = self::query("SELECT answer FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND question = '$question_id'");
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
	 * @param $question_id
	 * @param string $orderby
	 *
	 * @return array
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
	 */
	private function getTotalResponsesOf($person_id, $chapter_id)
	{
		$r = self::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND chapter = '$chapter_id';");

		return intval($r[0]->t);
	}

	/**
	 * Check if user finish the test
	 *
	 * @param string $person_id
	 * @param integer $test_id
	 *
	 * @return boolean
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
	 */
	private function isQuestionTerminated($person_id, $question_id)
	{
		$r = self::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND question = '$question_id';");

		return intval($r[0]->t) > 0;
	}

	/**
	 * Get total of completed courses
	 *
	 * @param $person_id
	 *
	 * @return int
	 * @throws Exception
	 */
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

	/**
	 * Get list of teachers
	 *
	 * @return array|mixed
	 * @throws Exception
	 */
	private function getTeachers()
	{
		$r = self::query('SELECT * FROM _escuela_teacher WHERE (SELECT COUNT(*) FROM _escuela_course WHERE _escuela_course.active = 1 AND _escuela_course.teacher = _escuela_teacher.id) > 0');
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
			$tmp = $html;
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
			__DIR__ . '/resources/Roboto-Bold.ttf',
			__DIR__ . '/resources/Roboto-Regular.ttf',
		];
	}

	/**
	 * EXPERIMENTAL
	 *
	 * @param  \Apretaste\Request  $request
	 * @param  \Apretaste\Response  $response
	 *
	 * @throws \Framework\Alert
	 */
	public function _example(Request $request, Response $response) {
		$response->setTemplate('chapter2.ejs', [
		  'chapter' => (object) [
		    'title' => 'Ejemplo de capitulo',
			'content' => [
			  (object) [
			    'template' => '<p id="<%= id %>"><%= text %></p>',
				'script' => '$("#<%= id %>").click(function() {alert(1);});',
				'style' => '#<%= id %> {background: red;}',
				'data' => (object)[
				  'id'=> 'parrafo1',
				  'text'=>'tremendo texto'
				]
			  ]
			]
		  ]
		]);
	}
}

<?php

use Apretaste\Level;
use Apretaste\Alert;
use Apretaste\Person;
use Apretaste\Images;
use Apretaste\Config;
use Apretaste\Bucket;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Tutorial;
use Apretaste\Database;
use Apretaste\Challenges;
use Apretaste\GoogleAnalytics;

class Service
{
	/** @var array */
	private $files = [];

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

		$where = '';
		$data = [];
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

		$courses = Database::query("
			SELECT * FROM (
				SELECT A.id, A.title, A.content, A.popularity, A.category, B.name AS professor, A.teacher, A.stars,
				(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
				(select count(*) from _escuela_chapter_viewed where A.id = _escuela_chapter_viewed.course and person_id = {$request->person->id}) as viewed
				FROM _escuela_course A JOIN _escuela_teacher B ON A.teacher = B.id
				WHERE A.active = 1) subq
			WHERE 1 $where ORDER BY popularity DESC");

		// remove extrange chars
		foreach ($courses as $k => $c) {
			// $course = $this->getCourse($c->id, $request->person->id);
			$c->progress = (int) ($c->viewed / $c->chapters * 100);
			$c->title = htmlspecialchars($c->title);
			$c->content = htmlspecialchars($c->content);
			$c->professor = htmlspecialchars($c->professor);
			$c->author = $c->professor;
			$c->stars = (int) $c->stars;
			$courses[$k] = $c;
		}

		$level = 'PRINCIPIANTE';
		$r = Database::query("SELECT level FROM _escuela_profile WHERE person_id = '{$request->person->id}'");
		if (isset($r[0])) {
			$level = $r[0]->level;
		}

		//$this->setFontFiles();

		// create content for the view
		$content = [
			'courses' => $courses,
			'name' => $person->firstName ? $person->firstName : '',
			'level' => $level,
			'completed' => $this->getTotalCompletedCourses($person_id),

			'categories' => [
				'ALL' => 'Todas',
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
			'data' => $data,
			'max_stars' => 5,
			'title' => 'Cursos'
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
			$courses = Database::query("
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

		if ($course->total_seen == 0) {
			Challenges::complete('start-school', $request->person->id);
		}

		//$this->setFontFiles();

		// display the course
		//$response->setLayout('escuela.ejs');
		$response->setTemplate('course.ejs', ['course' => $course], [], $this->files);
	}

	/**
	 * Subservice CAPITULO
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return \Apretaste\Response
	 * @example ESCUELA CAPITULO 3
	 * @author kuma
	 */
	public function _capitulo(Request $request, Response &$response)
	{
		$id = (int) $request->input->data->query;
		$chapter = $this->getChapter($id, $request->person->id);

		//$this->setFontFiles();

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

		$r = Database::queryFirst("
			select (select count(*) as viewed from apretaste._escuela_chapter_viewed WHERE person_id = {$request->person->id} and course = '{$chapter->course}') as viewed,
			(select count(id) as total from apretaste._escuela_chapter WHERE course = '{$chapter->course}' and xtype = 'CAPITULO') as total;");

		if (($course->total_seen ?? 0) === 0) {
			GoogleAnalytics::event('education_course_started', $course->id);
		}

		$totalChapters = (int) $r->total;
		$viewedChapters = (int) $r->viewed;

		// Log the visit to this chapter
		if ($chapter->xtype === 'CAPITULO') {
			Database::query("
				INSERT IGNORE INTO _escuela_chapter_viewed (person_id, email, chapter, course) 
				VALUES ('{$request->person->id}','{$request->person->email}', '{$id}', '{$chapter->course}');");
		} else {
			if ($viewedChapters < $totalChapters) {
				return $response->setTemplate('text.ejs', [
					'header' => 'Termina de estudiar',
					'icon' => 'sentiment_very_dissatisfied',
					'text' => 'Le faltan por leer '.($totalChapters - $viewedChapters).' cap&iacute;tulos. Cuando termines de leer todos los cap&iacute;tulos es que podr&aacute;s hacer el examen.',
					'button' => ['href' => 'ESCUELA CURSO', 'query' => $chapter->course, 'caption' => 'Volver']]);
			}
		}

		// get the code inside the <body> tag
		if (stripos($chapter->content, '<body>') !== false) {
			$ini = strpos($chapter->content, '<body>') + 6;
			$end = strpos($chapter->content, '</body>');
			$chapter->content = substr($chapter->content, $ini, $end - $ini);
		}

		// check if the course is terminated
		$course = $this->getCourse($chapter->course, $request->person->id);

		// create content for the view
		$content = [
			'totalChapters' => $totalChapters,
			'viewedChapters' => $viewedChapters,
			'chapter' => $chapter,
			'course' => $course,
			'before' => $beforeAfter['before'],
			'after' => $beforeAfter['after']
		];

		// send response to the view
		//$response->setLayout('escuela.ejs');
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

		if (count($answers) < 1)
			return;

		// check completed course
		$res = Database::queryFirst("SELECT * FROM _escuela_answer WHERE id = {$answers[0]}");
		if (empty($res))
			return;

		$cnt = Database::queryFirst("select count(*) as cnt from _escuela_completed_course where person = {$request->person->id} and course = {$res->course}")->cnt;

		if  ($cnt > 0) {
			return $response->setTemplate('text.ejs', [
				'header' => 'Aprobado',
				'icon' => 'sentiment_very_satisfied',
				'text' => 'Este curso ya lo has aprobado anteriormente.',
				'subtext' => 'Ve a otros cursos para seguir aprendiendo',
				'showRate' => true,
				'courseId' => $res->course,
				'button' => ['href' => 'ESCUELA', 'query' => $res->course, 'caption' => 'Cursos']]);
		}

		// check if the course is terminated
		$affectedRows = 0;
		$course = null;
		foreach ($answers as $id) {
			$res = Database::query("SELECT * FROM _escuela_answer WHERE id = $id");

			// do not let pass invalid answers
			if (empty($res)) {
				continue;
			} else {
				$answer = $res[0];
			}

			if ($course === null) {
				$course = $this->getCourse($answer->course, $request->person->id);
			}

			// save the answer in the database
			// INSERT IGNORE: if course already completed, affected rows will be 0
			Database::query("
				INSERT IGNORE INTO _escuela_answer_choosen (person_id, email, answer, chapter, question, course)
				VALUES ('{$request->person->id}','{$request->person->email}','$id', '{$answer->chapter}', '{$answer->question}', '{$answer->course}')");

			$affectedRows += Database::getAffectedRows();
		}

		if ($course !== null) {
			GoogleAnalytics::event('education_course_finished', $course->id);
			$courseAfter = $this->getCourse($course->id, $request->person->id);

			if ($courseAfter->calification < 80) {
				$request->input->data->query = $course->id;

				$this->_repetir($request, $response);

				GoogleAnalytics::event('education_test_failed', $course->id);

				return $response->setTemplate('text.ejs', [
					'header' => 'Desaprobado',
					'icon' => 'sentiment_very_dissatisfied',
					'text' => 'No has podido resolver el examen satisfactoriamente. Obtuviste '.$courseAfter->calification.' puntos y necesitas al menos 80. Ahora podr&aacute; repasar el curso completo y vover a hacer el examen.',
					'button' => ['href' => 'ESCUELA CURSO', 'query' => $course->id, 'caption' => 'Ir al curso']
				]);
			}

			// complete challenge
			Challenges::complete('complete-course', $request->person->id);

			// if it is the tutorial course ...
			if ($course->id === Config::pick('challenges')['tutorial_id']) {
				// complete challenge
				Challenges::complete('app-tutorial', $request->person->id);

				// complete tutorial
				Tutorial::complete($request->person->id, 'read_tutorial');
			}

			// submit to Google Analytics 
			GoogleAnalytics::event('education_test_passed', $course->id);

			// add the experience if profile is completed
			Level::setExperience('FINISH_COURSE', $request->person->id);

			// marca el curso como terminado
			Database::query("INSERT IGNORE INTO _escuela_completed_course (person, course, calification) VALUES ({$request->person->id}, {$course->id}, {$courseAfter->calification});");

			// TODO: como informar el nivel actual en el mensaje de felicitacion?
			$this->setLevel($request);

			// send data to the view
			return $response->setTemplate('text.ejs', [
				'header' => 'Aprobado',
				'icon' => 'sentiment_very_satisfied',
				'text' => 'Felicidades! Has podido resolver el examen satisfactoriamente. ',
				'subtext' => 'Aprobado con '.$courseAfter->calification.' puntos. ',
				'showRate' => true,
				'courseId' => $course->id,
				'button' => ['href' => 'ESCUELA', 'query' => $course->id, 'caption' => 'Cursos']
			]);
		}

		return null;
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
		Database::query("UPDATE _escuela_profile SET level = '$level' WHERE person_id = '{$request->person->id}';");
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

		GoogleAnalytics::event('education_course_reviewed', $stars);

		Database::query("INSERT IGNORE INTO _escuela_stars (course, person_id, stars) VALUES ('$course_id', '{$request->person->id}', '$stars');");
		Database::query("UPDATE _escuela_stars SET stars = $stars WHERE course = $course_id AND person_id = {$request->person->id};");
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

		$course_id = (int) $feed[0];
		$feedback_id = (int) $feed[1];
		$answer = strtolower(trim(($feed[2])));

		$course = $this->getCourse($course_id, $request->person->id);

		if ($course !== false) {
			$feedback = Database::query("SELECT id, text, answers FROM _escuela_feedback WHERE id = $feedback_id;");
			if (isset($feedback[0])) {
				$feedback = $feedback[0];
				$answers = $feedback->answers;
				$feedback_where = " person_id = '{$request->person->id}' AND feedback = $feedback_id AND course = $course_id;";

				// get last answer, and decrease popularity of the course
				$last_answer = false;
				$r = Database::query("SELECT answer FROM _escuela_feedback_received WHERE $feedback_where;");
				if (isset($r[0])) {
					$last_answer = $r[0]->answer;
				}

				if ($last_answer !== false) {
					$popularity = $this->getAnswerValue($answers, $last_answer);
					Database::query("DELETE FROM _escuela_feedback_received WHERE $feedback_where");

					if ($popularity !== false) {
						Database::query("UPDATE _escuela_course SET popularity = popularity - $popularity WHERE id = $course_id;");
					}
				}

				// analyze current answer && increase popularity of the course
				$popularity = $this->getAnswerValue($answers, $answer);
				if ($popularity !== false) {
					Database::query("INSERT INTO _escuela_feedback_received (feedback, course, person_id, email, answer) VALUES ($feedback_id, $course_id, '{$request->person->id}', '{$request->person->email}', '$answer');");
					Database::query("UPDATE _escuela_course SET popularity = popularity + $popularity WHERE id = $course_id;");
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
		Database::query("DELETE FROM _escuela_chapter_viewed WHERE course='{$request->input->data->query}' AND person_id='{$request->person->id}'");
		Database::query("DELETE FROM _escuela_answer_choosen WHERE course='{$request->input->data->query}' AND person_id='{$request->person->id}'");

		// load the test again
		$this->_curso($request, $response);

		if (isset($response->json->course)) {
			$response->json->course->repeated = true;
		}
	}

	/**
	 * Cursos terminados
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @throws Exception
	 */
	public function _terminados(Request $request, Response &$response)
	{
		$id = $request->person->id;
		$person = Person::find($id);
		$this->setLevel($request);

		// get the most popular courses
		$courses = Database::query("
		        SELECT *, coalesce(right_answers / nullif(questions,0),0) * 100 as calification FROM (
                    SELECT 
                            A.id, A.title, A.content, A.popularity, A.category, B.name AS 'professor',
                            A.teacher, COALESCE((SELECT AVG(stars) FROM _escuela_stars WHERE course = A.id), 0) AS stars,
                            (select count(*) from _escuela_chapter_viewed where A.id = _escuela_chapter_viewed.course and person_id = $id) as viewed,
                            (select count(*) from _escuela_question where A.id = _escuela_question.course) as questions,
                            (select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
                            (select count(*) from _escuela_chapter where A.id = _escuela_chapter.course AND _escuela_chapter.xtype = 'PRUEBA') as tests,
                            (select count(*) from _escuela_answer where A.id = _escuela_answer.course) as answers,
                            (select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = '$id') as answers_choosen,
                            (select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course
                                AND _escuela_answer_choosen.person_id = $id
                                AND (SELECT count(*) as right_choose FROM _escuela_question 
                                        WHERE _escuela_question.answer = _escuela_answer_choosen.answer) > 0) as right_answers
                    FROM _escuela_course A JOIN _escuela_teacher B ON A.teacher = B.id
                    WHERE A.active = 1
				) subq
				WHERE viewed >= (chapters - tests) and answers_choosen >= questions
				ORDER BY calification DESC;");

		//$this->setFontFiles();

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
			'title' => 'Terminados',
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
		$r = Database::query("
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

		$r = Database::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder - 1) . ';');
		if (isset($r[0])) {
			$before = $r[0];
		}

		$r = Database::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder + 1) . ';');
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
	private function getCourse($id, $person_id)
	{
		$course = Database::queryFirst("SELECT *, truncate(coalesce(right_answers / nullif(questions,0),0) * 100, 0) as calification FROM (
                    SELECT 
                            A.id, A.title, A.content, A.popularity, A.category, B.name AS 'professor', A.medal,
                            A.teacher, COALESCE((SELECT AVG(stars) FROM _escuela_stars WHERE course = A.id), 0) AS stars,
                           ((SELECT count(*) FROM _escuela_stars WHERE _escuela_stars.person_id = (SELECT id FROM person WHERE person.id = '$person_id') AND _escuela_stars.course = A.id) > 0) as rated,
                          	(SELECT name FROM _escuela_teacher WHERE _escuela_teacher.id = A.teacher) AS teacher_name,
				            (SELECT title FROM _escuela_teacher WHERE _escuela_teacher.id = A.teacher) AS teacher_title,
                            (select count(*) from _escuela_chapter_viewed where A.id = _escuela_chapter_viewed.course and person_id = $person_id) as viewed,
                            (select count(*) from _escuela_question where A.id = _escuela_question.course) as questions,
                            (select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
                            (select count(*) from _escuela_chapter where A.id = _escuela_chapter.course AND _escuela_chapter.xtype = 'PRUEBA') as tests,
                            (select count(*) from _escuela_answer where A.id = _escuela_answer.course) as answers,
                            (select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course AND _escuela_answer_choosen.person_id = $person_id) as answers_choosen,
                            (select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course
                                AND _escuela_answer_choosen.person_id = $person_id
                                AND (SELECT count(*) as right_choose FROM _escuela_question 
                                        WHERE _escuela_question.answer = _escuela_answer_choosen.answer) > 0) as right_answers
                    FROM _escuela_course A JOIN _escuela_teacher B ON A.teacher = B.id
                    WHERE A.active = 1
				) subq
				WHERE id = $id;");

		if ($course === null) {
			return false;
		}

		$course->chapters = $this->getChapters($id, $person_id);
		$course->total_tests = 0;
		$course->total_seen = 0;
		$course->total_answered = 0;
		$course->total_terminated = 0;
		$course->total_questions = 0;
		$course->total_childs = count($course->chapters);
		$course->total_right = 0;
		$course->repeated = false;

		$course->nextChapter = null;
		foreach ($course->chapters as $chapter) {

			// get first by default
			if ($course->nextChapter === null) {
				$course->nextChapter = $chapter;
			}

			// if current is seen, get next
			if ($course->nextChapter->seen) {
				$course->nextChapter = $chapter; // if all are seen, nextChapter is the last (course's test)
			}

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
		}

		$course->total_chapters = $course->total_childs - $course->total_tests;
		$course->terminated = $course->total_terminated == $course->total_childs;
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
	 * @return array
	 * @throws Exception
	 * @internal param int $chapter_id
	 */
	private function getChapterImages($chapter)
	{
		// get course and content
		$chapterText = Database::query("SELECT content, course FROM _escuela_chapter WHERE id=$chapter");
		$content = $chapterText[0]->content;
		$images = [];

		$p = -1;
		do {
			$p = stripos($content, '/school/image?guid=', $p + 1);
			if ($p !== false) {
				$p1 = strpos($content, ')', $p);
				$guid = substr($content, $p + 19, $p1 - $p - 19);
				$images[$guid] = Bucket::getPathByEnvironment('escuela', $guid);
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

		$r = Database::query("SELECT * FROM _escuela_chapter WHERE id = '$id';");

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
		$r = Database::query("SELECT id FROM _escuela_chapter WHERE course = '$course' ORDER BY xorder;");

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
		$rows = Database::query("SELECT id FROM _escuela_question WHERE chapter = '$test_id' ORDER BY xorder;");
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
		$row = Database::query("SELECT * FROM _escuela_question WHERE id = '$question_id';");
		if (isset($row[0])) {
			$q = $row[0];
			$q->answers = $this->getAnswers($question_id, $answer_order);
			$t = $this->isQuestionTerminated($person_id, $question_id);
			$q->terminated = $t;

			$q->answer_choosen = -1;
			$a = Database::query("SELECT answer FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND question = '$question_id'");
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
		$answers = Database::query("SELECT * FROM _escuela_answer WHERE question = '{$question_id}' ORDER BY $orderby;");
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
		$r = Database::query("SELECT count(id) as t FROm _escuela_question WHERE chapter = '$chapter_id';");

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
		$r = Database::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND chapter = '$chapter_id';");

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
		$r = Database::query("SELECT count(person_id) as t FROM _escuela_chapter_viewed WHERE person_id ='$person_id' AND chapter = '$chapter_id';");

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
		$r = Database::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE person_id = '$person_id' AND question = '$question_id';");

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
		$r = Database::query("
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
		$r = Database::query('SELECT * FROM _escuela_teacher WHERE (SELECT COUNT(*) FROM _escuela_course WHERE _escuela_course.active = 1 AND _escuela_course.teacher = _escuela_teacher.id) > 0');
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
	 * @param \Apretaste\Request $request
	 * @param \Apretaste\Response $response
	 *
	 * @throws \Framework\Alert
	 */
	public function _example(Request $request, Response $response)
	{
		$response->setTemplate('chapter2.ejs', [
			'chapter' => (object) [
				'title' => 'Ejemplo de capitulo',
				'content' => [
					(object) [
						'template' => '<p id="<%= id %>"><%= text %></p>',
						'script' => '$("#<%= id %>").click(function() {alert(1);});',
						'style' => '#<%= id %> {background: red;}',
						'data' => (object) [
							'id' => 'parrafo1',
							'text' => 'tremendo texto'
						]
					]
				]
			]
		]);
	}
}

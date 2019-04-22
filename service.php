<?php

class Service {

	/**
	 * Main function
	 *
	 * @author salvipascual
	 *
	 * @param Request
	 */
	public function _main(Request $request, Response &$response) {
		$email = $request->person->email;
		$person = Utils::getPerson($email);
		$this->setLevel($request);

		// get the most popular courses
		$courses = Connection::query("
		  SELECT * FROM (
				SELECT A.id, A.title, A.content, A.popularity, A.category, B.name AS 'professor',
				A.teacher, COALESCE((SELECT AVG(stars) FROM _escuela_stars WHERE course = A.id), 0) AS stars,
								(select count(*) from _escuela_chapter_viewed where A.id = _escuela_chapter_viewed.course and email = '$email') as viewed,
					(select count(*) from _escuela_chapter where A.id = _escuela_chapter.course) as chapters,
					(select count(*) from _escuela_answer where A.id = _escuela_answer.course) as answers,
					(select count(*) from _escuela_answer_choosen where A.id = _escuela_answer_choosen.course 
						AND _escuela_answer_choosen.email = '$email') as answers_choosen					
				FROM _escuela_course A
				JOIN _escuela_teacher B
				ON A.teacher = B.id
				WHERE A.active = 1
				) subq 
				WHERE viewed < chapters and answers_choosen < answers -- no se han visto todos, no se ha respondido todas 
				ORDER BY viewed/nullif(chapters,0),  answers_choosen/nullif(answers,0), popularity DESC
			LIMIT 10");

		// remove extrange chars
		foreach ($courses as $k => $c) {
			$course       = $this->getCourse($c->id, $request->person->email);
			$c->progress  = $course->progress;
			$c->title     = htmlspecialchars($c->title);
			$c->content   = htmlspecialchars($c->content);
			$c->professor = htmlspecialchars($c->professor);
			$c->author    = $c->professor;
			$c->stars     = intval($c->stars);
			$courses[$k]  = $c;
		}

		$level = 'PRINCIPIANTE';
		$r     = Connection::query("SELECT level FROM _escuela_profile WHERE person_id = '{$request->person->id}'");
		if (isset($r[0])) {
			$level = $r[0]->level;
		}

		// setup response
		$response->setLayout('escuela.ejs');
		$response->setTemplate('home.ejs', [
			"max_stars" => 5,
			"courses"   => $courses,
			// si no ha completado el nombre en el perfil debe decir solo Bienvenido
			"name"      => $person->first_name ? $person->first_name : '',
			"level"     => $level,
			"completed" => $this->getTotalCompletedCourses($person->email),
		]);
	}

	/**
	 * Buscar cursos
	 *
	 * @param \Request $request
	 * @param \Response $response
	 */
	public function _buscar(Request $request, Response &$response) {
		$courses   = [];
		$noResults = FALSE;
		$data      = $request->input->data->query;
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
				if ($data->raiting !== 'ALL') {
					$where .= " AND stars >= '{$data->raiting}'";
				}
			}
			if (isset($data->title)) {
				if ($data->title !== '') {
					$where .= " AND title LIKE '%{$data->title}%'";
				}
			}

			$courses = Connection::query("
			SELECT * FROM (
			SELECT A.id, A.title, A.content, A.popularity, A.category, B.name AS 'professor', A.teacher,
			COALESCE((SELECT AVG(stars) FROM _escuela_stars WHERE course = A.id), 0) AS stars
			FROM _escuela_course A
			JOIN _escuela_teacher B
			ON A.teacher = B.id
			WHERE A.active = 1) subq
			WHERE TRUE $where ORDER BY popularity DESC LIMIT 10");

			$noResults = !isset($courses);
		}

		if (!is_array($courses)) {
			$courses = [];
		}

		// remove extrange chars
		foreach ($courses as $k => $c) {
			$course       = $this->getCourse($c->id, $request->person->email);
			$c->progress  = $course->progress;
			$c->title     = htmlspecialchars($c->title);
			$c->content   = htmlspecialchars($c->content);
			$c->professor = htmlspecialchars($c->professor);
			$c->author    = $c->professor;
			$c->stars     = intval($c->stars);
			$courses[$k]  = $c;
		}

		// display the course
		$response->setLayout('escuela.ejs');
		$response->setTemplate('search.ejs', [
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
			"max_stars"  => 5,
		]);
	}

	/**
	 * Retrieve a course
	 *
	 * @author kuma
	 * @example ESCUELA CURSO 2
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _curso(Request $request, Response &$response) {
		// get the course details
		$id     = intval($request->input->data->query);
		$course = $this->getCourse($id, $request->person->email);

		// if course cannot be found
		if (empty($course)) {

			$response->setLayout('escuela.ejs');
			$response->setTemplate('text.ejs', [
				"title" => "Curso no encontrado",
				"body"  => "No encontramos el curso que usted pidio",
			]);

			return;
		}

		// display the course
		$response->setLayout('escuela.ejs');
		$response->setTemplate('course.ejs', ['course' => $course]);
	}

	/**
	 * Subservice CAPITULO
	 *
	 * @author kuma
	 * @example ESCUELA CAPITULO 3
	 *
	 * @param Response $response
	 * @param Request $request
	 *
	 */
	public function _capitulo(Request $request, Response &$response) {
		$id      = intval($request->input->data->query);
		$chapter = $this->getChapter($id, $request->person->email);

		if ($chapter) {
			$responses        = [];
			$beforeAfter      = $this->getBeforeAfter($chapter);
			$images           = $this->getChapterImages($id);
			$chapter->content = Utils::putInlineImagesToHTML($chapter->content, $images, 'cid:', '.jpg');

			// Log the visit to this chapter
			if ($chapter->xtype == 'CAPITULO') {
				Connection::query("INSERT IGNORE INTO _escuela_chapter_viewed (email, chapter, course) VALUES ('{$request->person->email}', '{$id}', '{$chapter->course}');");
			}

			// remove the cid: part from the content
			//$di = \Phalcon\DI\FactoryDefault::getDefault();
			/*if($di->get('environment') == "app")
			{
				$chapter->content = str_replace("cid:", "", $chapter->content);
			}

			// display the image via web
			if($di->get('environment') == "web")
			{
				$http = $di->get('path')['http'];
				$chapter->content = str_replace("cid:", "$http/courses/8/149/", $chapter->content);
			}*/

			// get the code inside the <body> tag
			if (stripos($chapter->content, '<body>') !== FALSE) {
				$ini              = strpos($chapter->content, '<body>') + 6;
				$end              = strpos($chapter->content, '</body>');
				$chapter->content = substr($chapter->content, $ini, $end - $ini);
			}

			// check if the course is terminated
			$course = $this->getCourse($chapter->course, $request->person->email);

			// send response to the view

			$response->setLayout('escuela.ejs');
			$response->setTemplate('chapter.ejs', [
				'chapter' => $chapter,
				'course'  => $course,
				'before'  => $beforeAfter['before'],
				'after'   => $beforeAfter['after'],
			], $images);

			$responses[] = $response;

			return $responses;
		}

		$response->setLayout('escuela.ejs');
		$response->createFromText("Capitulo no encontrado");
	}

	/**
	 * Subservice PRUEBA
	 *
	 * @example ESCUELA PRUEBA 2
	 */
	public function _prueba(Request $request, Response &$response) {
		$this->_capitulo($request, $response);
	}

	/**
	 * Records the answer for a question and resturns an empty response
	 */
	public function _responder(Request $request, Response &$response) {
		// pull the answer selected
		$answers = $request->input->data->answers;
		foreach ($answers as $id) {

			$res = Connection::query("SELECT * FROM _escuela_answer WHERE id=$id");

			// do not let pass invalid answers
			if (empty($res)) {
				continue;
			}
			else {
				$answer = $res[0];
			}

			// save the answer in the database
			Connection::query("
			INSERT IGNORE INTO _escuela_answer_choosen (email, answer, chapter, question, course)
			VALUES ('{$request->person->email}','$id', '{$answer->chapter}', '{$answer->question}', '{$answer->course}')");

		  $this->setLevel($request);
		}
	}

	/**
	 * Set level
	 *
	 * @param \Request $request
	 */
	public function setLevel(Request $request){
		$resume = $this->getResume($request->person->email);
		$total = 0;
		foreach($resume as $item){
			if ($item->answers > 0)
				if ($item->right_answers / $item->answers >= 0.8)
					$total++;
		}

		$level = 'PRINCIPIANTE';

		if ($total >= 1) $level = 'LITERADO';
		if ($total >= 3) $level = 'ESTUDIOSO';
		if ($total >= 6) $level = 'EDUCADO';
		if ($total >= 10) $level = 'EXPERTO';
		if ($total >= 15) $level = 'MAESTRO';
		if ($total >= 30) $level = 'GURU';

		// update user level
		Connection::query("UPDATE _escuela_profile SET level = '$level' WHERE person_id = '{$request->person->id}';");
	}

	/**
	 * Rate course
	 */
	public function _calificar(Request $request, Response &$response) {
		$course_id = $request->input->data->query->course;
		$stars = $request->input->data->query->stars;
		$stars = $stars > 5 ? 5: $stars;

		Connection::query("INSERT IGNORE INTO _escuela_stars (course, person_id, stars) VALUES ('$course_id', '{$request->person->id}', '$stars');");
		Connection::query("UPDATE _escuela_stars SET stars = $stars WHERE course = $course_id AND person_id = {$request->person->id};");
	}

	/**
	 * Subservice OPINAR
	 *
	 * @param \Request $request
	 *
	 * @return \Response
	 */
	public function _opinar(Request $request, Response &$response) {
		// expecting: course_id feedback_id answer
		$q = trim($request->input->data->query);

		Utils::clearStr($q);
		$feed = explode(' ', $q);

		if (!isset($feed[0]) || !isset($feed[1]) || !isset($feed[2])) {
			return new Response();
		}

		$courseid    = intval($feed[0]);
		$feedback_id = intval($feed[1]);
		$answer      = trim(strtolower(($feed[2])));

		$course = $this->getCourse($courseid);

		if ($course !== FALSE) {
			$feedback = Connection::query("SELECT id, text, answers FROM _escuela_feedback WHERE id = $feedback_id;");
			if (isset($feedback[0])) {
				$feedback       = $feedback[0];
				$answers        = $feedback->answers;
				$feedback_where = " email = '{$request->person->email}' AND feedback = $feedback_id AND course = $courseid;";

				// get last answer, and decrease popularity of the course
				$last_answer = FALSE;
				$r           = Connection::query("SELECT answer FROM _escuela_feedback_received WHERE $feedback_where;");
				if (isset($r[0])) {
					$last_answer = $r[0]->answer;
				}

				if ($last_answer !== FALSE) {
					$popularity = $this->getAnswerValue($answers, $last_answer);
					Connection::query("DELETE FROM _escuela_feedback_received WHERE $feedback_where");

					if ($popularity !== FALSE) {
						Connection::query("UPDATE _escuela_course SET popularity = popularity - $popularity WHERE id = $courseid;");
					}
				}

				// analyze current answer && increase popularity of the course
				$popularity = $this->getAnswerValue($answers, $answer);
				if ($popularity !== FALSE) {
					Connection::query("INSERT INTO _escuela_feedback_received (feedback, course, email, answer) VALUES ($feedback_id, $courseid, '{$request->person->email}', '$answer');");
					Connection::query("UPDATE _escuela_course SET popularity = popularity + $popularity WHERE id = $courseid;");
				}
			}
		}

	}

	/**
	 * Repeats a test for a course
	 *
	 * @author kuma
	 *
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function _repetir(Request $request, Response &$response) {
		// remove the previous answers
		Connection::query("DELETE FROM _escuela_answer_choosen WHERE course='{$request->input->data->query}' AND email='{$request->person->email}'");

		// load the test again
		$this->_curso($request, $response);
		$response->content['course']->repeated = TRUE;
	}

	/**
	 * Perfil de escuela
	 *
	 * @param \Request $request
	 * @param \Response $response
	 */
	public function _perfil(Request $request, Response &$response) {

		// save profile
		if (isset($request->input->data->save)) {
			$fields = [
				'first_name',
				'last_name',
				'year_of_birth',
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
			foreach ($request->input->data as $key => $value) {

				if ($key == 'date_of_birth') {
					$value = DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d');
				}

				if (in_array($key, $fields)) {
					$pieces[] = "$key='$value'";
				}
			}

			// save changes on the database
			if (!empty($pieces)) {
				Connection::query("UPDATE person SET " . implode(",", $pieces) . " WHERE id={$request->person->id}");
			}

			Connection::query("UPDATE _escuela_profile SET level = '{$request->input->data->level}' WHERE person_id = '{$request->person->id}'");

			return;
		}

		// show profile
		$resume         = $this->getResume($request->person->email);
		$profile        = Utils::getPerson($request->person->email);
		$profile->level = 'PRINCIPIANTE';
		$r              = Connection::query("SELECT * FROM _escuela_profile WHERE person_id = '{$request->person->id}'");
		if (!isset($r[0])) {
			Connection::query("INSERT INTO _escuela_profile (person_id, `level`) VALUES ('{$request->person->id}','PRINCIPIANTE');");
		}
		else {
			$profile->level = $r[0]->level;
		}

		$r = Connection::query("SELECT COLUMN_TYPE AS result
				FROM information_schema.`COLUMNS`
				WHERE TABLE_NAME = '_escuela_profile'
							AND COLUMN_NAME = 'level';");

		$levels = explode(",", str_replace(["'", "enum(", ")"], "", $r[0]->result));
		$response->setLayout('escuela.ejs');
		$response->setTemplate("profile.ejs", [
			"resume"  => $resume,
			"profile" => $profile,
			"levels"  => $levels,
		]);
	}

	private function getResume($email) {
		$r = Connection::query("
			SELECT id, medal, 
				(select count(*) from _escuela_chapter_viewed where _escuela_course.id = _escuela_chapter_viewed.course and email = '$email') as viewed,
				(select count(*) from _escuela_chapter where _escuela_course.id = _escuela_chapter.course) as chapters,
				(select count(*) from _escuela_question where _escuela_course.id = _escuela_question.course) as questions,
				(select count(*) from _escuela_answer where _escuela_course.id = _escuela_answer.course) as answers,
				(select count(*) from _escuela_answer_choosen where _escuela_course.id = _escuela_answer_choosen.course 
					AND _escuela_answer_choosen.email = '$email'
					AND (SELECT right_choosen FROM _escuela_answer WHERE _escuela_answer.id = _escuela_answer_choosen.answer) = 1) as right_answers
			FROM _escuela_course;");

		return $r;
	}

	/**
	 * Return feedbacks
	 *
	 * @return array
	 */
	private function getFeedbacks() {
		$feedback = Connection::query("SELECT id, text, answers FROM _escuela_feedback;");
		foreach ($feedback as $k => $fb) {
			$fb->answers = explode(',', $fb->answers);

			$new_answers = [];
			foreach ($fb->answers as $ans) {
				$value   = $ans;
				$caption = trim(ucfirst(strtolower($ans)));
				if (strpos($ans, ":") !== FALSE) {
					$arr     = explode(":", $ans);
					$value   = trim($arr[0]);
					$caption = trim($arr[1]);
				}

				$new_answers[] = ['value' => $value, 'caption' => $caption];
			}

			$feedback[$k]->answers = $new_answers;
		}

		return $feedback;
	}

	/**
	 * Get answer
	 *
	 * @param $answers
	 * @param $answer
	 *
	 * @return bool|int
	 */
	private function getAnswerValue($answers, $answer) {
		$answers = explode(",", $answers);

		$i = 0;
		foreach ($answers as $ans) {
			$ans = trim($ans);
			$i++;

			$value = $ans;

			if (strpos($ans, ":") !== FALSE) {
				$arr   = explode(":", $ans);
				$value = trim($arr[0]);
			}

			if ($value == $answer) {
				return $i;
			}
		}

		return FALSE;
	}

	/**
	 * Return previous and next chapter
	 *
	 * @author kuma
	 *
	 * @param $chapter
	 *
	 * @return array
	 */
	private function getBeforeAfter($chapter) {
		$before = FALSE;
		$after  = FALSE;

		$r = Connection::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder - 1) . ";");
		if (isset($r[0])) {
			$before = $r[0];
		}

		$r = Connection::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder + 1) . ";");
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
	 * @param string $email
	 *
	 * @return object/boolean
	 */
	private function getCourse($id, $email = '') {
		// get the full course
		$res = Connection::query("
			SELECT *,
				(SELECT name FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) AS teacher_name,
				(SELECT title FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) AS teacher_title
			FROM _escuela_course
			WHERE id='$id'
			AND active=1");

		// do not continue with empty values
		if (empty($res)) {
			return FALSE;
		}
		else {
			$course = $res[0];
		}

		$course->chapters = $this->getChapters($id, $email);

		$calification             = 0;
		$course->total_tests      = 0;
		$course->total_seen       = 0;
		$course->total_answered   = 0;
		$course->total_terminated = 0;
		$course->total_questions  = 0;
		$course->total_childs     = count($course->chapters);
		$course->total_right      = 0;
		$course->repeated         = FALSE;

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
		if ($course->total_tests > 0) {
			$course->calification = number_format($calification / $course->total_tests, 2) * 1;
		}

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
	private function getChapterImages($chapter) {
		// get course and content
		$chapterText = Connection::query("SELECT content, course FROM _escuela_chapter WHERE id=$chapter");
		$content     = $chapterText[0]->content;

		$tidy    = new tidy();
		$content = $tidy->repairString($content, [
			'output-xhtml' => TRUE,
		], 'utf8');

		$course = $chapterText[0]->course;

		// get all images from the content
		$dom = new DOMDocument();
		$dom->loadHTML($content);
		$imgs = $dom->getElementsByTagName('img');

		// get path to root folder
		$di      = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get full path to the image
		$images = [];
		foreach ($imgs as $img) {
			$src               = $img->getAttribute('src');
			$filename          = str_replace("cid:", "", $src);
			$images[$filename] = "$wwwroot/public/courses/$course/$chapter/$filename";
		}

		return $images;
	}

	/**
	 * Get chapter entity
	 *
	 * @param integer $id
	 *
	 * @return object
	 */
	private function getChapter($id, $email = '', $answer_order = 'rand()') {
		$chapter = FALSE;

		$r = Connection::query("SELECT * FROM _escuela_chapter WHERE id = '$id';");

		if (isset($r[0])) {
			$chapter = $r[0];
		}

		$chapter->questions = $this->getChapterQuestions($id, $email, $answer_order);

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

		$chapter->seen       = $this->isChapterSeen($email, $id);
		$chapter->answered   = $this->isTestTerminated($email, $id) && $chapter->xtype == 'PRUEBA';
		$chapter->terminated = $chapter->answered || $chapter->seen;

		$chapter->content = $this->clearHtml($chapter->content);

		return $chapter;
	}

	/**
	 * Get list of chapters
	 *
	 * @param integer $course
	 * @param string $email
	 * @param bool $terminated
	 *
	 * @return array
	 */
	private function getChapters($course, $email = '', $terminated = NULL) {
		// get chapters
		$r = Connection::query("SELECT id FROM _escuela_chapter WHERE course = '$course' ORDER BY xorder;");

		$chapters = [];
		if ($r) {
			foreach ($r as $row) {
				$c = $this->getChapter($row->id, $email);
				if ($c->terminated == $terminated || is_null($terminated)) {
					$chapters[] = $c;
				}
			}
		}

		return $chapters;
	}

	private function getChapterQuestions($test_id, $email = '', $answer_order = 'rand()') {
		$questions = [];
		$rows      = Connection::query("SELECT id FROM _escuela_question WHERE chapter = '$test_id' ORDER BY xorder;");
		if (!is_array($questions)) {
			$questions = [];
		}

		foreach ($rows as $i => $q) {
			$questions[] = $this->getQuestion($q->id, $email, $answer_order);
		}

		return $questions;
	}

	/**
	 * Return question object
	 *
	 * @param $question_id
	 * @param string $email
	 * @param string $answer_order
	 *
	 * @return bool
	 */
	private function getQuestion($question_id, $email = '', $answer_order = 'rand()') {
		$row = Connection::query("SELECT * FROM _escuela_question WHERE id = '$question_id';");
		if (isset($row[0])) {
			$q             = $row[0];
			$q->answers    = $this->getAnswers($question_id, $answer_order);
			$t             = $this->isQuestionTerminated($email, $question_id);
			$q->terminated = $t;

			$q->answer_choosen = -1;
			$a                 = Connection::query("SELECT answer FROM _escuela_answer_choosen WHERE email = '$email' AND question = '$question_id'");
			if (isset($a[0])) {
				$q->answer_choosen = intval($a[0]->answer);
			}

			$q->is_right = $q->answer_choosen == $q->answer;

			return $q;
		}

		return FALSE;
	}

	/**
	 * Return answers of a question
	 *
	 * @param $question_id
	 * @param string $orderby
	 *
	 * @return array
	 */
	private function getAnswers($question_id, $orderby = 'rand()') {
		$answers = Connection::query("SELECT * FROM _escuela_answer WHERE question = '{$question_id}' ORDER BY $orderby;");
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
	private function getTotalQuestionsOf($chapter_id) {
		$r = Connection::query("SELECT count(id) as t FROm _escuela_question WHERE chapter = '$chapter_id';");

		return intval($r[0]->t);
	}

	/**
	 * Return the total of chapter's responses
	 *
	 * @param $email
	 * @param $chapter_id
	 *
	 * @return int
	 */
	private function getTotalResponsesOf($email, $chapter_id) {
		$r = Connection::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE email = '$email' AND chapter = '$chapter_id';");

		return intval($r[0]->t);
	}

	/**
	 * Check if user finish the test
	 *
	 * @param string $email
	 * @param integer $test_id
	 *
	 * @return boolean
	 */
	private function isTestTerminated($email, $test_id) {
		$total_questions = $this->getTotalQuestionsOf($test_id);
		$total_responses = $this->getTotalResponsesOf($email, $test_id);

		return $total_questions == $total_responses;
	}

	/**
	 * Return TRUE if a chapter was seen by the user
	 *
	 * @param $email
	 * @param $chapter_id
	 *
	 * @return bool
	 */
	private function isChapterSeen($email, $chapter_id) {
		$r = Connection::query("SELECT count(email) as t FROM _escuela_chapter_viewed WHERE email ='$email' AND chapter = '$chapter_id';");

		return $r[0]->t * 1 > 0;
	}

	/**
	 * Return TRUE if a question is terminated by the user
	 *
	 * @param $email
	 * @param $question_id
	 *
	 * @return bool
	 */
	private function isQuestionTerminated($email, $question_id) {
		$r = Connection::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE email = '$email' AND question = '$question_id';");

		return intval($r[0]->t) > 0;
	}

	private function getTotalCompletedCourses($email) {
		$r = Connection::query("
			select count(*) as t from (
				select course, 
						count(*) as total, 
						(select count(*) 
						from _escuela_chapter_viewed 
						where _escuela_chapter.course = _escuela_chapter_viewed.course 
						and email = '$email') as viewed
				from  _escuela_chapter
				group by course
    		) subq
    		where subq.total = subq.viewed");

		return intval($r[0]->t);
	}

	private function getTeachers() {
		$r = Connection::query('SELECT * FROM _escuela_teacher');
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
	private function clearHtml($html) {
		$html = str_replace('&nbsp;', ' ', $html);

		do {
			$tmp  = $html;
			$html = preg_replace('#<([^ >]+)[^>]*>[[:space:]]*</\1>#', '', $html);
		} while ($html !== $tmp);

		return $html;
	}


}

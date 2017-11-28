<?php

class Escuela extends Service
{
	/**
	 * Function executed when the service is called
	 *
	 * @author kuma
	 * @example ESCUELA
	 * @param Request
	 * @return Response
	 */
	public function _main(Request $request)
	{
		$courses = [];
		$r = Connection::query("SELECT id FROM _escuela_course WHERE active = 1 ORDER BY popularity DESC");
		if(isset($r[0])) foreach($r as $course) $courses[] = $this->getCourse($course->id, $request->email);

		$current_courses = [];
		$new_courses = [];
		$old_courses = [];

		foreach($courses as $course)
		{
			if($course->progress == 100) $old_courses[] = $course;
			elseif($course->progress == 0) $new_courses[] = $course;
			else $current_courses[] = $course;
		}

		$l = count($current_courses);
		for($i = 0; $i < $l - 1; $i ++) for($j = $i + 1; $j < $l; $j ++) if($current_courses[ $i ]->progress < $current_courses[ $j ]->progress)
		{
			$temp  = $current_courses[ $i ];
			$current_courses[ $i ] = $current_courses[ $j ];
			$current_courses[ $j ] = $temp;
		}

		$response = new Response();
		$response->setEmailLayout('layout.tpl');
		$response->setResponseSubject("Cursos activos");
		$response->createFromTemplate('basic.tpl', ['courses' => array_merge($current_courses, $new_courses, $old_courses)]);
		return $response;
	}

	/**
	 * Retrieve a course
	 *
	 * @author kuma
	 * @example ESCUELA CURSO 2
	 * @param Request $request
	 * @return Response
	 */
	public function _curso(Request $request)
	{
		// get the course details
		$id = intval($request->query);
		$course = $this->getCourse($id, $request->email);

		// if course cannot be found
		if(empty($course))
		{
			$response = new Response();
			$response->setEmailLayout('layout.tpl');
			$response->setResponseSubject("Curso no encontrado");
			$response->createFromText("No encontramos el curso que usted pidio");
			return $response;
		}

		// display the course
		$response = new Response();
		$response->setEmailLayout('layout.tpl');
		$response->setResponseSubject("Curso: {$course->title}");
		$response->createFromTemplate('course.tpl', ['course' => $course]);
		return $response;
	}

	/**
	 * Subservice CAPITULO
	 *
	 * @author kuma
	 * @example ESCUELA CAPITULO 3
	 * @param Request $request
	 * @return Response/array
	 */
	public function _capitulo(Request $request)
	{
		$id = intval($request->query);
		$chapter = $this->getChapter($id, $request->email);

		if($chapter)
		{
			$responses = [];
			$beforeAfter = $this->getBeforeAfter($chapter);
			$images = $this->getChapterImages($id);

			// Log the visit to this chapter
			if($chapter->xtype == 'CAPITULO') Connection::query("INSERT IGNORE INTO _escuela_chapter_viewed (email, chapter, course) VALUES ('{$request->email}', '{$id}', '{$chapter->course}');");

			// remove the cid: part from the content
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			if($di->get('environment') == "app") {
				$chapter->content = str_replace("cid:", "", $chapter->content);
			}

			// display the image via web
			if($di->get('environment') == "web") {
				$http = $di->get('path')['http'];
				$chapter->content = str_replace("cid:", "$http/courses/8/149/", $chapter->content);
			}

			// get the code inside the <body> tag
			$ini = strpos($chapter->content, '<body>')+6;
			$end = strpos($chapter->content, '</body>');
			$chapter->content = substr($chapter->content, $ini, $end-$ini);

			// check if the course is terminated
			$course = $this->getCourse($chapter->course, $request->email);

			// send response to the view
			$response = new Response();
			$response->setEmailLayout('layout.tpl');
			$response->setResponseSubject($chapter->title);
			$response->createFromTemplate('chapter.tpl', [
				'chapter' => $chapter,
				'course' => $course,
				'before' => $beforeAfter['before'],
				'after' => $beforeAfter['after']
			], $images);

			$responses[] = $response;
			return $responses;
		}

		$response = new Response();
		$response->setEmailLayout('layout.tpl');
		$response->setResponseSubject("Capitulo no encontrado");
		$response->createFromText("Capitulo no encontrado");
		return $response;
	}

	/**
	 * Subservice PRUEBA
	 *
	 * @example ESCUELA PRUEBA 2
	 */
	public function _prueba(Request $request)
	{
		return $this->_capitulo($request);
	}

	/**
	 * Records the answer for a question and resturns an empty response
	 *
	 * @author salvipascual
	 * @example ESCUELA RESPONDER 4
	 */
	public function _responder(Request $request)
	{
		// pull the answer selected
		$id	= intval($request->query);
		$res = Connection::query("SELECT * FROM _escuela_answer WHERE id=$id");

		// do not let pass invalid answers
		if(empty($res)) return new Response();
		else $answer = $res[0];

		// save the answer in the database
		Connection::query("
			INSERT IGNORE INTO _escuela_answer_choosen (email, answer, chapter, question, course)
			VALUES ('{$request->email}','$id', '{$answer->chapter}', '{$answer->question}', '{$answer->course}')");
		return new Response();
	}

	/**
	 * Check if you passed the course and email your certificate
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function _certificado(Request $request)
	{
		// get the course details
		$courseid = intval($request->query);
		$course = $this->getCourse($courseid, $request->email);

		// if you failed the course
		if(empty($course) || $course->calification < 80)
		{
			$response = new Response();
			$response->setEmailLayout('layout.tpl');
			$response->setResponseSubject("No has conseguido pasar el curso");
			$response->createFromTemplate("failed.tpl", array("course"=>$course));
			return $response;
		}

		// get the person passing
		$person = $this->utils->getPerson($request->email);
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$certLogo = $di->get('path')['http'] . "/images/sello.jpg";

		// create HTML for the certificate
		$html = '<html><body><table width="600" align="center"><tr><td style="border: 1px solid black;"><table width="600" align="center"><tr><td style="border: 5px solid black;"><table width="600" align="center"><tr><td style="border: 1px solid black;"><table width="560" align="center" cellpadding="5" style="margin: 20px;"><tr><td colspan="2" style="font-family: Arial Black; padding: 5px; border: none; font-size: 34px;" align="center"><img height="150" src="'.$certLogo.'"><br/><strong>CERTIFICACION<br/> DE CURSO TERMINADO</strong></td></tr><tr><td colspan="2" style="font-family: Arial; padding: 5px; border: none;" align="center"><br/>El presente certifica que<br/></td></tr><tr><td colspan="2" style="font-size: 23px; padding: 5px; border: none;" align="center">{$person}<hr/></td></tr><tr><td colspan="2" style="font-family: Arial; padding: 5px; border: none;" align="center"><br/>ha terminado con exito el curso</td></tr><tr><td colspan="2" align="center" style="font-size: 23px; font-family: Arial; padding: 5px; border: none;"><br/>{$course}<hr/></td></tr><tr><td width="50%" style="font-size: 20px; padding: 5px; border: none; border: none;" align="center"><br/></td><td style="font-size: 20px; padding: 5px; border: none; border: none;" align="center"><br/>{$teacher_name}<hr/></td></tr><tr><td style="padding: 5px; border: none;" align="center"></td><td style="font-family: Arial; padding: 5px; border: none;" align="center">{$teacher_title}</td></tr><tr><td colspan="2" align="center" style="padding: 5px; border: none; text-decoration:none;"><i>{$date}</i></td></tr></table></td></tr></table></td></tr></table></td></tr></table></body></html>';
		$html = str_replace('{$person}', $person->full_name, $html);
		$html = str_replace('{$course}', $course->title, $html);
		$html = str_replace('{$teacher_name}', $course->teacher_name, $html);
		$html = str_replace('{$teacher_title}', $course->teacher_title, $html);
		$html = str_replace('{$date}', date("d M Y"), $html);

		// create the PDF of the certificate
		$fileName = $this->utils->generateRandomHash() . ".pdf";
		$filePath = $this->utils->getTempDir() . $fileName;

		// save the PDF and download
		$wwwroot = $di->get('path')['root'];

		if (!class_exists('mPDF'))
			include_once $wwwroot."/lib/mpdf/mpdf.php";

		$mPDF = new Mpdf();
		$mPDF->WriteHTML(trim($html));
		$mPDF->Output($filePath, 'F');

		// send file as email attachment
		$email = new Email();
		$email->to = $person->email;
		$email->subject = "Su certificado";
		$email->body = "Su certificado se encuentra adjunto a este correo";
		$email->attachments = array($filePath);
		$email->send();

		// send response to the view
		$response = new Response();
		$response->setEmailLayout('layout.tpl');
		$response->setResponseSubject("Certificacion de curso terminado");
		$response->createFromTemplate("certificate.tpl", array("course" => $course, "email"=>$person->email));
		return $response;
	}

	/**
	 * Subservice OPINAR
	 *
	 * @param \Request $request
	 * @return \Response
	 */
	public function _opinar(Request $request)
	{
		// expecting: course_id feedback_id answer
		$q = trim($request->query);

		$this->utils->clearStr($q);
		$feed = explode(' ', $q);

		if( ! isset($feed[0]) || ! isset($feed[1]) || ! isset($feed[2])) return new Response();

		$courseid = intval($feed[0]);
		$feedback_id = intval($feed[1]);
		$answer = trim(strtolower(($feed[2])));

		$course = $this->getCourse($courseid);

		if($course !== false)
		{
			$feedback = Connection::query("SELECT id, text, answers FROM _escuela_feedback WHERE id = $feedback_id;");
			if(isset($feedback[0]))
			{
				$feedback	   = $feedback[0];
				$answers		= $feedback->answers;
				$feedback_where = " email = '{$request->email}' AND feedback = $feedback_id AND course = $courseid;";

				// get last answer, and decrease popularity of the course
				$last_answer = false;
				$r = Connection::query("SELECT answer FROM _escuela_feedback_received WHERE $feedback_where;");
				if(isset($r[0])) $last_answer = $r[0]->answer;

				if($last_answer !== false)
				{
					$popularity = $this->getAnswerValue($answers, $last_answer);
					Connection::query("DELETE FROM _escuela_feedback_received WHERE $feedback_where");

					if($popularity !== false) Connection::query("UPDATE _escuela_course SET popularity = popularity - $popularity WHERE id = $courseid;");
				}

				// analyze current answer && increase popularity of the course
				$popularity = $this->getAnswerValue($answers, $answer);
				if($popularity !== false)
				{
					Connection::query("INSERT INTO _escuela_feedback_received (feedback, course, email, answer) VALUES ($feedback_id, $courseid, '{$request->email}', '$answer');");
					Connection::query("UPDATE _escuela_course SET popularity = popularity + $popularity WHERE id = $courseid;");
				}
			}
		}

		return new Response();
	}

	/**
	 * Repeats a test for a course
	 *
	 * @author kuma
	 * @param Request $request
	 * @return Response
	 */
	public function _repetir(Request $request)
	{
		// remove the previous answers
		Connection::query("DELETE FROM _escuela_answer_choosen WHERE course='{$request->query}' AND email='{$request->email}'");

		// load the test again
		$response = $this->_curso($request);
		$response->setEmailLayout('layout.tpl');
		$response->content['course']->repeated = true;
		return $response;
	}

	/**
	 * Return feedbacks
	 *
	 * @return array
	 */
	private function getFeedbacks()
	{
		$feedback = Connection::query("SELECT id, text, answers FROM _escuela_feedback;");
		foreach($feedback as $k => $fb)
		{
			$fb->answers = explode(',', $fb->answers);

			$new_answers = [];
			foreach($fb->answers as $ans)
			{
				$value   = $ans;
				$caption = trim(ucfirst(strtolower($ans)));
				if(strpos($ans, ":") !== false)
				{
					$arr	 = explode(":", $ans);
					$value   = trim($arr[0]);
					$caption = trim($arr[1]);
				}

				$new_answers[] = ['value' => $value, 'caption' => $caption];
			}

			$feedback[ $k ]->answers = $new_answers;
		}

		return $feedback;
	}

	/**
	 * Get answer
	 *
	 * @param $answers
	 * @param $answer
	 * @return bool|int
	 */
	private function getAnswerValue($answers, $answer)
	{
		$answers = explode(",", $answers);

		$i = 0;
		foreach($answers as $ans)
		{
			$ans = trim($ans);
			$i ++;

			$value = $ans;

			if(strpos($ans, ":") !== false)
			{
				$arr   = explode(":", $ans);
				$value = trim($arr[0]);
			}

			if($value == $answer)
			{
				return $i;
			}
		}

		return false;
	}

	/**
	 * Return previous and next chapter
	 *
	 * @author kuma
	 * @param $chapter
	 * @return array
	 */
	private function getBeforeAfter($chapter)
	{
		$before = false;
		$after  = false;

		$r = Connection::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder - 1) . ";");
		if(isset($r[0])) $before = $r[0];

		$r = Connection::query("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = " . ($chapter->xorder + 1) . ";");
		if(isset($r[0])) $after = $r[0];

		return [
			'before' => $before,
			'after' => $after
		];
	}

	/**
	 * Get course
	 *
	 * @param integer $id
	 * @param string  $email
	 * @return object/boolean
	 */
	private function getCourse($id, $email = '')
	{
		// get the full course
		$res = Connection::query("
			SELECT *,
				(SELECT name FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) as teacher_name,
				(SELECT title FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) as teacher_title
			FROM _escuela_course
			WHERE id='$id'
			AND active=1");

		// do not continue with empty values
		if(empty($res)) return false;
		else $course = $res[0];

		$course->chapters = $this->getChapters($id, $email);

		$calification = 0;
		$course->total_tests = 0;
		$course->total_seen = 0;
		$course->total_answered  = 0;
		$course->total_terminated = 0;
		$course->total_questions = 0;
		$course->total_childs = count($course->chapters);
		$course->total_right = 0;
		$course->repeated = false;

		foreach($course->chapters as $chapter)
		{
			if($chapter->seen) $course->total_seen++;
			if($chapter->answered) $course->total_answered++;
			if($chapter->terminated) $course->total_terminated++;
			if($chapter->xtype == 'PRUEBA') $course->total_tests++;
			$course->total_right += $chapter->total_right;
			$course->total_questions += $chapter->total_questions;
			$calification += $chapter->calification;
		}

		$course->total_chapters = $course->total_childs - $course->total_tests;
		$course->terminated = $course->total_terminated == $course->total_childs;

		$course->calification = 0;
		if($course->total_tests > 0) $course->calification = number_format($calification / $course->total_tests, 2) * 1;

		$course->progress = 0;
		if($course->total_childs > 0) $course->progress = number_format($course->total_terminated / $course->total_childs * 100, 2) * 1;

		$course->all_chapters_finished = $course->total_chapters == $course->total_seen;

		return $course;
	}

	/**
	 * Return a list of chapter's images paths
	 *
	 * @param integer $chapter_id
	 * @return array
	 */
	private function getChapterImages($chapter)
	{
		// get course and content
		$chapterText = Connection::query("SELECT content, course FROM _escuela_chapter WHERE id=$chapter");
		$content = $chapterText[0]->content;
		$course = $chapterText[0]->course;

		// get all images from the content
		$dom = new DOMDocument();
		$dom->loadHTML($content);
		$imgs = $dom->getElementsByTagName('img');

		// get path to root folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get full path to the image
		$images = [];
		foreach ($imgs as $img) {
			$src = $img->getAttribute('src');
			$filename = str_replace("cid:", "", $src);
			$images[$filename] = "$wwwroot/public/courses/$course/$chapter/$filename";
		}

		return $images;
	}

	/**
	 * Get chapter entity
	 *
	 * @param integer $id
	 * @return object
	 */
	private function getChapter($id, $email = '', $answer_order = 'rand()')
	{
		$chapter = false;

		$r = Connection::query("SELECT * FROM _escuela_chapter WHERE id = '$id';");

		if(isset($r[0])) $chapter = $r[0];

		$chapter->questions = $this->getChapterQuestions($id, $email, $answer_order);

		$total_questions = count($chapter->questions);
		$total_right	 = 0;

		foreach($chapter->questions as $i => $q) if($q->is_right) $total_right ++;

		$chapter->total_right	 = $total_right;
		$chapter->total_questions = $total_questions;
		$chapter->calification	= 0;

		if($total_questions > 0) $chapter->calification = intval($total_right / $total_questions * 100);

		$chapter->seen	   = $this->isChapterSeen($email, $id);
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
	 * @return array
	 */
	private function getChapters($course, $email = '', $terminated = null)
	{
		// get chapters
		$r = Connection::query("SELECT id FROM _escuela_chapter WHERE course = '$course' ORDER BY xorder;");

		$chapters = [];
		if($r) foreach($r as $row)
		{
			$c = $this->getChapter($row->id, $email);
			if($c->terminated == $terminated || is_null($terminated)) $chapters[] = $c;
		}
		return $chapters;
	}

	private function getChapterQuestions($test_id, $email = '', $answer_order = 'rand()')
	{
		$questions = [];
		$rows	  = Connection::query("SELECT id FROM _escuela_question WHERE chapter = '$test_id' ORDER BY xorder;");
		if( ! is_array($questions)) $questions = [];

		foreach($rows as $i => $q)
		{
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
	 * @return bool
	 */
	private function getQuestion($question_id, $email = '', $answer_order = 'rand()')
	{
		$row = Connection::query("SELECT * FROM _escuela_question WHERE id = '$question_id';");
		if(isset($row[0]))
		{
			$q = $row[0];
			$q->answers = $this->getAnswers($question_id, $answer_order);
			$t = $this->isQuestionTerminated($email, $question_id);
			$q->terminated = $t;

			$q->answer_choosen = - 1;
			$a = Connection::query("SELECT answer FROM _escuela_answer_choosen WHERE email = '$email' AND question = '$question_id'");
			if(isset($a[0])) $q->answer_choosen = intval($a[0]->answer);

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
	 */
	private function getAnswers($question_id, $orderby = 'rand()')
	{
		$answers = Connection::query("SELECT * FROM _escuela_answer WHERE question = '{$question_id}' ORDER BY $orderby;");
		if( ! is_array($answers)) $answers = [];

		return $answers;
	}

	/**
	 * Return the total of chapter's questions
	 *
	 * @param $chapter_id
	 * @return int
	 */
	private function getTotalQuestionsOf($chapter_id)
	{
		$r = Connection::query("SELECT count(id) as t FROm _escuela_question WHERE chapter = '$chapter_id';");

		return intval($r[0]->t);
	}

	/**
	 * Return the total of chapter's responses
	 *
	 * @param $email
	 * @param $chapter_id
	 * @return int
	 */
	private function getTotalResponsesOf($email, $chapter_id)
	{
		$r = Connection::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE email = '$email' AND chapter = '$chapter_id';");

		return intval($r[0]->t);
	}

	/**
	 * Check if user finish the test
	 *
	 * @param string  $email
	 * @param integer $test_id
	 * @return type
	 */
	private function isTestTerminated($email, $test_id)
	{
		$total_questions = $this->getTotalQuestionsOf($test_id);
		$total_responses = $this->getTotalResponsesOf($email, $test_id);

		return $total_questions == $total_responses;
	}

	/**
	 * Return TRUE if a chapter was seen by the user
	 *
	 * @param $email
	 * @param $chapter_id
	 * @return bool
	 */
	private function isChapterSeen($email, $chapter_id)
	{
		$r = Connection::query("SELECT count(email) as t FROM _escuela_chapter_viewed WHERE email ='$email' AND chapter = '$chapter_id';");

		return $r[0]->t * 1 > 0;
	}

	/**
	 * Return TRUE if a question is terminated by the user
	 *
	 * @param $email
	 * @param $question_id
	 * @return bool
	 */
	private function isQuestionTerminated($email, $question_id)
	{
		$r = Connection::query("SELECT count(id) as t FROM _escuela_answer_choosen WHERE email = '$email' AND question = '$question_id';");

		return intval($r[0]->t) > 0;
	}

	/**
	 * Helper for clear HTML code
	 *
	 * @param $html
	 * @return mixed
	 */
	private function clearHtml($html)
	{
		$html = str_replace('&nbsp;', ' ', $html);

		do
		{
			$tmp  = $html;
			$html = preg_replace('#<([^ >]+)[^>]*>[[:space:]]*</\1>#', '', $html);
		} while($html !== $tmp);

		return $html;
	}

}

<?php

/**
 * Apretaste
 * 
 * ESCUELA service
 */
class Escuela extends Service
{
    private $connection = null;
    
    /**
     * Singleton connection to db
     * 
     * @author kuma
     * @return Connection 
     */
    private function connection()
    {
        if (is_null($this->connection))
        {
            $this->connection = new Connection();
        }
        
        return $this->connection;
    }
    
    /**
     * Query assistant 
     * 
     * @author kuma
     * @example 
     *      $this->q("SELECT * FROM TABLE"); // (more readable / SQL is autodescriptive)
     * @param string $sql
     * @return array
     */
    private function q($sql)
    {
        return $this->connection()->deepQuery($sql);
    }
    
    /**
     * Function executed when the service is called
     * 
     * @example ESCUELA
     * @param Request
     * @return Response
     */
    public function _main(Request $request)
    {
        $courses = [];
        $sql =
        "SELECT *, 
            (SELECT COUNT(*) FROM _escuela_chapter WHERE _escuela_chapter.course = _escuela_course.id AND _escuela_chapter.xtype = 'CAPITULO') as chapters,
            (SELECT COUNT(*) FROM _escuela_chapter WHERE _escuela_chapter.course = _escuela_course.id AND _escuela_chapter.xtype = 'PRUEBA') as tests,
            (SELECT COUNT(*) FROM _escuela_question WHERE _escuela_question.course = _escuela_course.id) as questions,
            (SELECT COUNT(*) FROM _escuela_answer_choosen WHERE _escuela_answer_choosen.course = _escuela_course.id) as responses,
            (SELECT name FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) as teacher_name,
            (SELECT title FROM _escuela_teacher WHERE _escuela_teacher.id = _escuela_course.teacher) as teacher_title
        FROM _escuela_course WHERE active = 1;";

        $r = $this->q($sql);

        if ($r !== false)
            $courses = $r;

        $response = new Response();
        $response->setResponseSubject("Cursos activos");
        $response->createFromTemplate('basic.tpl', [
            'courses' => $courses
        ]);

        return $response;
    }

    /**
     * Retrieve a course
     * 
     * @example ESCUELA CURSO 2
     * @param Request $request
     */
    public function _curso(Request $request)
    {
        $id = intval($request->query);
        $r = $this->q("SELECT * FROM _escuela_course WHERE id = '$id';");

        if (isset($r[0]))
        {
            $course = $r[0];
            $r = $this->q("SELECT * FROM _escuela_chapter WHERE course = '$id' ORDER BY xorder;");

            $course->chapters = [];
            if ($r !== false)
                $course->chapters = $r;

            $response = new Response();
            $response->setResponseSubject("Curso: {$course->title}");
            $response->createFromTemplate('course.tpl', [
                'course' => $course
            ]);

            return $response;
        } 

        $response = new Response();
        $response->setResponseSubject("Curso no encontrado");
        $response->createFromText("No encontramos el curso para el identificador recibido");
        return $response;
    }

    /**
     * Subservice "capitulo"
     * 
     * @author kuma
     * @example ESCUELA CAPITULO 3
     * @param Request $request
     * @return Response
     */
    public function _capitulo(Request $request)
    {
        $id = intval($request->query);
        $chapter = $this->getChapter($id, $request->email);

        if ($chapter !== false)
        {
            $beforeAfter = $this->getBeforeAfter($chapter);
            $images = $this->getChapterImages($id);
            
            // Log the visit to this chapter
            $this->q("INSERT IGNORE INTO _escuela_chapter_viewed (email, chapter, course) "
                    . "VALUES ('{$request->email}', '{$id}', '{$chapter->course}');");

            $response = new Response();
            $response->setResponseSubject("{$chapter->title}");
            $response->createFromTemplate('chapter.tpl', [
                'chapter' => $chapter,
                'before' => $beforeAfter['before'],
                'after' => $beforeAfter['after']
            ], $images);

            return $response;
        }
        
        $response = new Response();
        $response->setResponseSubject("Capitulo no encontrado");
        $response->createFromText("Capitulo no encontrado");
        return $response;
    }

    /**
     * @example ESCUELA PRUEBA 2
     */
    public function _prueba(Request $request)
    {
        return $this->_capitulo($request);
    }

    /**
     * @example ESCUELA PREGUNTA 12
     */
    public function _pregunta(Request $request)
    {

    }

    /**
     * @example ESCUELA RESPONDER 4
     */
    public function _responder(Request $request)
    {
        $id = intval($request->query);
        $email = $request->email;
        $r = $this->q("SELECT * FROM _escuela_answer WHERE id = '$id';");

        if ($r !== false)
        {
            $answer = $r[0];

            if ($answer->id == $id)
            {
                $sql = "INSERT IGNORE INTO _escuela_answer_choosen (email, answer, date_choosen, chapter, question, course) "
                    . "VALUES ('$email','$id', CURRENT_DATE, '{$answer->chapter}', '{$answer->question}', '{$answer->course}');";

                $this->q($sql);
                
                $test = $this->getChapter($answer->chapter, $request->email);
        
                // check if test was completed
                if ($test->terminated)
                {
                    $response = new Response();
                    $response->setResponseSubject("Prueba completada");

                    /*
                    $sql = "SELECT * FROM _escuela_chapter WHERE id = '{$answer->chapter}';";
                    $r = $this->q($sql);
                    $test = $r[0];

                    // calculate calification

                    $sql_view = 
                    "SELECT question, 
                            chapter, 
                            course, 
                            answer AS answer_choosen, 
                            right_answer, 
                            answer = right_answer AS is_right 
                    FROM (
                        SELECT *, 
                            (SELECT _escuela_question.answer 
                             FROM _escuela_question
                             WHERE _escuela_question.id = _escuela_answer_choosen.question) AS right_answer 
                        FROM _escuela_answer_choosen 
                        WHERE email = '{$request->email}'
                            AND chapter = '{$answer->chapter}'
                    ) q1";

                    $sql = "SELECT sum(is_right) as c FROM ($sql_view) q2;";
                    $r = $this->q($sql);
                    $right_answers = intval($r[0]->c);

                    $sql = "SELECT count(*) as c FROM ($sql_view) q2;";
                    $r = $this->q($sql);
                    $total_answers = $r[0]->c;
                    */
                    $beforeAfter = $this->getBeforeAfter($test);

                    $response->createFromTemplate("test_done.tpl", [
                        'test' => $test, /*
                        'calification' => intval($right_answers / $total_answers * 100),*/
                        'before' => $beforeAfter['before'],
                        'after' => $beforeAfter['after']
                    ]);

                    return $response;
                }
            }
        }
        return new Response();
    }
    
    private function getBeforeAfter($chapter)
    {
        $before = false;
        $after = false;

        $r = $this->q("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = ".($chapter->xorder - 1).";");
        if (isset($r[0])) $before = $r[0];

        $r = $this->q("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = ".($chapter->xorder + 1).";");
        if (isset($r[0])) $after = $r[0];
        
        return [
            'before' => $before,
            'after' => $after
        ];
    }
    
    /**
     * Return a list of chapter's images paths
     * 
     * @param integer $chapter_id
     * @return array
     */
    private function getChapterImages($chapter_id)
    {
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $wwwroot = $di->get('path')['root'];
        
        $imgs = $this->q("SELECT * FROM _escuela_images WHERE chapter = '$chapter_id';");
        
        if ($imgs === false)
            $imgs = [];

        $images = [];

        foreach($imgs as $img)
            $images[] = $wwwroot."/courses/{$img->course}/{$img->chapter}/{$img->id}";
        
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
        
        $r = $this->q("SELECT * FROM _escuela_chapter WHERE id = '$id';");
        
        if (isset($r[0]))
            $chapter = $r[0];
        
        $chapter->questions = $this->getChapterQuestions($id, $email, $answer_order);
        
        $total_questions = count($chapter->questions);
        $total_right = 0;
        foreach($chapter->questions as $i => $q)
            if ($q->is_right) $total_right++;
        
        $chapter->calification = 0;
        if ($total_questions > 0)
            $chapter->calification = intval($total_right / $total_questions * 100);
        
        $chapter->terminated = $this->isTestTerminated($email, $id) && $chapter->xtype == 'PRUEBA';
        
        return $chapter;
    }
    
    private function getChapterQuestions($test_id, $email = '', $answer_order = 'rand()')
    {
        $questions = [];
        $rows = $this->q("SELECT id FROM _escuela_question WHERE chapter = '$test_id' ORDER BY xorder;");
        if (!is_array($questions)) $questions = [];
        
        foreach ($rows as $i => $q)
        {
           $questions[] = $this->getQuestion($q->id, $email, $answer_order);
        }
        
        return $questions;
    }
    
    private function getQuestion($question_id, $email = '', $answer_order = 'rand()')
    {
        $row = $this->q("SELECT * FROM _escuela_question WHERE id = '$question_id';");
        if (isset($row[0]))
        {
            $q = $row[0];
            $q->answers = $this->getAnswers($question_id, $answer_order);
            $t = $this->isQuestionTerminated($email, $question_id);
            $q->terminated = $t;
            
            $q->answer_choosen = -1;
            $a = $this->q("SELECT answer FROM _escuela_answer_choosen WHERE email = '$email' AND question = '$question_id'");
            if (isset($a[0]))
                $q->answer_choosen = intval($a[0]->answer);
            
            $q->is_right = $q->answer_choosen == $q->answer;
            return $q;
        }
        
        return false;            
    }
    
    private function getAnswers($question_id, $orderby = 'rand()')
    {  
        $answers = $this->q("SELECT * FROM _escuela_answer WHERE question = '{$question_id}' ORDER BY $orderby;");
        if (!is_array($answers)) $answers = [];
        return $answers;
    }
           
    private function getTotalQuestionsOf($chapter_id)
    {
        $r = $this->q("SELECT count(*) as t FROm _escuela_question WHERE chapter = '$chapter_id';");
        return intval($r[0]->t);
    }
    
    private function getTotalResponsesOf($email, $chapter_id)
    {
        $r = $this->q("SELECT count(*) as t FROM _escuela_answer_choosen WHERE email = '$email' AND chapter = '$chapter_id';");
        return intval($r[0]->t);
    }
    
    /**
     * Check if user finish the test
     * 
     * @param string $email
     * @param integer $test_id
     * @return type
     */
    private function isTestTerminated($email, $test_id)
    {
        $total_questions = $this->getTotalQuestionsOf($test_id);
        $total_responses = $this->getTotalResponsesOf($email, $test_id);
        return $total_questions == $total_responses;
    }
    
    private function isQuestionTerminated($email, $question_id)
    {
        $r = $this->q("SELECT count(*) as t FROM _escuela_answer_choosen WHERE email = '$email' AND question = '$question_id';");
        return intval($r[0]->t) > 0;
    }
}

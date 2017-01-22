<?php

class Escuela extends Service
{
    /**
     * Function executed when the service is called
     * 
     * @example ESCUELA
     * @param Request
     * @return Response
     */
    public function _main(Request $request)
    {
        $connection = new Connection();
        $courses = [];
        $sql =
        "SELECT *, 
            (SELECT COUNT(*) FROM _escuela_chapter WHERE _escuela_chapter.course = _escuela_course.id AND _escuela_chapter.xtype = 'CAPITULO') as chapters,
            (SELECT COUNT(*) FROM _escuela_chapter WHERE _escuela_chapter.course = _escuela_course.id AND _escuela_chapter.xtype = 'PRUEBA') as tests,
            (SELECT COUNT(*) FROM _escuela_question WHERE _escuela_question.course = _escuela_course.id) as questions,
            (SELECT COUNT(*) FROM _escuela_answer_choosen WHERE _escuela_answer_choosen.course = _escuela_course.id) as responses
        FROM _escuela_course WHERE active = 1;";

        $r = $connection->deepQuery($sql);

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
        $connection = new Connection();

        $r = $connection->deepQuery("SELECT * FROM _escuela_course WHERE id = '$id';");

        if (isset($r[0]))
        {
            $course = $r[0];
            $r = $connection->deepQuery("SELECT * FROM _escuela_chapter WHERE course = '$id' ORDER BY xorder;");

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
     * 
     * @example ESCUELA CAPITULO 3
     * @param Request $request
     */
    public function _capitulo(Request $request)
    {
        $id = intval($request->query);
        $connection = new Connection();
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $wwwroot = $di->get('path')['root'];
        $r = $connection->deepQuery("SELECT * FROM _escuela_chapter WHERE id = '$id';");

        if ($r !== false)
        {
            $chapter = $r[0];
            $before = false;
            $after = false;

            $r = $connection->deepQuery("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = ".($chapter->xorder - 1).";");
            if (isset($r[0]))
            {
                $before = $r[0];
            }

            $r = $connection->deepQuery("SELECT * FROM _escuela_chapter WHERE course = {$chapter->course} AND xorder = ".($chapter->xorder + 1).";");
            if (isset($r[0]))
            {
                $after = $r[0];
            }

            $imgs = $connection->deepQuery("SELECT * FROM _escuela_images WHERE chapter = '$id';");
            if ($imgs === false)
                $imgs = [];

            $images = [];
            foreach($imgs as $img)
            {
                $images[] = $wwwroot."/courses/{$img->course}/{$img->chapter}/{$img->id}";
            }

            $r = $connection->deepQuery("SELECT * FROM _escuela_question WHERE chapter = '$id' ORDER BY xorder;");
            if ( $r !== false)
            {
                $chapter->questions = $r;

                foreach ($chapter->questions as $i => $q)
                {
                    $r = $connection->deepQuery("SELECT * FROM _escuela_answer WHERE question = '{$q->id}}' ORDER BY rand();");
                    if ($r == false) $r = [];
                    $chapter->questions[$i]->answers = $r;
                }
            }

            // Log the visit to this chapter
            $sql = "INSERT IGNORE INTO _escuela_chapter_viewed (email, chapter, course) VALUES ('{$request->email}', '{$id}', '{$chapter->course}');";
            $connection->deepQuery($sql);

            $response = new Response();
            $response->setResponseSubject("{$chapter->title}");
            $response->createFromTemplate('chapter.tpl', [
                'chapter' => $chapter,
                'before' => $before,
                'after' => $after
            ], $images);

            return $response;
        }
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
        $connection = new Connection();
        $r = $connection->deepQuery("SELECT * FROM _escuela_answer WHERE id = '$id';");

        if ($r !== false)
        {
            $answer = $r[0];

            if ($answer->id == $id)
            {
                $sql = "INSERT IGNORE INTO _escuela_answer_choosen (email, answer, date_choosen, chapter, question, course) "
                    . "VALUES ('$email','$id', CURRENT_DATE, '{$answer->chapter}', '{$answer->question}', '{$answer->course}');";

                $connection->deepQuery($sql);

                // check if test was completed

                $sql = "SELECT (SELECT count(*) from _escuela_question WHERE chapter = '{$answer->chapter}')"
                        . " - (SELECT count(*) from _escuela_answer_choosen WHERE chapter = '{$answer->chapter}') as d;";

                $r = $connection->deepQuery($sql);
                $d = intval($r[0]->d);

                if ($d == 0)
                {
                    $response = new Response();
                    $response->setResponseSubject("Prueba completada");

                    $sql = "SELECT * FROM _escuela_chapter WHERE id = '{$answer->chapter}';";
                    $r = $connection->deepQuery($sql);
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
                    $r = $connection->deepQuery($sql);
                    $right_answers = intval($r[0]->c);

                    $sql = "SELECT count(*) as c FROM ($sql_view) q2;";
                    $r = $connection->deepQuery($sql);
                    $total_answers = $r[0]->c;

                    $before = false;
                    $after = false;

                    $r = $connection->deepQuery("SELECT * FROM _escuela_chapter WHERE course = {$test->course} AND xorder = ".($test->xorder - 1).";");
                    if (isset($r[0]))
                    {
                        $before = $r[0];
                    }

                    $r = $connection->deepQuery("SELECT * FROM _escuela_chapter WHERE course = {$test->course} AND xorder = ".($test->xorder + 1).";");
                    if (isset($r[0]))
                    {
                        $after = $r[0];
                    }

                    $response->createFromTemplate("test_done.tpl", [
                        'test' => $test,
                        'calification' => intval($right_answers / $total_answers * 100),
                        'before' => $before,
                        'after' => $after
                    ]);

                    return $response;
                }
            }
        }
        return new Response();
    }
}

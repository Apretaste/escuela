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
                (SELECT COUNT(*) FROM _escuela_chapter WHERE _escuela_chapter.course = _escuela_course.id AND _escuela_chapter.xtype = 'PRUEBA') as tests
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
            
            if ($r !== false)
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
                
                $imgs = $connection->deepQuery("SELECT * FROM _escuela_images WHERE chapter = '$id';");
                if ($imgs === false)
                    $imgs = [];
                
                $images = [];
                foreach($imgs as $img)
                {
                    $images[] = $wwwroot."/courses/{$img->course}/{$img->chapter}/{$img->id}";
                }
                    
                $response = new Response();
                $response->setResponseSubject("{$chapter->title}");
                $response->createFromTemplate('chapter.tpl', [
                    'chapter' => $chapter
                ], $images);

                return $response;
            }
        }
        
        /**
         * @example ESCUELA PRUEBA 2
         */
        public function _prueba(Request $request)
        {
            
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
            
        }
}

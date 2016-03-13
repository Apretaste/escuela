<?php

class RequestTypes
{

}


// use Goutte\Client; // UNCOMMENT TO USE THE CRAWLER OR DELETE

class Escuela extends Service
{
	/**
	 * Function executed when the service is called
	 * 
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request)
	{
		$responseContent = NULL;
		$template = NULL;
		$query = $request->query;
		$available_courses = $this->courses_request();

		$query = empty($query) ? 'lista' : strtolower($query);

		if (empty($query) OR (strtolower($query)=='lista'))
		{
			$query = 'lista';
		}
		else
		{
			$data = explode(' ', $query);
			$course = isset($data[0]) ? $data[0] : NULL;
			$query = 'course';
		}

		// Logic to set response given the query params
		switch ($query)
		{
			case 'lista':
				$responseContent = $available_courses;
				$template = "course_list.tpl";
				break;

			case 'course':
				if (isset($course) && in_array($course, $available_courses['courses']))
				{
					$template = "course_details.tpl";
					$responseContent = $this->course_request($course);
				}
				break;

			default:
				break;
		}

		if (!$responseContent)
		{
			// TODO: error handling.
			die;
		}

		$response = new Response();
		$response->setResponseSubject("ESCUELA");
		$response->createFromTemplate($template, $responseContent);
		return $response;
	}

	/**
	 * Parses the courses list to pass to template.
	 *
	 * @return array
     */
	private function courses_request()
	{
		$courses = $this->get_courses();
		$available_courses = array();

		foreach ($courses as $course)
		{
			$available_courses[] = $course->label;
		}
		$response['courses'] = $available_courses;
		return $response;
	}

	/**
	 * Gets all available courses.
	 *
	 * @param $published
	 * @return Array
     */
	private function get_courses($published=1)
	{
		$connection = new Connection();
		$query_str = "SELECT courses.label FROM courses";
		$query_str = $published ? $query_str. " WHERE `published` = 1" : $query_str;
		$courses = $connection->deepQuery($query_str);
		return $courses;
	}

	/**
	 * Gets specific course giving its label.
	 *
	 * @param $course_label string
	 * @return array
	 */
	private function course_request($course_label)
	{
		$topics = $this->get_course($course_label);
		if (!$topics) { return FALSE; }
		$topic_list = array();
		foreach ($topics as $topic)
		{
			$topic_list[$topic->order] = $topic->title;
		}
		print_r($topic_list);die;
		return $topic_list;
	}

	/**
	 * Gets specific course giving its label.
	 *
	 * @param $course_label string
	 * @return array
	 */
	private function get_course($course_label)
	{
		$connection = new Connection();
		$query_str = "SELECT courses.id FROM courses WHERE `label` = '$course_label'";
		$course = $connection->deepQuery($query_str);
		$topics = NULL;
		if (!empty($course))
		{
			$course_id = $course[0]->id;
			$query_str = "SELECT * FROM topics WHERE `course_id` = '$course_id'";
			$topics = $connection->deepQuery($query_str);
		}
		return $topics;
	}
}

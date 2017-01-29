<h1>Cursos</h1>
{foreach item=item from=$courses}
    {link href="ESCUELA CURSO {$item->id}" caption="{$item->title|capitalize|truncate:100:' ...'}"}
	
    <br/>
    <small>Profesor: <b><i>{$item->teacher_name}, {$item->teacher_title}</i></b></small><br/>
    <p>{$item->content}</p>
    <font color="gray">
    <small>{$item->total_seen} / {$item->total_chapters} cap&iacute;tulo(s)</small>{separator}
    <small>{$item->total_answered} / {$item->total_tests} prueba(s)</small>{separator}
    <small>{$item->total_right} / {$item->total_questions} preguntas </small>{separator}
	<small> {$item->progress} % terminado </small>
    </font>
    {space10}
{/foreach}

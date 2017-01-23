<h1>Cursos</h1>
{foreach item=item from=$courses}
    {link href="ESCUELA CURSO {$item->id}" caption="{$item->title|capitalize|truncate:100:' ...'}"}
    <br/>
    <small>Profesor: <b><i>{$item->teacher_name}, {$item->teacher_title}</i></b></small><br/>
    <p>{$item->content}</p>
    <font color="gray">
    <small>{$item->chapters} cap&iacute;tulo(s)</small>{separator}
    <small>{$item->tests} prueba(s)</small>{separator}
    <small>{$item->questions} pregunta(s)</small>{separator}
    <small>{$item->responses} respuesta(s)</small>
    </font>
    {space10}
{/foreach}

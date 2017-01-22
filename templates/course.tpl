<h1>{$course->title}</h1>
<p>{$course->content}</p>
<h2>Contenido del curso</h2>
<ol>
{foreach item=$chapter from=$course->chapters}
    <li>{link href="ESCUELA CAPITULO {$chapter->id}" caption="{$chapter->title}"}</li>
{/foreach}
</ol>
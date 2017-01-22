<h1>{$course->title}</h1>
<p>{$course->content}</p>
<h2>&Iacute;ndice del curso</h2>
<ol>
{foreach item=$chapter from=$course->chapters}
    <li>{link href="ESCUELA {$chapter->xtype} {$chapter->id}" caption="{$chapter->title}"}</li>
{/foreach}
</ol>
<center>{button href="ESCUELA" caption="Cursos"}</center>
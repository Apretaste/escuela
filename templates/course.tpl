<h1>{$course->title}</h1>
<p>{$course->content}</p>
{space10}
<h2>&Iacute;ndice del curso</h2>
<table width="100%">
    {foreach item=$chapter from=$course->chapters}
        <tr>
            <td style="padding:5px;">{link href="ESCUELA {$chapter->xtype} {$chapter->id}" caption="{$chapter->title}"}</td>
            <td style="padding:5px;" align="right">{if $chapter->terminated} {if $chapter->xtype == 'CAPITULO'} LE&Iacute;DO {else} {$chapter->calification} puntos {/if} {else} PENDIENTE{/if}</td>
        </tr>
    {/foreach}
</table>

{space10}

{if $course->terminated}
    <h2>Curso terminado</h2>
{else}
    <h2>Pregreso: <i>{$course->progress} % terminado</i></h2>
{/if}
<table width="100%">
    <tr>
        <td style="padding:5px;">Cap&iacute;tulos</td>
        <td style="padding:5px;" width="15%" align="right">{$course->total_chapters}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Pruebas</td>
        <td style="padding:5px;" width="15%" align="right">{$course->total_tests}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Pruebas completadas</td>
        <td style="padding:5px;" width="15%" align="right">{$course->total_answered}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Total de preguntas</td>
        <td style="padding:5px;" width="15%" align="right">{$course->total_questions}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Total de respuestas correctas</td>
        <td style="padding:5px;" align="right">{$course->total_right}</td>
    </tr>
    {if $course->calification > 0}
    <tr>
        <td style="padding:5px;">Calificaci&oacute;n</td>
        <td style="padding:5px;" align="right">{$course->calification} puntos</td>
    </tr>
    {/if}
</table>
{space10}
<center>
    {button href="ESCUELA" caption="Cursos" size="small"} 
    {if $course->terminated}
        {if $course->calification >= 80}
            {button href="ESCUELA CERTIFICADO {$course->id}" caption="Certificado" size="small" color="blue"}
        {/if}
    {/if}
</center>
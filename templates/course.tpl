<h1>{$course->title}</h1>
<p align="right"><b>{$course->progress} % terminado</b></p>
<p>{$course->content}</p>

{space10}

<h2>&Iacute;ndice del curso</h2>
<table width="100%">
    {foreach item=$chapter from=$course->chapters}
        <tr>
            <td style="padding:5px;">{link href="ESCUELA {$chapter->xtype} {$chapter->id}" caption="{$chapter->title}"}</td>
            <td style="padding:5px;">{if $chapter->terminated} {if $chapter->xtype == 'CAPITULO'} LE&Iacute;DO {else} TERMINADO {/if} {else} PENDIENTE{/if}</td>
            <td width="15%" style="padding:5px;">{if $chapter->terminated && $chapter->xtype == 'PRUEBA'}{$chapter->calification} puntos{/if}</td>
        </tr>
    {/foreach}
</table>

{space10}

{if $course->terminated}
    <h2>Curso terminado</h2>
{else}
    <h2>Progreso del terminado</h2>
{/if}
<table width="100%">
    <tr>
        <td style="padding:5px;">Cap&iacute;tulos</td>
        <td style="padding:5px;" width="15%">{$course->total_chapters}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Pruebas</td>
        <td style="padding:5px;" width="15%">{$course->total_tests}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Pruebas completadas</td>
        <td style="padding:5px;" width="15%">{$course->total_answered}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Total de preguntas</td>
        <td style="padding:5px;" width="15%">{$course->total_questions}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Total de respuestas correctas</td>
        <td style="padding:5px;">{$course->total_right}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Calificaci&oacute;n</td>
        <td style="padding:5px;">{$course->calification} puntos</td>
    </tr>
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
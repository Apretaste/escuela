{if $course->repeated}
<table width="100%">
    <tr>
    <td align="center" bgcolor="#F6CED8">
    <p><small>Hemos creado nuevas pruebas para que retomes el curso <b>{$course->title}</b>,
    el cual solicitaste repetir. Revisa el &iacute;ndice del curso y pasa
    todas las pruebas nuevamente para conseguir tu certificado. Puedes leerte
    de nuevo el contenido de ser necesario.</small></p>
    </td>
    </tr>
</table>
{space5}
{/if}

<h1>{$course->title}</h1>
<p>{$course->content}</p>
{space10}
<h2>&Iacute;ndice del curso</h2>
<table width="100%">
    {foreach item=$chapter from=$course->chapters}
        <tr>
            <td style="padding:5px;">{link href="ESCUELA {$chapter->xtype} {$chapter->id}" caption="{$chapter->title}"}</td>
            <td style="padding:5px;" align="right">
            {if $chapter->xtype == 'CAPITULO'}
                {if $chapter->terminated}
                    LE&Iacute;DO
                {else}
                    PENDIENTE
                {/if}
            {else}
                {$chapter->calification} puntos
            {/if}
        </tr>
    {/foreach}
</table>

{space10}

{if $course->terminated}
    <h2>Curso terminado</h2>
    <p>Usted ha terminado este curso, felicidades. Descargue su certificado en el bot&oacute;n m&aacute;s abajo.</p>
{else}
    <h2>Progreso en curso</i></h2>
    <p>Lee todos los cap&iacute;tulos y pasa todas las pruebas para recibir tu certificado.</p>
{/if}
<table width="100%">
    <tr>
        <td style="padding:5px;">Cap&iacute;tulos</td>
        <td style="padding:5px;" align="right">{$course->total_seen}/{$course->total_chapters}</td>
    </tr>
    <tr>
        <td style="padding:5px;">Pruebas</td>
        <td style="padding:5px;" align="right">{$course->total_answered}/{$course->total_tests}</td>
    </tr>
    {if $course->calification > 0}
    <tr>
        <td style="padding:5px;">Calificaci&oacute;n</td>
        <td style="padding:5px;" align="right"><nobr>{$course->calification} puntos</nobr></td>
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
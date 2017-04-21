<h1><u>Cursos</u> <span style="text-decoration:none;">Disponibles</span></h1>
{foreach item=item from=$courses}
    {if $item->progress == 100}
        <strike>
        <b>{link style="text-decoration: line-through;" href="ESCUELA CURSO {$item->id}" caption="{$item->title|capitalize|truncate:100:' ...'}"}</b>
        </strike>
    {else}
        {link href="ESCUELA CURSO {$item->id}" caption="{$item->title|capitalize|truncate:100:' ...'}"}
    {/if}

    &nbsp;&nbsp;&nbsp;<font color="gray"><small><b>{$item->progress}% terminado</b></small></font>
    <br/>
    {$item->content}<br/>
    <small>Profesor: <b><i>{$item->teacher_name}, {$item->teacher_title}</i></b></small>
    {space10}
{/foreach}

<h2>Convi&eacute;rtete en Profesor</h2>

<p>Te gustar&iacute;a crear un impacto &uacute;nico en la vida de otros, y hacer que tus conocimientos ayuden a miles de Cubanos? Si es asi, nos encantar&iacute;a que escribieses un curso para nuestra escuela.</p>

<p>Si das clases particulares, nuestra escuela es un Aula Virtual donde puedes evaluar el desempe&ntilde;o de tu grupo. Aprovecha el impulso de las nuevas tecnolog&iacute;as y escribe un curso y comprueba si tus estudiantes aprenden revisando sus Certificados de Aprobaci&oacute;n.</p>

<p>Si deseas redactar un curso, escr&iacute;benos a <a href="mailto:cubasoporte@gmail.com">cubasoporte@gmail.com</a>. </p>

<p>Muchas gracias!</p>
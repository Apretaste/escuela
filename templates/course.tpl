{if $course->repeated}
	<table width="100%"><tr><td align="center" bgcolor="#F6CED8">
		<p><small>Hemos creado nuevas pruebas para que retomes este curso. Pasa todas las pruebas nuevamente; puedes leerte de nuevo el contenido de ser necesario.</small></p>
	</td></tr></table>
	{space5}
{/if}

<h1>{$course->title}</h1>

<p>{$course->content}</p>

<h2>Indice del curso</h2>

<table width="100%">
	{foreach item=$chapter from=$course->chapters}
		<tr>
			<td>{link href="ESCUELA {$chapter->xtype} {$chapter->id}" caption="{$chapter->title}"}</td>
			<td align="right">
			{if $chapter->xtype == 'CAPITULO'}
				{if $chapter->terminated}LEIDO{else}PENDIENTE{/if}
			{else}
				{$chapter->calification} puntos
			{/if}
		</tr>
	{/foreach}
</table>

<h2>Progreso del curso</h2>

<table width="100%">
	<tr>
		<td>Cap&iacute;tulos</td>
		<td align="right">{$course->total_seen}/{$course->total_chapters}</td>
	</tr>
	<tr>
		<td>Pruebas</td>
		<td align="right">{$course->total_answered}/{$course->total_tests}</td>
	</tr>
	{if $course->calification > 0}
	<tr>
		<td>Calificaci&oacute;n</td>
		<td align="right"><nobr>{$course->calification} puntos</nobr></td>
	</tr>
	{/if}
</table>

{space10}

<center>
	{if $course->calification < 80}
		{button href="ESCUELA REPETIR {$course->id}" caption="Repetir" color="red"}
	{else}
		{button href="ESCUELA CERTIFICADO {$course->id}" caption="Certificado"}
	{/if}
	{button href="ESCUELA" caption="Cursos" color="grey"}
</center>

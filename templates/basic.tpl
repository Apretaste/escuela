<h1>Cursos disponibles</h1>

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

<h2>Convi&eacute;rtase en Profesor</h2>
<p>&iquest;Le gustar&iacute;a que sus conocimientos ayuden a miles de Cubanos? Si es as&iacute; por favor considere escribir un curso para nuestra escuela. Si est&aacute; interesado {link href="SOPORTE" caption="escribanos al soporte" desc="Que curso desea escribir?" popup="true"}</p>

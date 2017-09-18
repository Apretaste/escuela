<h1>Curso terminado</h1>
{if $course->calification >=80 }
<p align="justify">Felicidades! Has pasado el curso <strong>{link href="ESCUELA CURSO {$course->id}" caption="{$course->title}"}</strong>
con una nota de <strong>{$course->calification}/100 puntos</strong>, lo cual indica
que tienes el conocimiento y est&aacute;s listo para poner estas ense&ntilde;anzas en la pr&aacute;ctica. </p>

<p align="justify">Como prueba de que has pasado este curso en nuestra escuela, puedes obtener nuestro certificado de terminaci&oacute;n.
	Por favor, nota que nuestra escuela no est&aacute; aprobada para expedir certificados oficiales, as&iacute; que
	aunque este certificado es prueba de tus conocimientos, puede no ser aceptado por empleadores
	ni ser cambiado por cr&eacute;ditos universitarios.</p>
<center>{button href="ESCUELA CERTIFICADO {$course->id}" caption="Certificado"}</center>
{else}
<p align="justify">Usted ha sacado {$course->calification}/100 en el curso {$course->title} y no es suficiente para pasarlo, pero no se preocupe,
eso no es un problema. Estudie nuevamente y vuelva a presentarse para las pruebas.</p>

<center>
	{button href="ESCUELA REPETIR {$course->id}" caption ="Repetir curso"}
</center>
{/if}

{space15}

<h1>Ay&uacute;denos evaluando nuestro curso</h1>

<p align="justify">Gracias por pasar nuestra escuela, por favor d&eacute;jenos saber su opini&oacute;n
sobre este curso en el cuestionario m&aacute;s abajo. Sus opiniones ser&aacute;n enviadas
	an&oacute;nimamente al profesor y servir&aacute;n para mejorar nuestro contenido.</p>
{space5}
{foreach item=item from=$feedback }
	<strong>{$item->text}</strong><br/>
	{foreach $item->answers as $ans}
		{link href="ESCUELA OPINAR {$course->id} {$item->id} {$ans['value']}" caption="{$ans['caption']}"}
		{if not $ans@last}{separator}{/if}
	{/foreach}
	{space5}
{/foreach}

{space10}

{if $popular_courses|@count gt 0}
	<h1>Otros cursos populares</h1>

	{foreach item = item from = $popular_courses}
		{link href="ESCUELA CURSO {$item->id}" caption="{$item->title}"}<br/>
		{$item->content}
		{space5}
	{/foreach}
	{space5}
{/if}

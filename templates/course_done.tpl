{if $course->calification >=80 }
<p>Felicidades! Has pasado el curso {$course->title} con una nota de {$course->calification}/100 puntos, lo cual indica
que tienes el conocimiento y est&aacute;s listo para poner estas ense&ntilde;anzas en la pr&aacute;ctica. </p>
{else}
<p>Usted ha sacado {$course->calification}/100 en el curso {$course->title} y no es suficiente para pasarlo, pero no se preocupe, 
eso no es un problema. Estudie nuevamente y vuelva a presentarse para las pruebas.</p>

<center>{button href="ESCUELA REPETIR {$course->id}" caption ="REPETIR CURSO"}</center>
{/if}

<p>Como prueba de que has pasado este curso en nuestra escuela, adjuntamos nuestro certificado de terminaci&oacute;n.
Por favor nota que nuestra escuela no est&aacute; aprovada para expedir certificados oficiales, as&iacute; que
aunque este certificado es prueba de tus conocimientos, puede no ser aceptado por empleadores
    ni ser cambiado por cr&eacute;ditos universitarios.</p>

<h2>Ayudenos evaluando nuestro curso</h2>

<p>Gracias por pasar nuestra escuela, por favor d&eacute;jenos saber su opini&oacute;n
sobre este curso en el cuestionario m&aacute;s abajo. Sus opiniones ser&aacute;n enviadas
    an&oacute;nimamente al profesor y servir&aacute;n para mejorar nuestro contenido.</p>
{space5}
{foreach item=item from=$feedback }
    <strong>{$item->text}</strong><br/>
    {foreach $item->answers as $ans}
        {link href="ESCUELA OPINAR {$course->id} {$item->id} {$ans}" caption="{$ans}"} 
        {if not $ans@last}{separator}{/if}
    {/foreach}
    {space5}
{/foreach}

{space10}

{if $popular_courses|@count gt 0}
    <h2>Otros cursos populares</h2>

    {foreach item = item from = $popular_courses}
        {link href="ESCUELA CURSO {$item->id}" caption="{$item->title}"}<br/>
        {$item->description}
    {/foreach}
    {space5}
{/if}
<p>Muchas Gracias!</p>

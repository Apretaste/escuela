<h1>Prueba terminada</h1>
<p>Has terminado de responder todas las preguntas de la prueba <b>{$test->title}</b>. Tu calificaci&oacute;n fue de
    {$calification} puntos.</p>

<center>
    {if $before !== false}
        {button href="ESCUELA {$before->xtype} {$before->id}" caption="&lt;&lt; Anterior" size="small" color="blue"} &nbsp;
    {/if}
    {button href="ESCUELA CURSO {$test->course}" caption="&Iacute;ndice" size="small"} &nbsp;
    {if $after !== false}
    {button href="ESCUELA {$after->xtype} {$after->id}" caption="Siguente &gt;&gt;" size="small" color="blue"}
    {/if}
</center>

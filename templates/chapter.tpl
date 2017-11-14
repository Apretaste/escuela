<h1>{$chapter->title}</h1>

{strip}
	{$chapter->content}
{/strip}

{if $chapter->xtype == 'PRUEBA'}
	{if $chapter->terminated}
		<p align="center" style="background-color:#C11414; color:white; padding:5px;">
			<b>Terminada con {$chapter->calification} puntos de 100</b>
		</p>
	{/if}

	{foreach from=$chapter->questions item=$question}
		<b>{$question->title}</b><br/>
		<ol>
			{foreach from = $question->answers item=$answer}
				<li>
					{if $question->terminated}
						{if $question->answer_choosen == $answer->id}
							<b>{$answer->title}</b>
						{else}
							{$answer->title}
						{/if}
						{if $question->answer == $answer->id}
							<span style="color:gray;">&#10004;</span>
						{/if}
					{else}
						{link href="ESCUELA RESPONDER {$answer->id}" caption="{$answer->title}" wait="false"}
					{/if}
				</li>
			{/foreach}
		</ol>
		{space5}
	{/foreach}
{/if}

{space10}

<center>
	{if $chapter->xtype == 'PRUEBA' && $course->all_chapters_finished}
		{button href="ESCUELA CERTIFICADO {$chapter->course}" caption="Evaluar"}
		{button href="ESCUELA CURSO {$chapter->course}" caption="Indice" color="grey"}
	{else}
		{if $before}{button href="ESCUELA {$before->xtype} {$before->id}" caption="&laquo; Anterior" size="small"}{/if}
		{button href="ESCUELA CURSO {$chapter->course}" caption="Indice" size="small" color="grey"}
		{if $after}{button href="ESCUELA {$after->xtype} {$after->id}" caption="Siguente &raquo;" size="small"}{/if}
	{/if}
</center>

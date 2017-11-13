<h1>{$chapter->title}</h1>

{strip}
	{$chapter->content}
{/strip}

{space10}

{if isset($chapter->questions)}
	{if $chapter->xtype == 'PRUEBA' && $chapter->terminated}
		<p align="center" style="color:red;"><font color="red"><b>TERMINADA con {$chapter->calification} puntos de 100</b></font></p>
	{/if}

	<br/>
	{foreach from=$chapter->questions item=$question}
		<b>{$question->title}</b><br/>
		<ol>
			 {foreach from = $question->answers item=$answer}
				 <li>
					 {if $question->terminated}
						 {if $question->answer_choosen == $answer->id}
							 <b>{$answer->title}</b> <font color="gray"><small><i>&lt;&lt; seleccionada </i></small></font>
						 {else}
							 {$answer->title}
						 {/if}
						 {if $question->answer == $answer->id}
							 <font color="gray"><small><i>&lt;&lt; correcta </i></small></font>
						 {/if}

					 {else}
						 {link href="ESCUELA RESPONDER {$answer->id}" caption="{$answer->title}"}
					 {/if}
				 </li>
			 {/foreach}
		 </ol>
	   {space5}
	{/foreach}
	</ul>
{/if}

<center>
	{if $before !== false}
		{button href="ESCUELA {$before->xtype} {$before->id}" caption="&lt;&lt; Anterior" size="small" color="blue"} &nbsp;
	{/if}

	{button href="ESCUELA CURSO {$chapter->course}" caption="&Iacute;ndice" size="small"} &nbsp;

	{if $after !== false}
		{button href="ESCUELA {$after->xtype} {$after->id}" caption="Siguente &gt;&gt;" size="small" color="blue"}
	{/if}
</center>

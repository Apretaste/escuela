<h1>Thanks for creating a service for Apretaste</h1>
<p>We want to say thank you, and we hope you find that using our API is fun and easy.</p>

{space10}

<p>{$label|capitalize}</p>
{space5}

<center>
	<p>{$title|capitalize}</p>
	{space5}
</center>

{foreach from=$topics item=topic}
	Titulo: {link href="ESCUELA {$course_label} {$topic}" caption="{$topic}"}
	{space5}
{/foreach}

{space15}

<center>
	<p><small>You can use all the power of HTML and Smarty to generate this template.</small></p>
	{link href="ESCUELA" caption="La ayuda de Apretaste"}
	{space15}
</center>

{space30}

<p>If you have any questions, please email me at salvi.pascual@gmail.com</p>
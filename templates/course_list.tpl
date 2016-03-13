<h1>Thanks for creating a service for Apretaste</h1>
<p>We want to say thank you, and we hope you find that using our API is fun and easy.</p>

{space10}

{*<p>{$courses|capitalize}</p>*}

{foreach from=$courses item=course_label}
	Titulo: {link href="ESCUELA {$course_label}" caption="{$course_label}"}
	{*Titulo: {button href="AYUDA" caption="La ayuda de Apretaste"}*}
	{*Titulo: {$course_label}<br />*}
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
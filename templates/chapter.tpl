<h1>{$chapter->title}</h1>
{$chapter->content}
{space10}
{if isset($chapter->questions)}
    {foreach from=$chapter->questions item=$question}
       <h2>{$question->title}</h2>
            <ol>
                {foreach from = $question->answers item=$answer}
                    <li>{link href="ESCUELA RESPONDER {$answer->id}" caption="{$answer->title}"}</li>
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

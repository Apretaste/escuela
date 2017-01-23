<h1>{$chapter->title}</h1>
{$chapter->content}
{space10}
{if isset($chapter->questions)}
    {if $chapter->terminated}
        <p align="right"><b>TERMINADA</b> con {$chapter->calification} puntos de 100</p>
    {/if}
    {foreach from=$chapter->questions item=$question}
        <h2 {if $question->answer == $question->answer_choosen} style="color:blue;" {else} style="color:red;" {/if}>{$question->title}</h2>
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

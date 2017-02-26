<h1><u>Cursos</u> <span style="text-decoration:none;">Disponibles</span></h1>
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

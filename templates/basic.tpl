<h1>Cursos</h1>
{foreach item=item from=$courses}
    {link href="ESCUELA CURSO {$item->id}" caption="{$item->title}"}<br/>
    <p>{$item->content}</p>
    <small>{$item->chapters} cap&iacute;tulos</small>{separator}<small>{$item->tests} pruebas</small>
    {space10}
{/foreach}

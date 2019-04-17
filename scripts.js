function showToast(text) {
  M.toast({html: text});
}

function jsUcfirst(string)
{
  return string.charAt(0).toUpperCase() + string.slice(1);
}

$(function () {

  $('select').formSelect();

  $("#btnSearch").click(function () {

    apretaste.send({
      command: 'ESCUELA BUSCAR',
      data: {
        category: $("#category").val(),
        author: $("#author").val(),
        raiting: $("#raiting").val(),
        title: $("#title").val()
      }
    });
  });

  if (typeof data != 'undefined') {
    if (typeof data.category != 'undefined') $('#category option[value="' + data.category + '"]').prop("selected", true);
    if (typeof data.author != 'undefined') $('#author option[value="' + data.author + '"]').prop("selected", true);
    if (typeof data.raiting != 'undefined') $('#author option[value="' + data.raiting + '"]').prop("selected", true);
    if (typeof data.title != 'undefined') $('#title').val(data.title);
    
    $('select').formSelect();
  }

  $('.save').click(() => {
    apretaste.send({
      command: 'ESCUELA PERFIL',
      data: {
        save: true,
        level: $("#level").val(),
        name: $("#name").val()
      },
      redirect: false,
      callback: {
        name: "showToast",
        data: "Sus cambios han sido guardados"
      }
    })
  });

  if (typeof profile != 'undefined') {
    $('#level option[value="' + profile.level + '"]').prop("selected", true);
    $('select').formSelect();
  }

});
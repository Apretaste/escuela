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
    $('#category option[value="' + data.category + '"]').prop("selected", true);
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

});
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
});
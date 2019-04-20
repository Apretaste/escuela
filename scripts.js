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

// submit a test once completed
function submitTest() {
  // variable to save the ID of the responses
  var answers = [];
  var total = 0;

  chapter.questions.forEach(function(e, i){
    total += e.answers.length;
  });

  $('input.answer:checked').each(function() {
     answers.push($(this).val());
  });

  if (answers.length < total){
    M.toast({html: 'Por favor responda todas las preguntas'});
    $("html, body").animate({scrollTop: $(this).offset().top - 100}, 1000);
    answers = [];
    return false;
  } else {

    M.toast({html: 'Enviando sus respuestas...'});

    // send information to the backend
    apretaste.send({
      command: "ESCUELA RESPONDER",
      data: {
        answers: answers
      },
      redirect: false,
      callback: {
        name: "testSent",
        data: '{}'
      }
    });
  }
}

function testSent(data){
  M.toast({html: 'Prueba enviada satisfactoriamente'});

  // display the DONE message
  $('#list').hide();
  $('#msg').show();

  apretaste.send({
    command: "ESCUELA PRUEBA",
    data: {
      query: chapter.id
    }
  })
}

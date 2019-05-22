function formatDate(dateStr) {
  var months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  var date = new Date(dateStr);
  var month = date.getMonth();
  var day = date.getDate().toString().padStart(2, '0');
  return day + ' de ' + months[month] + ' del ' + date.getFullYear();
}

function showToast(text) {
  M.toast({html: text});
}

function showModal(text) {
  // open the modal
  $("#modalText .modal-content").html(text);
  var popup = document.getElementById('modalText');
  var modal = M.Modal.init(popup);
  modal.open();
}

function jsUcfirst(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

$(function () {

  $('select').formSelect();

  $("#btnSearch").click(function () {

    apretaste.send({
      command: 'ESCUELA BUSCAR',
      data: {
        query: {
          category: $("#category").val(),
          author: $("#author").val(),
          raiting: $("#raiting").val(),
          title: $("#title").val()
        },
      }
    });
  });

  if (typeof profile != 'undefined') {

   // $('#level option[value="' + profile.level + '"]').prop("selected", true);

    var provinces = [
      'Pinar del Rio', 'La Habana', 'Artemisa', 'Mayabeque',
      'Matanzas', 'Villa Clara', 'Cienfuegos', 'Sancti Spiritus',
      'Ciego de Avila', 'Camaguey', 'Las Tunas', 'Holguin',
      'Granma', 'Santiago de Cuba', 'Guantanamo', 'Isla de la Juventud'
    ];

    var states = [
      {caption: 'Alabama', value: 'AL'},
      {caption: 'Alaska', value: 'AK'},
      {caption: 'Arizona', value: 'AZ'},
      {caption: 'Arkansas', value: 'AR'},
      {caption: 'California', value: 'CA'},
      {caption: 'Carolina del Norte', value: 'NC'},
      {caption: 'Carolina del Sur', value: 'SC'},
      {caption: 'Colorado', value: 'CO'},
      {caption: 'Connecticut', value: 'CT'},
      {caption: 'Dakota del Norte', value: 'ND'},
      {caption: 'Dakota del Sur', value: 'SD'},
      {caption: 'Delaware', value: 'DE'},
      {caption: 'Florida', value: 'FL'},
      {caption: 'Georgia', value: 'GA'},
      {caption: 'Hawái', value: 'HI'},
      {caption: 'Idaho', value: 'ID'},
      {caption: 'Illinois', value: 'IL'},
      {caption: 'Indiana', value: 'IN'},
      {caption: 'Iowa', value: 'IA'},
      {caption: 'Kansas', value: 'KS'},
      {caption: 'Kentucky', value: 'KY'},
      {caption: 'Luisiana', value: 'LA'},
      {caption: 'Maine', value: 'ME'},
      {caption: 'Maryland', value: 'MD'},
      {caption: 'Massachusetts', value: 'MA'},
      {caption: 'Míchigan', value: 'MI'},
      {caption: 'Minnesota', value: 'MN'},
      {caption: 'Misisipi', value: 'MS'},
      {caption: 'Misuri', value: 'MO'},
      {caption: 'Montana', value: 'MT'},
      {caption: 'Nebraska', value: 'NE'},
      {caption: 'Nevada', value: 'NV'},
      {caption: 'Nueva Jersey', value: 'NJ'},
      {caption: 'Nueva York', value: 'NY'},
      {caption: 'Nuevo Hampshire', value: 'NH'},
      {caption: 'Nuevo México', value: 'NM'},
      {caption: 'Ohio', value: 'OH'},
      {caption: 'Oklahoma', value: 'OK'},
      {caption: 'Oregón', value: 'OR'},
      {caption: 'Pensilvania', value: 'PA'},
      {caption: 'Rhode Island', value: 'RI'},
      {caption: 'Tennessee', value: 'TN'},
      {caption: 'Texas', value: 'TX'},
      {caption: 'Utah', value: 'UT'},
      {caption: 'Vermont', value: 'VT'},
      {caption: 'Virginia', value: 'VA'},
      {caption: 'Virginia Occidental', value: 'WV'},
      {caption: 'Washington', value: 'WA'},
      {caption: 'Wisconsin', value: 'WI'},
      {caption: 'Wyoming', value: 'WY'}
    ];

    provinces.forEach(function (province) {
      $('#province').prepend('<option value=\'' + province.toUpperCase().replace(/\s/g, '_') + '\'>' + province + '</option>');
    });

    states.forEach(function (state) {
      $('#usstate').append('<option value=\'' + state.value + '\'>' + state.caption + '</option>');
    });

    /*
    if (profile.country.toUpperCase() == 'US') {
      $("#province-section").hide();
      $("#usstate-section").show();
    }
    else {
      $("#province-section").show();
      $("#usstate-section").hide();
    }*/

    $('#gender option[value="' + profile.gender.substring(0, 1) + '"]').prop("selected", true);
    $('#sexual_orientation option[value="' + profile.sexual_orientation + '"]').prop("selected", true);
    $('#marital_status option[value="' + profile.marital_status + '"]').prop("selected", true);
    $('#religion option[value="' + profile.religion + '"]').prop("selected", true);
    $('#country option[value="' + profile.country.toUpperCase() + '"]').prop("selected", true);
    $('#province option[value="' + profile.province.toUpperCase().replace(/\s/g, '_') + '"]').prop("selected", true);
    $('#usstate option[value="' + profile.usstate + '"]').prop("selected", true);
    $('#body_type option[value="' + profile.body_type + '"]').prop("selected", true);
    $('#eyes option[value="' + profile.eyes + '"]').prop("selected", true);
    $('#skin option[value="' + profile.skin + '"]').prop("selected", true);
    $('#hair option[value="' + profile.hair + '"]').prop("selected", true);
    $('#highest_school_level option[value="' + profile.highest_school_level + '"]').prop("selected", true);
    $('#occupation option[value="' + profile.occupation + '"]').prop("selected", true);
/*
    $('#country').on('change', function () { // Important! Do not use lambda notation
      if ($(this).val() == 'US') {
        $("#province-section").hide();
        $("#usstate-section").show();
      }
      else {
        $("#province-section").show();
        $("#usstate-section").hide();
      }
    });
*/

    var date = new Date();
    var today = '12/31/' + date.getFullYear();

    $('.datepicker').datepicker({
      format: 'd/mm/yyyy',
      defaultDate: new Date(profile.date_of_birth),
      setDefaultDate: true,
      selectMonths: true, // Creates a dropdown to control month
      selectYears: 15, // Creates a dropdown of 15 years to control year,
      max: true,
      today: 'Hoy',
      clear: 'Limpiar',
      close: 'Aceptar'
    });

    profile.date_of_birth = $('#date_of_birth').val();

    $('.save').click(function () {
      var names = [
        'first_name', 'last_name', 'date_of_birth', 'country', 'province',
        'usstate', 'gender', 'highest_school_level', 'occupation', 'level'
      ];

      var data = {save: true, query: {level: $('#level').val()}};

      names.forEach(function (prop) {
        if ($('#' + prop).val() != profile[prop] && $('#' + prop).val() != null) {
          data.query[prop] = $('#' + prop).val();
        }
      });

      if (!$.isEmptyObject(data)) {
        return apretaste.send({
          "command": "ESCUELA PERFIL",
          "data": data,
          "redirect": false,
          "callback": {
            "name": "showToast",
            "data": "Sus cambios han sido guardados"
          }
        });
      }
      else {
        showToast("Usted no ha hecho ningun cambio");
      }
    });

    $('select').formSelect();
  }


  if (typeof data != 'undefined' && data != null) {
    //data = data.query;
    if (typeof data.category != 'undefined') {
      $('#category option[value="' + data.category + '"]').prop("selected", true);
    }
    if (typeof data.author != 'undefined') {
      $('#author option[value="' + data.author + '"]').prop("selected", true);
    }
    if (typeof data.raiting != 'undefined') {
      $('#raiting option[value="' + data.raiting + '"]').prop("selected", true);
    }
    if (typeof data.title != 'undefined') {
      $('#title').val(data.title);
    }

    $('select').formSelect();
  }

  // after render
  $(".link").attr('href', '#!');
  $(".link-simple").click(function(){
    var q = null;
    eval('q = ' + $(this).attr('data-query'));
    apretaste.send({
      command: $(this).attr('data-command'),
      data: {
        query: q
      }
    });
  });

  $(".star-link").click(function(){
    var q = null;
    eval('q = ' + $(this).attr('data-query'));

    apretaste.send({
      command: 'ESCUELA CALIFICAR',
      data: {
        query: q
      },
      redirect: false,
      callback: {
        name: "showToast",
        data: "Su opinion ha sido enviada"
      }
    });

  });

});

// submit a test once completed
function submitTest() {
  // variable to save the ID of the responses
  var answers = [];

  $('input.answer:checked').each(function () {
    answers.push($(this).val());
  });

  if (answers.length < chapter.questions.length) {
    M.toast({html: 'Por favor responda todas las preguntas'});
    $("html, body").animate({scrollTop: $(this).offset().top - 100}, 1000);
    answers = [];
    return false;
  }
  else {

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

function testSent(data) {
  M.toast({html: 'Prueba enviada satisfactoriamente'});

  // display the DONE message
  $('#list').hide();
  $('#msg').show();

  apretaste.send({
    command: "ESCUELA PRUEBA",
    data: {
      query: chapter.id
    }
  });
}

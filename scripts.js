"use strict";

var education = {
	'PRIMARIO': 'Primario',
	'SECUNDARIO': 'Secundario',
	'TECNICO': 'Técnico',
	'UNIVERSITARIO': 'Universitario',
	'POSTGRADUADO': 'Postgraduado',
	'DOCTORADO': 'Doctorado',
	'OTRO': 'Otro'
};
var occupation = {
	'AMA_DE_CASA': 'Ama de casa',
	'ESTUDIANTE': 'Estudiante',
	'EMPLEADO_PRIVADO': 'Empleado Privado',
	'EMPLEADO_ESTATAL': 'Empleado Estatal',
	'INDEPENDIENTE': 'Trabajador Independiente',
	'JUBILADO': 'Jubilado',
	'DESEMPLEADO': 'Desempleado'
};
var provinces = {
	'PINAR_DEL_RIO': 'Pinar del Río',
	'LA_HABANA': 'La Habana',
	'ARTEMISA': 'Artemisa',
	'MAYABEQUE': 'Mayabeque',
	'MATANZAS': 'Matanzas',
	'VILLA_CLARA': 'Villa Clara',
	'CIENFUEGOS': 'Cienfuegos',
	'SANCTI_SPIRITUS': 'Sancti Spiritus',
	'CIEGO_DE_AVILA': 'Ciego de Ávila',
	'CAMAGUEY': 'Camagüey',
	'LAS_TUNAS': 'Las Tunas',
	'HOLGUIN': 'Holguín',
	'GRANMA': 'Granma',
	'SANTIAGO_DE_CUBA': 'Santiago de Cuba',
	'GUANTANAMO': 'Guantánamo',
	'ISLA_DE_LA_JUVENTUD': 'Isla de la Juventud'
};

function formatDate(dateStr) {
	var months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
	var date = new Date(dateStr);
	var month = date.getMonth();
	var day = date.getDate().toString().padStart(2, '0');
	return day + ' de ' + months[month] + ' del ' + date.getFullYear();
}

function showToast(text) {
	M.toast({
		html: text
	});
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
	$('.modal').modal();
	$('select').formSelect();
	$('.tabs').tabs();

	// after render
	$(".link").attr('href', '#!');
	$(".link-simple").click(function () {
		var q = null;
		eval('q = ' + $(this).attr('data-query'));
		apretaste.send({
			command: $(this).attr('data-command'),
			data: {query: q}
		});
	});

	$('.fixed-action-btn').floatingActionButton({
		direction: 'left',
		hoverEnabled: false
	});

	$("#btnSearch").click(function () {
		apretaste.send({
			command: 'ESCUELA',
			data: {
				query: {
					category: $("#category").val(),
					author: $("#author").val(),
					raiting: $("#raiting").val(),
					title: $("#title").val()
				}
			}
		});
	});

	if (typeof data != 'undefined' && data != null) {
		//data = data.query;
		if (typeof data.category != 'undefined') {
			$('#category option[value="' + data.category + '"]').prop("selected", true);
		}

		if (typeof data.author != 'undefined') {
			var element = $('#author option[value="' + data.author + '"]');
			element.prop("selected", true);
			$('#authorChip').html('Autor: ' + element.html());
		}

		if (typeof data.raiting != 'undefined') {
			$('#raiting option[value="' + data.raiting + '"]').prop("selected", true);
		}

		if (typeof data.title != 'undefined') {
			$('#title').val(data.title);
		}

		$('select').formSelect();
	}

	$(".star-link").click(function () {
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
		$("#rate-stars").hide();
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
		M.toast({
			html: 'Por favor responda todas las preguntas'
		});
		$("html, body").animate({
			scrollTop: $(this).offset().top - 100
		}, 1000);
		answers = [];
		return false;
	} else {
		M.toast({
			html: 'Enviando sus respuestas...'
		});

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
	M.toast({
		html: 'Prueba enviada satisfactoriamente'
	});

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

// POLYFILL


if (!String.prototype.padStart) {
	String.prototype.padStart = function padStart(targetLength, padString) {
		targetLength = targetLength >> 0; //truncate if number or convert non-number to 0;

		padString = String(typeof padString !== 'undefined' ? padString : ' ');

		if (this.length > targetLength) {
			return String(this);
		} else {
			targetLength = targetLength - this.length;

			if (targetLength > padString.length) {
				padString += padString.repeat(targetLength / padString.length); //append to original to ensure we are longer than needed
			}

			return padString.slice(0, targetLength) + String(this);
		}
	};
}

if (!String.prototype.padEnd) {
	String.prototype.padEnd = function padEnd(targetLength, padString) {
		targetLength = targetLength >> 0; //floor if number or convert non-number to 0;

		padString = String(typeof padString !== 'undefined' ? padString : ' ');

		if (this.length > targetLength) {
			return String(this);
		} else {
			targetLength = targetLength - this.length;

			if (targetLength > padString.length) {
				padString += padString.repeat(targetLength / padString.length); //append to original to ensure we are longer than needed
			}

			return String(this) + padString.slice(0, targetLength);
		}
	};
}

function _typeof(obj) {
	if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
		_typeof = function _typeof(obj) {
			return typeof obj;
		};
	} else {
		_typeof = function _typeof(obj) {
			return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
		};
	}
	return _typeof(obj);
}

if (!Object.keys) {
	Object.keys = function () {
		'use strict';

		var hasOwnProperty = Object.prototype.hasOwnProperty,
			hasDontEnumBug = !{
				toString: null
			}.propertyIsEnumerable('toString'),
			dontEnums = ['toString', 'toLocaleString', 'valueOf', 'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable', 'constructor'],
			dontEnumsLength = dontEnums.length;

		return function (obj) {
			if (_typeof(obj) !== 'object' && (typeof obj !== 'function' || obj === null)) {
				throw new TypeError('Object.keys called on non-object');
			}

			var result = [],
				prop,
				i;

			for (prop in obj) {
				if (hasOwnProperty.call(obj, prop)) {
					result.push(prop);
				}
			}

			if (hasDontEnumBug) {
				for (i = 0; i < dontEnumsLength; i++) {
					if (hasOwnProperty.call(obj, dontEnums[i])) {
						result.push(dontEnums[i]);
					}
				}
			}

			return result;
		};
	}();
}

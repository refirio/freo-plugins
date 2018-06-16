/*********************************************************************

 freo | フォーム管理プラグイン (2013/12/25)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//文字を取得
function get_value(form, name) {
	var value = null;

	if ($(form).find('textarea[name="' + name + '"]').size() > 0) {
		value = $(form).find('textarea[name="' + name + '"]').val();
	} else if ($(form).find('select[name="' + name + '"]').size() > 0) {
		value = $(form).find('select[name="' + name + '"]').val();
	} else if ($(form).find('input[type=checkbox][name="' + name + '"]:checked').size() > 0) {
		value = $(form).find('input[type=checkbox][name="' + name + '"]:checked').val();
	} else if ($(form).find('input[type=radio][name="' + name + '"]:checked').size() > 0) {
		value = $(form).find('input[type=radio][name="' + name + '"]:checked').val();
	} else if ($(form).find('input[type=text][name="' + name + '"]').size() > 0) {
		value = $(form).find('input[type=text][name="' + name + '"]').val();
	}

	return value;
}

//数値を取得
function get_number(form, name) {
	value = get_value(form, name);

	if (isNaN(value)) {
		value = 0;
	}

	return value;
}

//数値にコンマを付加
function number_format(number) {
	var value = new String(number).replace(/,/g, '');

	while (value != (value = value.replace(/^(-?\d+)(\d{3})/, '$1,$2')));

	return value;
}

//価格を取得
function calculate() {
	var form = $('form#plugin_form');

	var total = 0;
	form.find('input[name^="plugin_form[__price]"]').each(function() {
		var matches = $(this).attr('name').match(/^plugin_form\[__price\](\[[^\]]+\])(\[[^\]]+\])*/);

		var id    = '';
		var value = '';

		if (matches[1]) {
			id = matches[1].replace(/(\[|\])/g, '');
		}
		if (matches[2]) {
			value = matches[2].replace(/(\[|\])/g, '');
		}

		var integer = $(this).val();

		if (form.find('select[name^="plugin_form[' + id + ']"]').size() > 0) {
			if (form.find('select[name^="plugin_form[' + id + ']"]').val() == value) {
				var number = get_number('form#plugin_form', 'plugin_form[count][' + id + ']');
				if (number == null) {
					number = 1;
				}

				total += integer * number;
			}
		} else if (form.find('input[type=checkbox][name^="plugin_form[' + id + '][]"]').size() > 0) {
			form.find('input[type=checkbox][name^="plugin_form[' + id + '][]"]').each(function() {
				if ($(this).prop('checked') && $(this).val() == value) {
					var number = get_number('form#plugin_form', 'plugin_form[count][' + id + '][' + value + ']');
					if (number == null) {
						number = 1;
					}

					total += integer * number;
				}
			});
		} else if (form.find('input[type=radio][name^="plugin_form[' + id + ']"]').size() > 0) {
			if (form.find('input[type=radio][name^="plugin_form[' + id + ']"]:checked').val() == value) {
				var number = get_number('form#plugin_form', 'plugin_form[count][' + id + ']');
				if (number == null) {
					number = 1;
				}

				total += integer * number;
			}
		} else if (form.find('input[name^="plugin_form[' + id + ']"]').size() > 0) {
			if (form.find('input[name^="plugin_form[' + id + ']"]').val() != '') {
				var number = get_number('form#plugin_form', 'plugin_form[count][' + id + ']');
				if (number == null) {
					number = 1;
				}

				total += integer * number;
			}
		}
	});
	if (form.find('[name="plugin_form[set]"]').size() > 0) {
		total *= form.find('[name="plugin_form[set]"]').val() - 0;
	}
	if (form.find('[name="plugin_form[price]"]').size() > 0) {
		total += form.find('[name="plugin_form[price]"]').val() - 0;
	}
	if (isNaN(total)) {
		$('#plugin_form_price').html('-');
	} else {
		$('#plugin_form_price').html(number_format(total));
	}

	return;
}

//価格を表示
if ($('#plugin_form_price').size()) {
	$('form#plugin_form input, select, textarea').change(function() {
		calculate();
	});
	calculate();
}

//入力内容を送信
$('form#plugin_form input[type=submit]').click(function() {
	var target = $(this);

	$.fn.subwindow({
		width: 600,
		height: 600,
		close: '×',
		fade: 300
	});
	$.fn.subwindow.callback = function() {
		target.closest('form').attr('target', 'subwindow_content').submit();
	};
	$.fn.subwindow.open('/', 'メール送信');

	return false;
});

/*********************************************************************

 freo | 漫画表示プラグイン (2013/01/26)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

$(document).ready(function() {
	var matches = null;
	var first   = 0;
	var last    = 0;

	//最初のページを取得
	matches = $('a#first').attr('href').match(/#page-(\d+)/);
	if (matches && matches[1]) {
		first = parseInt(matches[1]);
	}

	//最後のページを取得
	matches = $('a#last').attr('href').match(/#page-(\d+)/);
	if (matches && matches[1]) {
		last = parseInt(matches[1]);
	}

	//終了ページの有無を確認
	var end;
	if (media_comic_end == 'on') {
		end = 1;
	} else {
		end = 0;
	}

	//ステータスを表示
	var current;
	if (media_comic_cover == true) {
		current = '';
	} else {
		var show;
		if (media_comic_columns == 2) {
			show = '1-2';
		} else {
			show = '1';
		}

		current = ' / <em>' + show + '</em>ページ目を表示';
	}

	$('#status').html('全<em>' + media_comic_all + '</em>ページ' + current);

	//ページを移動
	$('div#menu a, div#media_comic a').click(function() {
		if ($(this).attr('rel') == 'external') {
			return true;
		}

		var page = 0;

		matches = $(this).attr('href').match(/#page-(\d+)/);
		if (matches && matches[1]) {
			page = parseInt(matches[1]);
		}

		var previous;
		var next;

		if (page > 0) {
			previous = page - 1;
		} else {
			previous = 0;
		}

		if (page < media_comic_to) {
			next = page + 1;
		} else {
			next = media_comic_to;
		}

		$('a#previous').attr('href', '#page-' + previous);
		$('a#next').attr('href', '#page-' + next);

		$('html, body').animate({
			scrollTop: $('#page-' + page).offset().top
		}, 0);

		var current;

		if (media_comic_cover == true) {
			if (page < media_comic_to + 1 - end && page > media_comic_from && page <= media_comic_to - media_comic_all % 2 - end) {
				var show;

				if (media_comic_columns == 2 && (page != media_comic_to - end || media_comic_all % 2 == 0)) {
					show = (page * media_comic_columns - 1) + '-' + (page * media_comic_columns);
				} else {
					show = page * media_comic_columns;
				}

				current = ' / <em>' + show + '</em>ページ目を表示';
			} else {
				current = '';
			}
		} else {
			if (page < media_comic_to + 1 - end) {
				var show;

				if (media_comic_columns == 2 && (page != media_comic_to - end || media_comic_all % 2 == 0)) {
					show = (page * media_comic_columns + 1) + '-' + (page * media_comic_columns + 2);
				} else {
					show = page * media_comic_columns + 1;
				}

				current = ' / <em>' + show + '</em>ページ目を表示';
			} else {
				current = '';
			}
		}

		$('#status').html('全<em>' + media_comic_all + '</em>ページ' + current);

		return false;
	});
});

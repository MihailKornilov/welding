var	zayavSpisok = function(v, id) {
		_filterSpisok(ZAYAV, v, id);
		$('.condLost')[(ZAYAV.find ? 'add' : 'remove') + 'Class']('hide');
		$.post(AJAX_MAIN, ZAYAV, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('#spisok').html(res.spisok);
			}
		}, 'json');
	},
	zayavEdit = function() {//редактирование заявки с картриджами
		var html =
				'<table id="zayav-add-tab">' +
					'<tr><td class="label">Клиент:' +
						'<td><input type="hidden" id="client_id" value="' + ZAYAV.client_id + '" />' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + ZAYAV.name + '" />' +
					'<tr><td class="label top">Описание:<td><textarea id="about">' + ZAYAV.about + '</textarea>' +
					'<tr><td class="label">Количество:<td><input type="text" id="count" value="' + ZAYAV.count + '" /> шт.' +
					'<tr><td class="label">Адрес:<td><input type="text" id="adres" value="' + ZAYAV.adres + '" />' +
					'<tr><td class="label">Предварительная стоимость:<td><input type="text" id="pre_cost" class="money" value="' + ZAYAV.pre_cost + '" /> руб.' +
				'</table>',
			dialog = _dialog({
				width:500,
				top:30,
				head:'Создание новой заявки',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#client_id').clientSel({add:1});
		$('#name').focus();
		$('#about').autosize();
		function submit() {
			var send = {
				op:'zayav_edit',
				zayav_id:ZAYAV.id,
				client_id:_num($('#client_id').val()),
				name:$('#name').val(),
				about:$('#about').val(),
				count:_num($('#count').val()),
				adres:$('#adres').val(),
				pre_cost:$('#pre_cost').val()
			};
			if(!send.client_id)
				dialog.err('Не указан клиент');
			else if(!send.name) {
				dialog.err('Не указано название');
				$('#name').focus();
			} else if(!send.count) {
				dialog.err('Некорректно указано количество');
				$('#count').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg();
						location.reload();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	};



$(document)
	.on('click', '#zayav .clear', function() {
		$('#find')._search('clear');
		$('#sort')._radio(1);
		$('#desc')._check(0);
		$('#status').rightLink(0);

		ZAYAV.find = '';
		ZAYAV.sort = 1;
		ZAYAV.desc = 0;
		ZAYAV.status = 0;
		zayavSpisok();
	})

	.on('click', '.zayav_unit', function() {
		var id = $(this).attr('val');
		_scroll('set', 'u' + id);
		location.href = URL + '&p=zayav&d=info&id=' + id;
	})
	.on('click', '#zayav-add', function() {
		if(!window.CLIENT)
			CLIENT = {
				id:0,
				name:''
			};
		var html =
				'<table id="zayav-add-tab">' +
					'<tr><td class="label">Клиент:' +
						'<td><input type="hidden" id="client_id" value="' + CLIENT.id + '" />' +
							'<b>' + CLIENT.name + '</b>' +
					'<tr><td class="label">Название:<td><input type="text" id="name" />' +
					'<tr><td class="label top">Описание:<td><textarea id="about"></textarea>' +
					'<tr><td class="label">Количество:<td><input type="text" id="count" value="1" /> шт.' +
					'<tr><td class="label">Адрес:<td><input type="text" id="adres" />' +
					'<tr><td class="label">Предварительная стоимость:<td><input type="text" id="pre_cost" class="money" /> руб.' +
				'</table>',
			dialog = _dialog({
				width:500,
				top:30,
				head:'Создание новой заявки',
				content:html,
				submit:submit
			});
		if(!CLIENT.id)
			$('#client_id').clientSel({add:1});
		$('#name').focus();
		$('#about').autosize();
		function submit() {
			var send = {
				op:'zayav_add',
				client_id:_num($('#client_id').val()),
				name:$('#name').val(),
				about:$('#about').val(),
				count:_num($('#count').val()),
				adres:$('#adres').val(),
				pre_cost:$('#pre_cost').val()
			};
			if(!send.client_id)
				dialog.err('Не указан клиент');
			else if(!send.name) {
				dialog.err('Не указано название');
				$('#name').focus();
			} else if(!send.count) {
				dialog.err('Некорректно указано количество');
				$('#count').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Заявка внесена');
						location.href = URL + '&p=zayav&d=info&id=' + res.id;
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#zayav-info #edit', zayavEdit)
	.on('click', '#zayav-status-button', function() {
		var html =
				'<div id="zayav-status">' +
					(ZAYAV.status != 1 ?
						'<div class="st c1" val="1">' +
							'Ожидает выполнения' +
							'<div class="about">Возобновление работы по заявке.</div>' +
						'</div>'
					: '') +
					(ZAYAV.status != 2 ?
						'<div class="st c2" val="2">' +
							'Выполнено' +
							'<div class="about">' +
								'Заявка выполнена успешно.<br />' +
								'Не забудьте расписать расходы по заявке, проверьте начисления.<br />' +
								'Добавьте напоминание, если необходимо.' +
							'</div>' +
						'</div>'
					: '') +
					(ZAYAV.status != 3 ?
						'<div class="st c3" val="3">' +
							'Заявка отменена' +
							'<div class="about">Отмена заявки по какой-либо причине.</div>' +
						'</div>'
					: '') +
						'<input type="hidden" id="zs-status" />' +
				'</div>',

			dialog = _dialog({
				top:30,
				width:420,
				head:'Изменение статуса заявки',
				content:html,
				butSubmit:'',
				submit:submit
			});
		$('.st').click(function() {
			var t = $(this),
				v = t.attr('val');
				$('#zs-status').val(v);
				submit();
		});


		function submit() {
			var send = {
				op: 'zayav_status',
				zayav_id: ZAYAV.id,
				status: _num($('#zs-status').val())
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Изменения сохранены');
					location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
	})

	.ready(function() {
		if($('#zayav').length) {
			$('#find')
				._search({
					width: 153,
					focus: 1,
					txt: 'Быстрый поиск...',
					enter: 1,
					func: zayavSpisok
				})
				.inp(ZAYAV.find);
			$('#sort')._radio(zayavSpisok);
			$('#desc')._check(zayavSpisok);
			$('#status').rightLink(zayavSpisok);
		}
		if($('#zayav-info').length) {
			$('.a-page').click(function () {
				var t = $(this);
				t.parent().find('.link').removeClass('sel');
				var i = t.addClass('sel').index();
				$('.page:first')[(i ? 'add' : 'remove') + 'Class']('dn');
				$('.page:last')[(!i ? 'add' : 'remove') + 'Class']('dn');
			});
			$('#zayav-action')._dropdown({
				head:'Действие',
				nosel:1,
				spisok:[
					{uid:1, title:'Редактировать данные заявки'},
//					{uid:2, title:'<b>Распечатать квитанцию</b>'},
					{uid:3, title:'Сформировать счёт на оплату'},
//					{uid:4, title:'Изменить статус заявки'},
					{uid:5, title:'Начислить'},
					{uid:6, title:'<b>Принять платёж</b>'},
					{uid:7, title:'Возврат'},
					{uid:8, title:'Изменить расходы по заявке'},
					{uid:9, title:'Новое напоминание'}
				],
				func:function(v) {
					switch(v) {
						case 1: zayavEdit(); break;
						case 3:
							_schetEdit({
								edit:1,
								client_id:ZAYAV.client_id,
								client:ZAYAV.client_link,
								zayav_id:ZAYAV.id
							});
							break;
						case 5: _accrualAdd(); break;
						case 6: _incomeAdd(); break;
						case 7: _refundAdd(); break;
						case 8: _zayavExpenseEdit(); break;
						case 9: _remindAdd(); break;
					}
				}
			});
		}
	});


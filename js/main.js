
$(document)
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
	});

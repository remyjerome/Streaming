$('document').ready(function() {
	$('.note-plus').click(function(){
		$.ajax({
			type: 'get',
			url: Routing.generate('rj_stream_episode_notation:', { saison: {{episode.saison}}, episode: {{episode.episode}},note:1});
			//url:'http://localhost:8888/Streaming/web/app_dev.php/notation/'+{{episode.saison}}+'/'+{{episode.episode}}+'/1',
			beforeSend: function() {
				console.log('Chargement');
				$('.note-plus').addClass('progress-bar-striped active');
			},
			success: function(data) {
				console.log(data.note);
				$('.note-plus').css('width',data.note+'%');
				$('.note-moins').css('width',100 - data.note+'%');
				$('.note-plus').removeClass('progress-bar-striped active');

			}
		});
	});
	$('.note-moins').click(function(){
		$.ajax({
			type: 'get',
			url:'http://localhost:8888/Streaming/web/app_dev.php/notation/2/1/0',
			beforeSend: function() {
				console.log('Chargement');
				$('.note-moins').addClass('progress-bar-striped active');
			},
			success: function(data) {
				console.log(data.note);
				$('.note-plus').css('width',data.note+'%');
				$('.note-moins').css('width',100 - data.note+'%');
				$('.note-moins').removeClass('progress-bar-striped active');
			}
		});
	});
});

//url:'http://localhost:8888/Streaming/web/app_dev.php/notation/'+{{episode.saison}}+'/'+{{episode.episode}}+'/1',
//url:'http://localhost:8888/Streaming/web/app_dev.php/notation/'+{{episode.saison}}+'/'+{{episode.episode}}+'/0',
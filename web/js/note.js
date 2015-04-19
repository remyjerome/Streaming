    $('document').ready(function() {
        var episode = {{ episode.episode|json_encode() }};
        var saison = {{ episode.saison|json_encode() }};
        $('.note-plus').click(function(){
            $.ajax({
                type: 'get',
                
                url: Routing.generate('rj_stream_episode_notation', { saison: saison, episode: episode, note:1}),
                beforeSend: function() {
                    console.log('Chargement');
                    $('.note-plus').addClass('progress-bar-striped active');
                },
                success: function(data) {
                    console.log(data.note);   
                    if(data.note != "")
                    {        
                        $('.note-plus').css('width',data.note+'%');
                        $('.note-moins').css('width',100 - data.note+'%');
                        $('.note-plus').attr('data-original-title', data.nbplus+' votes');
                    }
                    $('.note-plus').removeClass('progress-bar-striped active');

                }
            });
        });
        $('.note-moins').click(function(){
            $.ajax({
                type: 'get',
                url: Routing.generate('rj_stream_episode_notation', { saison: saison, episode: episode, note:0}),
                beforeSend: function() {
                    console.log('Chargement');
                    $('.note-moins').addClass('progress-bar-striped active');
                },
                success: function(data) {
                    console.log(data.note);
                    if(data.note != "")
                    {
                        $('.note-plus').css('width',data.note+'%');
                        $('.note-moins').css('width',100 - data.note+'%');
                        $('.note-moins').attr('data-original-title', data.nbmoins+' votes');
                    }
                    $('.note-moins').removeClass('progress-bar-striped active');
                }
            });
        });
    });
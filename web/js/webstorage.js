if(typeof localStorage!='undefined') 
{
  var nbepisode = localStorage.getItem('episode');
  var bddepisode;
  $.ajax({
                type: 'get',
                
                url: Routing.generate('rj_stream_nbepisode'),
                beforeSend: function() {
                    console.log('Chargement');

                },
                success: function(data) {
                        console.log(data.nbepisode);
                        if(data.nbepisode)
                        {
                            bddepisode = data.nbepisode;
                            if(nbepisode!=null)
                            {
                              nbepisode = parseInt(nbepisode);
                              
                              bddepisode = parseInt(bddepisode);
                              console.log(nbepisode);
                              nbepisode = 42;
                              nbepisode = bddepisode - nbepisode;
                              console.log(nbepisode);
                              if(nbepisode != 0)
                              {
                                $('#badge-ws-episode').text(nbepisode);
                                $('#badge-ws-episode').toggleClass('active');
                              }

                            } 
                            localStorage.setItem('episode',bddepisode);
                        } 
                }
            });

} 
else 
{
  alert("localStorage n'est pas supporté");
}
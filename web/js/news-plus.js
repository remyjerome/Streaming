String.prototype.nl2br = function()
{
    return this.replace(/\n/g, "<br />");
};
function newsplus(e, id){
        $(e).css('display','none');
        
        $.ajax({
                type: 'get',
                
                url: Routing.generate('rj_stream_news_voirplus', { id: id }),
                beforeSend: function() {
                    console.log('Chargement');
                    $('#description-news-'+id).append('<div class="row text-center"><span class="icon-spin6 chargement animate-spin"></span></div>');

                },
                success: function(data) {
                     
                        if(data.description)
                        {
                            var content = $('<p id="description-news-'+id+'" class="text-justify description-news">'+data.description.nl2br()+'</p>').hide();
                            $('#description-news-'+id).replaceWith(content);
                            $('#description-news-'+id).slideDown();
                            $('#image-saison-'+id).hide();
                            $('#image-saison-'+id).slideDown();
                            $('#image-saison-'+id).toggleClass('active');
                            $('#news-moins-'+id).css('display','initial');
                        } 

                        
                    
                }
            });

};
function newsmoins(e, id){
        $(e).css('display','none');
        
        $.ajax({
                type: 'get',
                
                url: Routing.generate('rj_stream_news_voirplus', { id: id }),
                beforeSend: function() {
                    console.log('Chargement');
                    $('#description-news-'+id).append('<div class="row text-center"><span class="icon-spin6 chargement animate-spin"></span></div>');

                },
                success: function(data) {
                     
                        if(data.description)
                        {
                            $('#description-news-'+id).replaceWith('<p id="description-news-'+id+'" class="text-justify description-news">'+data.description.substr(0,456).nl2br()+' ...</p>');
                            $('#image-saison-'+id).toggleClass('active'); 
                            $('#news-plus-'+id).css('display','initial');

                        } 

                        
                    
                }
            });
};
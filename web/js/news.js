$(document).ready(function() 
{
	$('li.active:first').removeClass('active');
	$('li.news').addClass('active');


});
String.prototype.nl2br = function()
{
    return this.replace(/\n/g, "<br />");
};
function newsplus(e, id){
		$(e).css('display','none');
		
		$.ajax({
                type: 'get',
                
                url: 'http://localhost:8888/Streaming/web/app_dev.php/voirplus/'+id,
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
							$('#news-moins-'+id).css('display','initial');
                    	} 

                    	
                    
                }
            });

};
function newsmoins(e, id){
		$(e).css('display','none');
		
		$.ajax({
                type: 'get',
                
                url: 'http://localhost:8888/Streaming/web/app_dev.php/voirplus/'+id,
                beforeSend: function() {
                    console.log('Chargement');
                    $('#description-news-'+id).append('<div class="row text-center"><span class="icon-spin6 chargement animate-spin"></span></div>');

                },
                success: function(data) {
                     
                    	if(data.description)
                    	{
							$('#description-news-'+id).replaceWith('<p id="description-news-'+id+'" class="text-justify description-news">'+data.description.nl2br()+'</p>'); 
							$('#news-plus-'+id).css('display','initial');
                    	} 

                    	
                    
                }
            });
};
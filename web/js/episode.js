$(document).ready(function() 
{
	$('button.hid').click(function(){
		$('div.alert').removeClass('hidden');
		$('button.hid').attr('disabled', 'disabled');
		$('button.hid').addClass('disabled');
		});
	$('li.active:first').removeClass('active');
	$('li.episode').addClass('active');
	$('[data-toggle="tooltip"]').tooltip();
	$( '[data-toggle="tooltip"]' ).hover(
  	function() {
  		$(this).tooltip('show');
  	});


});
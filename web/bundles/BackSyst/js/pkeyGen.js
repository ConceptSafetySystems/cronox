$(document).ready(function()
{
	$('#generate-pk').click(function()
	{
		$('#original-content').hide();
		$('#loading-content').show();
		$.ajax({
		  url: GEN_CALL,
		  success: function( data, textStatus ) {

		    $('#original-content').show();
		    $('#loading-content').hide();

		    if(typeof data.error == 'undefined')
		    {
		    	$('#private-key').text(data.private);
		   		$('#public-key').text(data.public);
		   		
			    $('.keygen-error').hide();
			    alert("Private and Public generated successfully. Please click Save to update your changes"); 
		    }
		    else
		    {
		    	$('.keygen-error').show();
		    	alert("ERROR: " +data.error); 
		    }
		  },
		  error: function(XMLHttpRequest, textStatus, errorThrown)
		  {
		  	$('.keygen-error').show();
		  	alert("Error: " + errorThrown); 
		  }
		});
	});
	
});
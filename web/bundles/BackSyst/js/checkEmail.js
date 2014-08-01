$(document).ready(function()
{
	if(originalEmail.length == 0)
	{
		$('#test-email-link').hide();
	}
	else
	{
		$('#test-email-link').show();
	}

	$('#test-email-link').click(function()
	{
		$('#original-action').hide();
		$('#progress-action').show();
		$.ajax({
		  url: AJAX_CALL,
		  success: function( data, textStatus ) {
		    $('#progress-action').hide();
			$('#original-action').show();

			console.log(data);
		    if(data.success == 1)
		    {	
		    	$('.error-state').hide();
		    	$('.success-state').hide();	   		
			    $('#test-success').show();
		    }
		    else
		    {
		    	$('.error-state').hide();
		    	$('.success-state').hide();
		    	$('#test-error').show();
		    	
		    }
		  },
		  error: function(XMLHttpRequest, textStatus, errorThrown)
		  {
		  	$('.error-state').hide();
	    	$('.success-state').hide();
	    	$('#test-error').show();
		  	alert("Error: " + errorThrown); 
		  }
		});
	});
	
});
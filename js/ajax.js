jQuery(document).ready(function($) {
      $(".clickable").live("click", function(){
         var $this = $(this);
         jQuery('#results').empty();
	 jQuery.ajax({
				url: MyAjax.ajaxurl,
				type: 'POST',
				data: {
				action: 'pagination',
                                id: $this.attr('id')
				},
				dataType: 'html',
				success: function(response) {
				  $("#results").append(response);
				}
			});
      });
      
     $(window).load(function(){
      $('#carousel').flexslider({       
        animation: "slide",
        controlNav: false,
        animationLoop: false,
        slideshow: false,
        itemWidth: 210,
        itemMargin: 5,
        asNavFor: '#slider'
      });

     $('#slider').flexslider({
        animation: "slide",
        controlNav: false,
        animationLoop: false,
        slideshow: false,
        sync: "#carousel",
        start: function(slider){
         $('body').removeClass('loading');
        }
      });
    });
});

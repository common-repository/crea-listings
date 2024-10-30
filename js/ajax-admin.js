jQuery(document).ready(function(){
    jQuery('.view-data').live( "click", function(){
         var element = jQuery(this);
                 var Id = element.attr("id"); 
                  jQuery.ajax({
                  type:'POST',
                  url:Viewajax.ajaxurl,
                  data:{ action: 'view_list' ,
                         content:Id},
                  dataType: 'html',       
                  success: function(response) {
                    if(response =='ok')
                 {
                          
                 } 
                 else{                   
                    jQuery('.wrap').css('display','none');
                    jQuery('.wrap-inner').append(response);
                 }
               } 
              });
     });
});


jQuery(document).ready(function(){
    jQuery('.hide-data').live( "click", function(){      
         var element = jQuery(this);
                 var Id = element.attr("id"); 
                  jQuery.ajax({
                  type:'POST',
                  url:Viewajax.ajaxurl,
                  data:{ action: 'hide_list' ,
                         content:Id},
                  dataType: 'html',       
                  success: function(response) {
                    if(response =='ok')
                 {
                          
                 } 
                 else{                             
                    jQuery('.wrap').css('display','none');
                    jQuery('.wrap-inner').html(' ');
                    jQuery('.wrap-inner').append(response); 
                 }
               } 
              });
     });
});

jQuery(document).ready(function(){
    jQuery('.show-data').live( "click", function(){     
         var element = jQuery(this);
                 var Id = element.attr("id"); 
                  jQuery.ajax({
                  type:'POST',
                  url:Viewajax.ajaxurl,
                  data:{ action: 'show_list' ,
                         content:Id},
                  dataType: 'html',       
                  success: function(response) {
                    if(response =='ok')
                 {
                          
                 } 
                 else{                 
                    jQuery('.wrap').css('display','none');
                    jQuery('.wrap-inner').html(' ');
                    jQuery('.wrap-inner').append(response);          
                   
                 }
               } 
              });
     });
});
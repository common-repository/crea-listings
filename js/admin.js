(function ($) {
	"use strict";
	$(function () {
		jQuery("#clear-form").click(function() {
                    jQuery('#option-form').find("input[type=text], textarea").val("");
                });
	});
}(jQuery));


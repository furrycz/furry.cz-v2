/**
 * AJAX Nette Framwork plugin for jQuery
 *
 * @copyright  Copyright (c) 2009 Jan Marek
 * @license    MIT
 * @link       http://nettephp.com/cs/extras/jquery-ajax
 * @version    0.1
 */

jQuery.extend({
	updateSnippet: function(id, html) {
		$("#" + id).html(html);
	},

	netteCallback: function(data) {
		// redirect
		if (data.redirect) {
			window.location.href = data.redirect;
		}

		// snippets
		if (data.snippets) {
			for (var i in data.snippets) {
				jQuery.updateSnippet(i, data.snippets[i]);
			}
		}
	}
});


jQuery.ajaxSetup({
	success: function (data) {
		jQuery.netteCallback(data);
	},

	dataType: "json"
});



$(function() {
	// apply AJAX unobtrusive way
	$("a.ajax").live("click", function(event) {
		$.get(this.href);

		// show spinner
		$('<div id="ajax-spinner"></div>').css({
			position: "absolute",
			left: event.pageX + 20,
			top: event.pageY + 40

		}).ajaxStop(function() {
			$(this).remove();

		}).appendTo("body");

		return false;
	});
});
(function($){
	$(function () {
		$('.shelf-toggle').each(function(i,e){
			var $e     = $(e);
			var $shelf = $( '#' + $e.attr('data-shelf') );
			var f  = function () {
				$shelf.collapse($e[0].checked ? 'show' : 'hide');
			};
			$e.change(f);
			$shelf.addClass($e[0].checked ? 'collapse.in' : 'collapse');
			$shelf.collapse($e[0].checked ? 'show' : 'hide');
		});
		
		$('.datetimepicker').each(function(i,e){
			var $e  = $(e);
			var fmt = 'YYYY-MM-DD HH:mm:ss ZZ';
			if ($e.attr('data-datetime-format')) {
				fmt = $e.attr('data-datetime-format');
			}
			$e.datetimepicker({ 'format': fmt });
		});
	});
})(jQuery);


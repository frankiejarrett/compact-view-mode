jQuery( document ).ready( function( $ ) {

	$.each( $( '#the-list tr' ), function() {

		minCells( $( this ) );

	} );

	$( '.inline-edit-row' ).on( 'remove', function() {

		var id = $( this ).prop( 'id' ).replace( 'edit-', '' );

		minCells( $( '#post-' + id ) );

	} );

	function minCells( $row ) {

		minDateCell( $row );

		minTaxCells( $row );

	}

	function minDateCell( $row ) {

		$row.find( '.column-date br' ).remove();

		$row.find( '.column-date' ).contents().filter( function() {

			return this.nodeType === Node.TEXT_NODE;

		} ).remove();

	}

	function minTaxCells( $row ) {

		var classes = [ 'column-categories', 'column-tags', 'column-taxonomy-' ];

		$.each( classes, function( k, v ) {

			$( '.fixed th:regex(class, .*' + v + '.*)' ).css( 'width', '10%' );

			var $taxCell = $row.find( 'td:regex(class, .*' + v + '.*)' );

			if ( ! $taxCell.hasClass( 'cvm-compacted' ) ) {

				var $contents = $taxCell.html();

				$taxCell.empty().append( '<div class="cvm-original"></div><div class="cvm-compact"></div>' ).addClass( 'cvm-compacted' ).find( '.cvm-original' ).html( $contents );

			}

			var $taxOriginal = $taxCell.find( '.cvm-original' ).hide(),
			    $taxCompact  = $taxCell.find( '.cvm-compact' ).show();

			var $tax  = $taxOriginal.children( 'a' ),
			    count = ( $tax.length > 0 ) ? '<a href="#" class="cvm-tax-count">' + $tax.length + '</a>' : '&mdash;';

			$taxCompact.html( count ).show();

		});

	}

	$( document ).on( 'click', '.cvm-tax-count', function() {

		$( this ).parent().hide().prev( '.cvm-original' ).show();

		return false;

	});

});

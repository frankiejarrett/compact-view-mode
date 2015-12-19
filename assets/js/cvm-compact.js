jQuery( function( $ ) {

	$.each( $( '#the-list tr' ), function() {

		minCells( $( this ) );

	} );

	$( document ).on( 'remove', 'tr.inline-editor', function() {

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

		var cols = [
			'column-categories',
			'column-tags',
			'column-taxonomy-'
		];

		$.each( cols, function( i, className ) {

			$( '.fixed th.manage-column.' + className ).empty();

		} );

		$.each( cols, function( i, className ) {

			$( '.fixed th:regex(class, .*' + className + '.*)' ).css( 'width', '5.5em' );

			var $cell     = $row.find( 'td:regex(class, .*' + className + '.*)' ),
			    $cellData = $cell.html();

			$cell
				.empty()
				.addClass( 'cvm-compacted' )
				.append( '<div class="cvm-original"></div><div class="cvm-compact"></div>' );

			var $original = $cell.find( '.cvm-original' ).html( $cellData ).hide(),
			    $taxes    = $original.children( 'a' ),
			    count     = ( $taxes.length > 0 ) ? '<a href="#" class="cvm-tax-count">' + $taxes.length + '</a>' : '&mdash;',
			    $compact  = $cell.find( '.cvm-compact' ).html( count ).show();

		} );

	}

	$( document ).on( 'click', '.cvm-tax-count', function() {

		$( this ).parent().hide().prev( '.cvm-original' ).show();

		return false;

	} );

} );
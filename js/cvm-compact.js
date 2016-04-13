jQuery( function( $ ) {

	$.each( $( '#the-list tr' ), function() {

		minCells( $( this ) );

	} );

	$( document ).on( 'remove', 'tr.quick-edit-row', function() {

		var id = $( this ).prop( 'id' ).replace( 'edit-', '' );

		minCells( $( '#post-' . id ) );

	} );

	function minCells( $row ) {

		$( '.fixed th.manage-column.column-author' ).empty();

		minDateCell( $row );

		minTaxCells( $row );

	}

	function minDateCell( $row ) {

		$row.find( '.column-date br' ).remove();

		$row.find( '.column-date' ).contents().filter( function() {

			return 3 === this.nodeType; // Node.TEXT_NODE == 3, IE8 compat

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

			$( '.fixed th:regex(class, .*' + className + '.*)' ).css( 'width', '4em' );

			var $cell     = $row.find( 'td:regex(class, .*' + className + '.*)' ),
			    $cellData = $cell.html();

			$cell
				.empty()
				.addClass( 'cvm-compacted' )
				.append( '<div class="cvm-original"></div><div class="cvm-compact"></div>' );

			var $original = $cell.find( '.cvm-original' ).html( $cellData ).hide(),
			    $taxes    = $original.children( 'a' ),
			    names     = $taxes.slice( 0, 5 ).map( function() { return $( this ).text(); } ).get().join( ', ' ),
			    names     = ( $taxes.length > 5 ) ? names + ' &hellip;' : names,
			    count     = ( $taxes.length > 0 ) ? '<a href="#" class="cvm-tax-count" title="' + names + '">' + $taxes.length + '</a>' : '&mdash;',
			    $compact  = $cell.find( '.cvm-compact' ).html( count ).show();

		} );

	}

	$( document ).on( 'click', '.cvm-tax-count', function() {

		$( this ).parent().hide().prev( '.cvm-original' ).show();

		return false;

	} );

} );

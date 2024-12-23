/* globals newspackNetworkEventLogLabels */
( function( $ ) {
  $( document ).ready( function() {
    const dataColumns = document.querySelectorAll( '.newspack-network-data-column' );
    dataColumns.forEach( function( column ) {
      const button = column.querySelector( 'button' );
      const text = column.querySelector( 'textarea' ).value;
      button.addEventListener( 'click', function( ev ) {
        ev.preventDefault();
        button.textContent = newspackNetworkEventLogLabels.copying;
        navigator.clipboard.writeText( text ).then( function() {
          button.textContent = newspackNetworkEventLogLabels.copied;
          setTimeout( function() {
            button.textContent = newspackNetworkEventLogLabels.copy;
          }, 1000 );
        } );
      } );
    } );
  } );
} )( jQuery );

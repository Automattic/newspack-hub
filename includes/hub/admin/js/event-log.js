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
        button.disabled = true;
        navigator.clipboard.writeText( text ).then( function() {
          button.textContent = newspackNetworkEventLogLabels.copied;
          setTimeout( function() {
            button.textContent = newspackNetworkEventLogLabels.copy;
            button.disabled = false;
          }, 1000 );
        } ).catch( function( err ) {
          console.error( 'Failed to copy: ', err );
          button.disabled = false;
        } );
      } );
    } );
  } );
} )( jQuery );

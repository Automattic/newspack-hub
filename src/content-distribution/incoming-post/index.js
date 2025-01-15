/* globals newspack_network_incoming_post */

/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { globe } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies.
 */
import './style.scss';
import DistributePanel from "../distribute-panel";

const originalUrl = newspack_network_incoming_post.originalUrl;
const unlinked = newspack_network_incoming_post.unlinked;

function IncomingPost() {

	const { createNotice } = useDispatch( 'core/notices' );
	const { lockPostAutosaving, unlockPostAutosaving } = useDispatch( 'core/editor' );
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' );
	const [ isUnLinkedToggling, setIsUnLinkedToggling ] = useState( false );
	const [ isUnLinked, setIsUnLinked ] = useState( false );
	const [ hasInitializedSidebar, setHasInitializedSidebar ] = useState( false );

	const { postId, areMetaBoxesInitialized } = useSelect( select => {
		const {
			getCurrentPostId,
		} = select( 'core/editor' );
		const {
			areMetaBoxesInitialized,
		} = select( 'core/edit-post' );
		return {
			postId: getCurrentPostId(),
			areMetaBoxesInitialized: areMetaBoxesInitialized(),
		};
	} );

	useEffect( () => {
		setIsUnLinked( unlinked );
	}, [ unlinked ] );

	useEffect( () => {
		if ( !hasInitializedSidebar && areMetaBoxesInitialized ) {
			openGeneralSidebar(
				'newspack-network-incoming-post/newspack-network-distribute-panel'
			);
			setHasInitializedSidebar( true ); // We only want to strongarm this once.
		}
	}, [ areMetaBoxesInitialized ] );

	useEffect( () => {
		createNotice(
			'warning',
			isUnLinked
				? sprintf( __( 'Originally distributed from %s.', 'newspack-network' ), originalUrl )
				: sprintf( __( 'Distributed from %s.', 'newspack-network' ), originalUrl ),

			{
				id: 'newspack-network-incoming-post-notice',
			}
		);

		const lockName = 'distributed-incoming-post-lock';
		if ( isUnLinked ) {
			unlockPostAutosaving( lockName );
		} else {
			lockPostAutosaving( lockName );
		}
		// Toggle the CSS overlay.
		document.querySelector( '#editor' )?.classList.toggle( 'newspack-network-incoming-post-linked', !isUnLinked );

	}, [ isUnLinked ] );

	const toggleUnlinkedState = async ( unlinked ) => {
		setIsUnLinkedToggling( true );

		apiFetch( {
			path: `newspack-network/v1/content-distribution/unlink/${ postId }`,
			method: 'POST',
			data: {
				unlinked: unlinked,
			},
		} )
			.then( ( data ) => {
				setIsUnLinked( data.unlinked );
				setIsUnLinkedToggling( false );
			} )
			.catch( ( error ) => createNotice( 'error', error.message ) );
	}

	const toggleUnlinkedClicked = ( unlinked ) => {
		if ( isUnLinked ) {
			// For relinking, we need to save the post (it will be overwritten by the origin post)
			// to avoid the browser warning when reloading.
			wp.data.dispatch( 'core/editor' ).savePost().then( () => {
				// Remove the 'draft saved' notice.
				wp.data.dispatch( 'core/notices' ).removeNotice( 'SAVE_POST_NOTICE_ID' );
				toggleUnlinkedState( unlinked )
					.then( () => window.location.reload() ); // Reload to get the origin post content.
			} );
		} else {
			toggleUnlinkedState( unlinked )
				.then( () => createNotice( 'info', __( 'Post has been unlinked', 'newspack-network' ), {
					type: 'snackbar',
					isDismissible: true,
					autoDismiss: true,
					autoDismissTimeout: 3000,
				} ) );
		}
	};

	return (
		<DistributePanel
			header={
				isUnLinked ? __(
						'This post has been unlinked from the origin post. Edits to the origin post will not update this version.',
						'newspack-network'
					)
					: __(
						'This post is linked to the origin post. Edits to the origin post will update this version.',
						'newspack-network'
					)
			}
			buttons={
				<>
					<Button
						variant="secondary"
						target="_blank"
						href={ originalUrl }
					>
						{ __( 'View origin post', 'newspack-network' ) }
					</Button>
					<Button
						variant={ isUnLinked ? 'primary' : 'secondary' }
						isDestructive={ !isUnLinked }
						disabled={ isUnLinkedToggling }
						onClick={ () => {
							toggleUnlinkedClicked( !isUnLinked );
						} }
					>
						{ isUnLinkedToggling ? (isUnLinked ? __( 'Linking...', 'newspack-network' ) : __( 'Unlinking...', 'newspack-network' )) : (!isUnLinked ? __( 'Unlink from origin post', 'newspack-network' ) : __( 'Relink to origin post', 'newspack-network' )) }
					</Button>
				</>
			}/>
	);
}

registerPlugin( 'newspack-network-incoming-post', {
	render: IncomingPost,
	icon: globe,
} );

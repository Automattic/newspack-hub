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
	const [ isLinkedToggling, setIsLinkedToggling ] = useState( false );
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

	const toggleLinked = () => {
		setIsLinkedToggling( true );
		apiFetch( {
			path: `newspack-network/v1/content-distribution/unlink/${ postId }`,
			method: 'POST',
			data: {
				unlinked: !isUnLinked,
			},
		} ).then( data => {
			setIsUnLinked( data.unlinked );
			createNotice( 'info', __( sprintf( 'Post has been %s.', isUnLinked ? 'unlinked' : 'relinked' ), 'newspack-network' ), {
				type: 'snackbar',
				isDismissible: true,
			} );
		} ).catch( error => {
			createNotice( 'error', error.message );
		} ).finally( () => {
			setIsLinkedToggling( false );
		} );
	}

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
						disabled={ isLinkedToggling }
						onClick={ () => {
							toggleLinked();
						} }
					>
						{  isLinkedToggling ? ( isUnLinked ? __( 'Linking...', 'newspack-network' ) : __( 'Unlinking...', 'newspack-network' )) : (!isUnLinked ? __( 'Unlink from origin post', 'newspack-network' ) : __( 'Relink to origin post', 'newspack-network' )) }
					</Button>
				</>
			}/>
	);
}

registerPlugin( 'newspack-network-incoming-post', {
	render: IncomingPost,
	icon: globe,
} );

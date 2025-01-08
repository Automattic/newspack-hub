/* globals newspack_network_incoming_post */

/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { globe } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies.
 */
import './style.scss';
import DistributePanel from "../distribute-panel";

const originalUrl = newspack_network_incoming_post.original_url;
const unlinkedMetaKey = newspack_network_incoming_post.unlinked_meta_key;

function IncomingPost() {
	const { createNotice } = useDispatch( 'core/notices' );
	const { lockPostSaving, lockPostAutosaving } = useDispatch( 'core/editor' );
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' );
	const [ isLinkedToggling, setIsLinkedToggling ] = useState( false );
	const [ isUnLinked, setIsUnLinked ] = useState( false );

	const { postId, postStatus, postIsUnlinked, isAutosaveLocked, isPostSaveLocked  } = useSelect( select => {
		const {
			getCurrentPostId,
			getCurrentPostAttribute,
			isPostSavingLocked,
			isPostAutosavingLocked,
		} = select( 'core/editor' );
		return {
			postId: getCurrentPostId(),
			postStatus: getCurrentPostAttribute( 'status' ),
			postIsUnlinked: getCurrentPostAttribute( 'meta' )?.[unlinkedMetaKey] || false,
			isPostSaveLocked: isPostSavingLocked(), // These are inital states taht we should honor?
			isAutosaveLocked: isPostAutosavingLocked(),
		};
	} );


	useEffect( () => {
		const lockName = 'distributed-incoming-post-lock';

		if (isUnLinked && !isPostSaveLocked && !isAutosaveLocked) {
			unlockPostSaving( lockName );
			unlockPostAutosaving( lockName );
		} else {
			// Save should not be allowed on a linked post.
			lockPostSaving( lockName );
			lockPostAutosaving( lockName );

			// But we do need to deal with publish/unpublish
			// TODO
		}
	console.log('Locking effects', isUnLinked, isPostSaveLocked, isAutosaveLocked);
	}, [ isUnLinked, postStatus ] );

	useEffect( () => {
		setIsUnLinked( postIsUnlinked );
	}, [ postIsUnlinked ] );


	useEffect( () => {
		createNotice(
			'warning',
			isUnLinked
				? sprintf( __( 'Distributed from %s.', 'newspack-network' ), originalUrl )
				: sprintf( __( 'Originally distributed from %s.', 'newspack-network' ), originalUrl ),
			{
				id: 'newspack-network-incoming-post-notice',
			}
		);

		setTimeout( () => {
			openGeneralSidebar(
				'newspack-network-incoming-post/newspack-network-distribute-panel'
			);
		}, 10 );
	}, [ isUnLinked ] );

	const toggleLinked = () => {
		setIsLinkedToggling( true );
		console.log( isUnLinked ? 'unlinked' : 'linked' );
		apiFetch( {
			path: `newspack-network/v1/content-distribution/unlink/${ postId }`,
			method: 'POST',
			data: {
				unlinked: !isUnLinked,
			},
		} ).then( data => {
			console.log( 'data', data );
			setIsUnLinked( data.unlinked );
			createNotice( 'info', 'yay', {
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
						'This post has been unlinked from the origin post. Edits to the origin post will not update this version',
						'newspack-network'
					)
					: __(
						'This post is linked to the origin post. Edits to the origin post will update this version',
						'newspack-network'
					)
			}
			body={ `isUnLinked: ${ isUnLinked }` }
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
						onClick={ () => {
							toggleLinked();
						} }
					>
						{ !isUnLinked ? __( 'Unlink from origin post', 'newspack-network' ) : __( 'Relink to origin post', 'newspack-network' ) }
					</Button>
				</>
			}/>
	);
}

registerPlugin( 'newspack-network-incoming-post', {
	render: IncomingPost,
	icon: globe,
} );

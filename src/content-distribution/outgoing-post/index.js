/* globals newspack_network_outgoing_post */

/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';
import { sprintf, __, _n } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { CheckboxControl, TextControl, Button } from '@wordpress/components';
import { globe } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies.
 */
import ContentDistributionPanel from '../content-distribution-panel';

const networkSites = newspack_network_outgoing_post.network_sites;
const distributedMetaKey = newspack_network_outgoing_post.distributed_meta;
const postTypeLabel = newspack_network_outgoing_post.post_type_label;

function OutgoingPost() {
	const [ search, setSearch ] = useState( '' );
	const [ isDistributing, setIsDistributing ] = useState( false );
	const [ distribution, setDistribution ] = useState( [] );
	const [ siteSelection, setSiteSelection ] = useState( [] );

	const { postId, postStatus, savedUrls, hasChangedContent, isSavingPost, isCleanNewPost } = useSelect( select => {
		const {
			getCurrentPostId,
			getCurrentPostAttribute,
			hasChangedContent,
			isSavingPost,
			isCleanNewPost,
		} = select( 'core/editor' );
		return {
			postId: getCurrentPostId(),
			postStatus: getCurrentPostAttribute( 'status' ),
			savedUrls: getCurrentPostAttribute( 'meta' )?.[ distributedMetaKey ] || [],
			hasChangedContent: hasChangedContent(),
			isSavingPost: isSavingPost(),
			isCleanNewPost: isCleanNewPost(),
		};
	} );

	useEffect( () => {
		setSiteSelection( [] );
	}, [ postId ] );

	useEffect( () => {
		setDistribution( savedUrls );
		// Create notice if the post has been distributed.
		if ( savedUrls.length > 0 ) {
			createNotice(
				'warning',
				sprintf(
					_n(
						'This %s is distributed to one network site.',
						'This %s is distributed to %d network sites.',
						savedUrls.length,
						'newspack-network'
					),
					postTypeLabel.toLowerCase(),
					savedUrls.length
				),
				{
					id: 'newspack-network-distributed-notice',
				}
			);
		}
	}, [ savedUrls ] );

	const { savePost } = useDispatch( 'core/editor' );
	const { createNotice } = useDispatch( 'core/notices' );

	const sites = networkSites.filter( url => url.includes( search ) );

	const selectableSites = networkSites.filter( url => ! distribution.includes( url ) );

	const isUnpublished = postStatus !== 'publish';

	const isDisabled = isUnpublished || isSavingPost || isDistributing || isCleanNewPost;

	const getFormattedSite = site => {
		const url = new URL( site );
		return url.hostname;
	}

	const distribute = () => {
		setIsDistributing( true );
		apiFetch( {
			path: `newspack-network/v1/content-distribution/distribute/${ postId }`,
			method: 'POST',
			data: {
				urls: siteSelection,
			},
		} ).then( urls => {
			setDistribution( urls );
			setSiteSelection( [] );
			createNotice(
				'info',
				sprintf(
					_n(
						'%s distributed to one network site.',
						'%s distributed to %d network sites.',
						urls.length,
						'newspack-network'
					),
					postTypeLabel,
					urls.length
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		} ).catch( error => {
			createNotice( 'error', error.message );
		} ).finally( () => {
			setIsDistributing( false );
		} );
	}

	return (
		<ContentDistributionPanel
			header={ (
				<>
					{ ! distribution.length ? (
						<p>
							{ isUnpublished ? (
								sprintf( __( 'This %s has not been published yet. Please publish the %s before distributing it to any network sites.', 'newspack-network' ), postTypeLabel.toLowerCase(), postTypeLabel.toLowerCase() )
							) : networkSites.length === 1 ?
								sprintf( __( 'This %s has not been distributed to your network site yet.', 'newspack-network' ), postTypeLabel.toLowerCase() ) :
								sprintf( __( 'This %s has not been distributed to any network sites yet.', 'newspack-network' ), postTypeLabel.toLowerCase() )
							}
						</p>
					) : (
						<p>
							{ sprintf(
								_n(
									'This %s has been distributed to one network site.',
									'This %s has been distributed to %d network sites.',
									distribution.length,
									'newspack-network'
								),
								postTypeLabel.toLowerCase(),
								distribution.length
							) }
						</p>
					) }
					{ networkSites.length > 5 && (
						<TextControl
							__next40pxDefaultSize
							placeholder={ __( 'Search available network sites', 'newspack-network' ) }
							value={ search }
							disabled={ isDisabled }
							onChange={ setSearch }
						/>
					) }
				</>
			) }
			body={ (
				<>
					{ networkSites.length > 1 && selectableSites.length !== 0 && sites.length === networkSites.length && (
						<CheckboxControl
							name="select-all"
							label={ __( 'Select all', 'newspack-network' ) }
							disabled={ isDisabled }
							checked={ siteSelection.length === selectableSites.length }
							indeterminate={ siteSelection.length > 0 && siteSelection.length < selectableSites.length }
							onChange={ checked => {
								setSiteSelection( checked ? selectableSites : [] );
							} }
						/>
					) }
					{ sites.map( siteUrl => (
						<CheckboxControl
							key={ siteUrl }
							label={ getFormattedSite( siteUrl ) }
							disabled={ isDisabled || distribution.includes( siteUrl ) } // Do not allow undistributing a site.
							checked={ siteSelection.includes( siteUrl ) || distribution.includes( siteUrl ) }
							onChange={ checked => {
								const urls = checked ? [ ...siteSelection, siteUrl ] : siteSelection.filter( url => siteUrl !== url );
								setSiteSelection( urls );
							} }
						/>
					) ) }
				</>
			) }
			buttons={ (
				<>
					{ siteSelection.length > 0 && (
						<p>
							{ sprintf(
								_n(
									'One network site selected.',
									'%d network sites selected.',
									siteSelection.length,
									'newspack-network'
								),
								siteSelection.length
							) }
						</p>
					) }
					{ siteSelection.length > 0 && (
						<Button
							variant="secondary"
							disabled={ isDisabled }
							onClick={ () => setSiteSelection( [] ) }
						>
							{ __( 'Clear', 'newspack-network' ) }
						</Button>
					) }
					<Button
						isBusy={ isDistributing }
						variant="primary"
						disabled={ isDisabled || siteSelection.length === 0 }
						onClick={ () => {
							if ( hasChangedContent || isCleanNewPost ) {
								savePost().then( distribute );
							} else {
								distribute();
							}
						} }
					>
						{ isDistributing ? __( 'Distributing...', 'newspack-network' ) : (
							hasChangedContent || isCleanNewPost ?
							__( 'Save & Distribute', 'newspack-network' ) :
							__( 'Distribute', 'newspack-network' )
						) }
					</Button>
				</>
			) }
		/>
	);
}

registerPlugin( 'newspack-network-outgoing-post', {
		render: OutgoingPost,
		icon: globe,
} );

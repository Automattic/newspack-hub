/* globals newspack_network_distribute */

/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';
import { sprintf, __, _n } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginSidebar } from '@wordpress/editor';
import { Panel, PanelBody, CheckboxControl, TextControl, Button } from '@wordpress/components';
import { globe } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies.
 */
import './style.scss';

const networkSites = newspack_network_distribute.network_sites;
const distributedMetaKey = newspack_network_distribute.distributed_meta;

function Distribute() {
	const [ search, setSearch ] = useState( '' );
	const [ isDistributing, setIsDistributing ] = useState( false );
	const [ distribution, setDistribution ] = useState( [] );
	const [ siteSelection, setSiteSelection ] = useState( [] );

	const { postId, savedUrls, hasChangedContent, isSavingPost, isCleanNewPost } = useSelect( select => {
		const {
			getCurrentPostId,
			getCurrentPostAttribute,
			hasChangedContent,
			isSavingPost,
			isCleanNewPost,
		} = select( 'core/editor' );
		return {
			postId: getCurrentPostId(),
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
					__(
						'This post is distributed to %s.',
						'newspack-network'
					),
					savedUrls.slice( 0, -1 ).join( ', ' ) + ( savedUrls.length > 1 ? ' ' + __( 'and', 'newspack-network' ) + ' ' : '' ) + savedUrls.slice( -1 )
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
						'Post distributed with one connection.',
						'Post distributed with %d connections.',
						urls.length,
						'newspack-network'
					),
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
		<PluginSidebar
			name="newspack-network-distribute"
			icon={ globe }
			title={ __( 'Distribute', 'newspack-network' ) }
			className="newspack-network-distribute"
		>
			<Panel>
				<PanelBody className="distribute-header">
					{ ! distribution.length ? (
						<p>
							{ __( 'This post has not been distributed to any connections yet.', 'newspack-network' ) }
						</p>
					) : (
						<p>
							{ sprintf(
								_n(
									'This post has been distributed with one connection.',
									'This post has been distributed with %d connections.',
									distribution.length,
									'newspack-network'
								),
								distribution.length
							) }
						</p>
					) }
					<TextControl
						__next40pxDefaultSize
						placeholder={ __( 'Search available connections', 'newspack-network' ) }
						value={ search }
						disabled={ isSavingPost || isDistributing }
						onChange={ setSearch }
					/>
				</PanelBody>
				<PanelBody className="distribute-body">
					{ selectableSites.length !== 0 && sites.length === networkSites.length && (
						<CheckboxControl
							name="select-all"
							label={ __( 'Select all', 'newspack-network' ) }
							disabled={ isSavingPost || isDistributing }
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
							disabled={ distribution.includes( siteUrl ) || isSavingPost || isDistributing } // Do not allow undistributing a site.
							checked={ siteSelection.includes( siteUrl ) || distribution.includes( siteUrl ) }
							onChange={ checked => {
								const urls = checked ? [ ...siteSelection, siteUrl ] : siteSelection.filter( url => siteUrl !== url );
								setSiteSelection( urls );
							} }
						/>
					) ) }
				</PanelBody>
				<PanelBody className="distribute-footer">
					{ siteSelection.length > 0 && (
						<p>
							{ sprintf(
								_n(
									'One connection selected.',
									'%d connections selected.',
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
							disabled={ isSavingPost || isDistributing }
							onClick={ () => setSiteSelection( [] ) }
						>
							{ __( 'Clear', 'newspack-network' ) }
						</Button>
					) }
					<Button
						isBusy={ isDistributing }
						variant="primary"
						disabled={ siteSelection.length === 0 || isSavingPost || isDistributing }
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
				</PanelBody>
			</Panel>
		</PluginSidebar>
	);
}

registerPlugin( 'newspack-network-distribute', {
		render: Distribute,
		icon: globe,
} );

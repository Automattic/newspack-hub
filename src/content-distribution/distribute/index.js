/* globals newspack_network_distribute */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginSidebar } from '@wordpress/editor';
import { Panel, PanelBody, CheckboxControl, TextControl, Button } from '@wordpress/components';
import { globe } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

function Distribute() {
	const [ search, setSearch ] = useState( '' );
	const [ filteredSites, setFilteredSites ] = useState( newspack_network_distribute.network_sites );

	const { savedUrls, editedUrls } = useSelect( ( select ) => {
		const { getCurrentPostAttribute, getEditedPostAttribute } = select( 'core/editor' );
		const savedMeta = getCurrentPostAttribute( 'meta' );
		const editedMeta = getEditedPostAttribute( 'meta' );
		return {
			savedUrls: savedMeta?.[ newspack_network_distribute.distribute_meta ]?.site_urls || [],
			editedUrls: editedMeta?.[ newspack_network_distribute.distribute_meta ]?.site_urls || [],
		};
	} );

	useEffect( () => {
		setFilteredSites(
			newspack_network_distribute.network_sites.filter( ( url ) => url.includes( search ) )
		);
	}, [ search ] );

	const { editPost, savePost } = useDispatch( 'core/editor' );

	return (
		<PluginSidebar
			name="newspack-network-distribute"
			icon={ globe }
			title={ __( 'Distribute', 'newspack-network' ) }
		>
			<Panel>
				<PanelBody>
					{ ! savedUrls.length && (
						<p>
							{ __( 'This post has not been distributed to any connections yet.', 'newspack-network' ) }
						</p>
					) }
					<TextControl
						placeholder={ __( 'Search available connections', 'newspack-network' ) }
						value={ search }
						onChange={ setSearch }
					/>
					{ filteredSites.length === newspack_network_distribute.network_sites.length && (
						<CheckboxControl
							label={ __( 'Select all', 'newspack-network' ) }
							checked={ editedUrls.length === newspack_network_distribute.network_sites.length }
							indeterminate={ editedUrls.length > 0 && editedUrls.length < newspack_network_distribute.network_sites.length }
							onChange={ checked => {
								const urls = checked ? [ ...newspack_network_distribute.network_sites ] : [ ...savedUrls ];
								editPost( { meta: { [ newspack_network_distribute.distribute_meta ]: { site_urls: urls } } } );
							} }
						/>
					) }
					{ filteredSites.map( siteUrl => (
						<CheckboxControl
							key={ siteUrl }
							label={ siteUrl }
							disabled={ savedUrls.includes( siteUrl ) } // Do not allow undistributing to sites that have been distributed to.
							checked={ editedUrls.includes( siteUrl ) }
							onChange={ checked => {
								const urls = checked ? [ ...editedUrls, siteUrl ] : editedUrls.filter( url => siteUrl !== url );
								editPost( { meta: { [ newspack_network_distribute.distribute_meta ]: { site_urls: urls } } } );
							} }
						/>
					) ) }
					<Button
						variant="primary"
						onClick={ savePost }
					>
						{ __( 'Distribute', 'newspack-network' ) }
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

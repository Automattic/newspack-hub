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

const networkSites = newspack_network_distribute.network_sites;
const distributedMetaKey = newspack_network_distribute.distributed_meta;

function Distribute() {
	const [ search, setSearch ] = useState( '' );
	const [ filteredSites, setFilteredSites ] = useState( networkSites );

	const { savedUrls, editedUrls } = useSelect( select => {
		const { getCurrentPostAttribute, getEditedPostAttribute } = select( 'core/editor' );
		const savedMeta = getCurrentPostAttribute( 'meta' );
		const editedMeta = getEditedPostAttribute( 'meta' );
		return {
			savedUrls: savedMeta?.[ distributedMetaKey ],
			editedUrls: editedMeta?.[ distributedMetaKey ],
		};
	} );

	useEffect( () => {
		setFilteredSites(
			networkSites.filter( url => url.includes( search ) )
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
					{ filteredSites.length === networkSites.length && (
						<CheckboxControl
							label={ __( 'Select all', 'newspack-network' ) }
							checked={ editedUrls.length === networkSites.length }
							indeterminate={ editedUrls.length > 0 && editedUrls.length < networkSites.length }
							onChange={ checked => {
								const urls = checked ? [ ...networkSites ] : [ ...savedUrls ];
								editPost( { meta: { [ distributedMetaKey ]: urls } } );
							} }
						/>
					) }
					{ filteredSites.map( siteUrl => (
						<CheckboxControl
							key={ siteUrl }
							label={ siteUrl }
							disabled={ savedUrls.includes( siteUrl ) } // Do not allow undistributing a site.
							checked={ editedUrls.includes( siteUrl ) }
							onChange={ checked => {
								const urls = checked ? [ ...editedUrls, siteUrl ] : editedUrls.filter( url => siteUrl !== url );
								editPost( { meta: { [ distributedMetaKey ]: urls } } );
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

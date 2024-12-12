/* globals newspack_network_distribute */

/**
 * WordPress dependencies.
 */
import { sprintf, __, _n } from '@wordpress/i18n';
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

	const { savedUrls, editedUrls } = useSelect( select => {
		const { getCurrentPostAttribute, getEditedPostAttribute } = select( 'core/editor' );
		const savedMeta = getCurrentPostAttribute( 'meta' );
		const editedMeta = getEditedPostAttribute( 'meta' );
		return {
			savedUrls: savedMeta?.[ distributedMetaKey ],
			editedUrls: editedMeta?.[ distributedMetaKey ],
		};
	} );

	const { editPost, savePost } = useDispatch( 'core/editor' );

	const selectedConnectionsCount = editedUrls.length - savedUrls.length;

	const sites = networkSites.filter( url => url.includes( search ) );

	return (
		<PluginSidebar
			name="newspack-network-distribute"
			icon={ globe }
			title={ __( 'Distribute', 'newspack-network' ) }
		>
			<Panel>
				<PanelBody>
					{ ! savedUrls.length ? (
						<p>
							{ __( 'This post has not been distributed to any connections yet.', 'newspack-network' ) }
						</p>
					) : (
						<p>
							{ sprintf(
								_n(
									'This post has been distributed with one connection.',
									'This post has been distributed with %d connections.',
									savedUrls.length,
									'newspack-network'
								),
								savedUrls.length
							) }
						</p>
					) }
					<TextControl
						placeholder={ __( 'Search available connections', 'newspack-network' ) }
						value={ search }
						onChange={ setSearch }
					/>
					{ sites.length === networkSites.length && (
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
					{ sites.map( siteUrl => (
						<CheckboxControl
							key={ siteUrl }
							label={ siteUrl }
							disabled={ savedUrls.includes( siteUrl ) } // Do not allow undistributing a site.
							checked={ editedUrls.includes( siteUrl ) || savedUrls.includes( siteUrl ) }
							onChange={ checked => {
								const urls = checked ? [ ...editedUrls, siteUrl ] : editedUrls.filter( url => siteUrl !== url );
								editPost( { meta: { [ distributedMetaKey ]: urls } } );
							} }
						/>
					) ) }
					{ selectedConnectionsCount > 0 && (
						<p>
							{ sprintf(
								_n(
									'One connection selected.',
									'%d connections selected.',
									selectedConnectionsCount,
									'newspack-network'
								),
								selectedConnectionsCount
							) }
						</p>
					) }
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

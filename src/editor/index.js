import { __ } from '@wordpress/i18n';
import { PluginSidebar } from '@wordpress/editor';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { globe } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

function Distribute() {
	return (
		<PluginSidebar
			name="newspack-network-distribute"
			icon={ globe }
			title={ __( 'Distribute', 'newspack-network' ) }
		>
			<Panel>
				<PanelBody>
					<p>
						{ __( 'This post has not been distributed to any connections yet.', 'newspack-network' ) }
					</p>
				</PanelBody>
			</Panel>
		</PluginSidebar>
	);
}

registerPlugin( 'newspack-network-content-distribution', {
		render: Distribute,
		icon: globe,
} );

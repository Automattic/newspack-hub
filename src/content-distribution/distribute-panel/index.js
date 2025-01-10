/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { PluginSidebar } from '@wordpress/editor';
import { Flex, Panel, PanelBody } from '@wordpress/components';
import { globe } from '@wordpress/icons';

/**
 * Internal dependencies.
 */
import './style.scss';

const DistributePanel = ({ header, body, footer, buttons }) => {
	return (
		<PluginSidebar
			name="newspack-network-distribute-panel"
			icon={ globe }
			title={ __( 'Distribute', 'newspack-network' ) }
			className="newspack-network-distribute-panel"
		>
			<Panel>
				<PanelBody className="distribute-panel-header">
					{ header }
				</PanelBody>
				<PanelBody className="distribute-panel-body">
					{ body }
				</PanelBody>
				<PanelBody className="distribute-panel-footer">
					{ footer }
					<Flex direction="column" className="distribute-panel__button-column" gap={ 4 }>
						{ buttons }
					</Flex>
				</PanelBody>
			</Panel>
		</PluginSidebar>
	);
};

export default DistributePanel;

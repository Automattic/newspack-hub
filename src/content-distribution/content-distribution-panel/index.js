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

const ContentDistributionPanel = ({ header, body, footer, buttons }) => {
	return (
		<PluginSidebar
			name="newspack-network-content-distribution-panel"
			icon={ globe }
			title={ __( 'Distribute', 'newspack-network' ) }
			className="newspack-network-content-distribution-panel"
		>
			<Panel>
				<PanelBody className="content-distribution-panel-header">
					{ header }
				</PanelBody>
				<PanelBody className="content-distribution-panel-body">
					{ body }
				</PanelBody>
				<PanelBody className="content-distribution-panel-footer">
					{ footer }
					<Flex direction="column" className="content-distribution-panel__button-column" gap={ 4 }>
						{ buttons }
					</Flex>
				</PanelBody>
			</Panel>
		</PluginSidebar>
	);
};

export default ContentDistributionPanel;

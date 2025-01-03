/* globals newspack_network_incoming_post */

/**
 * WordPress dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { PluginSidebar } from '@wordpress/editor';
import { Panel, PanelBody } from '@wordpress/components';
import { globe } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies.
 */
import './style.scss';

const originalUrl = newspack_network_incoming_post.original_url;
const isLinked = newspack_network_incoming_post.is_linked;

function IncomingPost() {
	const { createNotice } = useDispatch('core/notices');
	const { lockPostSaving, lockPostAutosaving } = useDispatch('core/editor');
	const { openGeneralSidebar } = useDispatch('core/edit-post');

	useEffect(() => {
		lockPostSaving('distributed-incoming-post-lock');
		lockPostAutosaving('distributed-incoming-post-lock');
	});

	useEffect(() => {
		createNotice(
			'warning',
			isLinked
				? sprintf(
						__('Distributed from %s.', 'newspack-network'),
						originalUrl
					)
				: sprintf(
						__(
							'Originally distributed from %s.',
							'newspack-network'
						),
						originalUrl
					),
			{
				id: 'newspack-network-incoming-post-notice',
			}
		);

		// This doesn't work without a timeout for some reason.
		setTimeout(() => {
			openGeneralSidebar(
				'newspack-network-incoming-post/newspack-network-incoming-post'
			);
		}, 10);
	}, []);

	return (
		<PluginSidebar
			name="newspack-network-incoming-post"
			icon={globe}
			title={__('Distribute', 'newspack-network')}
			className="newspack-network-incoming-post"
		>
			<Panel>
				<PanelBody className="distribute-header">
					{isLinked
						? __(
								'This post is linked to the origin post. Edits to the origin post will update this version',
								'newspack-network'
							)
						: __(
								'This post has been unlinked from the origin post. Edits to the origin post will not update this version',
								'newspack-network'
							)}
				</PanelBody>
			</Panel>
		</PluginSidebar>
	);
}

registerPlugin('newspack-network-incoming-post', {
	render: IncomingPost,
	icon: globe,
});

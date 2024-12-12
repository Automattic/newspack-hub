/**
 **** WARNING: No ES6 modules here. Not transpiled! ****
 */
/* eslint-disable import/no-nodejs-modules */
/* eslint-disable @typescript-eslint/no-var-requires */

const getBaseWebpackConfig = require( 'newspack-scripts/config/getWebpackConfig' );
const path = require( 'path' );

module.exports = getBaseWebpackConfig(
	{
		entry: {
      distribute: path.join( __dirname, 'src', 'content-distribution', 'distribute' ),
    },
	}
);

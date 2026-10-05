/**
 * Regenerates languages/animation-addons-for-elementor.pot (PHP + JS strings).
 *
 * Uses `wp` from PATH. Where WP-CLI is not on PATH (Local by Flywheel on
 * Windows), set WP_CLI_PHAR to the wp-cli.phar path and, optionally, PHP_BIN to
 * a php binary; mbstring is enabled for that binary automatically.
 */
const { spawnSync } = require( 'child_process' );
const path = require( 'path' );
const fs = require( 'fs' );

const root = path.resolve( __dirname, '..' );
const args = [
	'i18n',
	'make-pot',
	'.',
	'languages/animation-addons-for-elementor.pot',
	'--slug=animation-addons-for-elementor',
	'--domain=animation-addons-for-elementor',
	'--exclude=node_modules,vendor,dist,assets,tests,.claude,languages',
	'--allow-root',
];

const run = ( cmd, cmdArgs ) =>
	spawnSync( cmd, cmdArgs, { cwd: root, stdio: 'inherit', shell: false } );

let result;
if ( process.env.WP_CLI_PHAR ) {
	const php = process.env.PHP_BIN || 'php';
	const phpArgs = [ '-d', 'memory_limit=2G' ];
	const ext = path.join( path.dirname( php ), 'ext' );
	if ( process.env.PHP_BIN && fs.existsSync( ext ) ) {
		phpArgs.push( '-d', `extension_dir=${ ext }`, '-d', 'extension=mbstring' );
	}
	result = run( php, [ ...phpArgs, process.env.WP_CLI_PHAR, ...args ] );
} else {
	result = run( process.platform === 'win32' ? 'wp.bat' : 'wp', args );
}

if ( result.error || result.status !== 0 ) {
	console.error(
		'\nmake-pot failed. Install WP-CLI (`wp` on PATH) or set WP_CLI_PHAR (and PHP_BIN) to run it.'
	);
	process.exit( result.status || 1 );
}

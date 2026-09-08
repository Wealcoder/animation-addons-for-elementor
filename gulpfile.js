/**
 * Gulp Config.
 * @version 1.0.0
 */

//const app = require( './package.json' );
const gulp = require('gulp');
const babel = require('gulp-babel');
const eslint = require('gulp-eslint');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const {sass} = require("@mr-hope/gulp-sass");
const minifyCSS = require('gulp-clean-css');
const autoprefixer = require('gulp-autoprefixer');
const sourcemaps = require('gulp-sourcemaps');
const mode = require('gulp-mode')();


// Tasks
gulp.task('compile:js', () => {
    return gulp.src([
        'assets/src/js/**/*.js',
        'assets/src/code-snippet/**/*.js',
        'assets/src/notices/**/*.js',
        '!assets/src/js/**/*.min.js',
        '!assets/src/js/utils/*.min.js',
    ])
        .pipe(mode.development(sourcemaps.init({largeFile: true})))
        .pipe(eslint())
        .pipe(mode.development(eslint.format()))
       // .pipe(babel({ presets: [['@babel/preset-env', {modules: false}]] }))
        // .pipe(mode.production(terser()))
        // gulp-js-prettify REMOVED 2026-07-27: its tokenizer predates ES2020
        // and mangles `=>`, `?.`, `??` into invalid syntax (e.g. `=>` becomes
        // `= >`), silently corrupting the built file's entire parse. It ran
        // in dev mode only, so any dev rebuild of a source file using those
        // operators broke it. Cosmetic-only step (babel/terser already
        // handle real transforms elsewhere) — dropping it just means dev
        // output is an unmodified copy of source, same as production always
        // was.
        .pipe(mode.development(sourcemaps.write('/.')))
        .pipe(gulp.dest('assets/js'));
});

// Tasks
gulp.task('minify:js', () => {
    return gulp.src([
        'assets/js/**/*.js',
        '!assets/js/**/*.min.js',
    ])
        .pipe(mode.development(sourcemaps.init({largeFile: true})))
        .pipe(terser())
        .pipe(rename({suffix: '.min'}))
        .pipe(mode.development(sourcemaps.write('/.')))
        .pipe(gulp.dest('assets/js'));
});

gulp.task('compile:scss', () => {
    return gulp.src([
        'assets/src/scss/**/*.scss',
        'assets/src/code-snippet/**/*.scss',
        'assets/src/code-snippet/**/*.css',
        'assets/src/scss/loop-builder/**/*.css',
        'assets/src/notices/**/*.scss',
        'assets/src/notices/**/*.css',
    ])
        .pipe(mode.development(sourcemaps.init({largeFile: true})))
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(mode.development(sourcemaps.write('/.')))
        .pipe(gulp.dest('assets/css'));
});

gulp.task('minify:css', function () {
    return gulp.src([
        'assets/css/**/*.css',
        '!assets/css/**/*.min.css'
    ])
        .pipe(mode.development(sourcemaps.init({largeFile: true})))
        .pipe(minifyCSS())
        .pipe(rename({suffix: '.min'}))
        .pipe(mode.development(sourcemaps.write('/.')))
        .pipe(gulp.dest('assets/css'));
});

// NOTE: atomic widget JS is built by WEBPACK (`npm run build` / `npm run start`),
// which bundles inc/AtomicWidgets/Widgets/*/assets/js/*.js into assets/atomic/js/
// and resolves the `@elementor/frontend-handlers` imports via externals.
// Never raw-copy those sources into assets/atomic/js — unbundled files throw
// "Cannot use import statement outside a module" / "Identifier 'register' has
// already been declared" on the frontend. (The old `compile:atomic-js` task did
// exactly that and was removed 2026-07-20.)

gulp.task('minify:atomic-js', () => {
    return gulp.src([
        'assets/atomic/js/**/*.js',
        '!assets/atomic/js/**/*.min.js',
    ])
        .pipe(mode.development(sourcemaps.init({largeFile: true})))
        .pipe(terser())
        .pipe(rename({suffix: '.min'}))
        .pipe(mode.development(sourcemaps.write('/.')))
        .pipe(gulp.dest('assets/atomic/js'));
});

gulp.task('compile:atomic-scss', () => {
    return gulp.src([
        'inc/AtomicWidgets/Widgets/**/*.scss',
    ])
        .pipe(mode.development(sourcemaps.init({largeFile: true})))
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(rename({dirname: ''})) // Flatten directory structure
        .pipe(mode.development(sourcemaps.write('/.')))
        .pipe(gulp.dest('assets/atomic/css'));
});

gulp.task('minify:atomic-css', function () {
    return gulp.src([
        'assets/atomic/css/**/*.css',
        '!assets/atomic/css/**/*.min.css'
    ])
        .pipe(mode.development(sourcemaps.init({largeFile: true})))
        .pipe(minifyCSS())
        .pipe(rename({suffix: '.min'}))
        .pipe(mode.development(sourcemaps.write('/.')))
        .pipe(gulp.dest('assets/atomic/css'));
});

// compile:atomic-scss flattens every widget's *.scss into assets/atomic/css/
// with dirname stripped, so a relative url("../images/foo.png") in a widget's
// scss resolves to assets/atomic/images/foo.png. Nothing copied that image
// there until now — it worked only incidentally, for widgets whose JS also
// `import`ed the same scss through webpack (which resolves/copies url()
// assets itself). Once that redundant JS import was removed project-wide
// (see CLAUDE.md's SCSS-via-JS cleanup), any widget relying on a local image
// in its CSS (e.g. Btn's mask effect, assets/images/mask-btn.png) lost its
// only working asset path. This mirrors compile:atomic-scss's own
// dirname-flatten so the two line up.
gulp.task('copy:atomic-images', () => {
    return gulp.src([
        'inc/AtomicWidgets/Widgets/**/assets/images/**/*',
    ])
        .pipe(rename({dirname: ''}))
        .pipe(gulp.dest('assets/atomic/images'));
});

// Combined tasks.
gulp.task('buildJs', gulp.series('compile:js', 'minify:js', 'minify:atomic-js'));
gulp.task('buildCss', gulp.series('compile:scss', 'minify:css', 'compile:atomic-scss', 'copy:atomic-images', 'minify:atomic-css'));

gulp.task('build', gulp.series('buildCss', 'buildJs'));

gulp.task('watch', () => new Promise((resolve, reject) => {
    try {
        gulp.watch('assets/src/js/**/*.js', {ignoreInitial: true}, gulp.series('buildJs'));
        gulp.watch('assets/src/scss/**/*.scss', {ignoreInitial: true}, gulp.series('buildCss'));
        gulp.watch('inc/AtomicWidgets/Widgets/**/*.scss', {ignoreInitial: true}, gulp.series('compile:atomic-scss', 'minify:atomic-css'));
        gulp.watch('inc/AtomicWidgets/Widgets/**/assets/images/**/*', {ignoreInitial: true}, gulp.series('copy:atomic-images'));
        resolve();
    } catch (e) {
        reject(e);
    }
}));

const gulpZip = require('gulp-zip').default;
const path = require('path');
// Plugin info
const pluginSlug = 'animation-addons-for-elementor'; // 🔁 change this
const distPath = 'dist';

/**
 * Create plugin ZIP
 */
gulp.task('zip', () => {
    return gulp.src([
        '**/*',

        // unnecessary files
        '!node_modules/**',
        '!public/**',
        '!dist/**',

        // /src and /assets/src SHIP. They are the human-readable sources for
        // everything compiled into assets/build and assets/css, and the
        // directory guidelines require them to be available. Excluding them
        // is what made the shipped plugin look like compiled code with no
        // origin, even though the repository had them all along.

        '!**/*.map',

        // Both the .js and the .min.js in assets/js/widgets ship: each .min.js
        // is the build output of the .js beside it, and dropping the readable
        // half left 33 scripts in the package with no source.

        '!.git/**',
        '!.github/**',
        '!.vscode/**',
        '!.eslintignore',
        '!.eslintrc',
        '!.eslintrc.cjs',
        '!.gitignore',
        '!.phpcs.xml.dist',
        '!components.cptBuilder.json',
        '!components.dashboard.json',
        '!components.pageImport.json',
        '!jsconfig.json',
        '!package-lock.json',
        '!phpstan.neon',

        // The build tooling ships too, so "npm install && npm run build" can be
        // run against the plugin as distributed rather than only against the
        // repository: package.json, webpack.config.js, gulpfile.js,
        // postcss.config.js, the tailwind configs and swap-config.js.
        // Developer documentation and internal notes -- not plugin code.
        '!elementor-atomic-learning-guide/**',
        '!**/*.md',
        '!phpcs.xml.dist',

        // vendor/ is HALF required. vendor/autoload.php and vendor/composer/**
        // carry the ONLY PSR-4 map for WCF_ADDONS\ -> inc/, and 337 namespaced
        // files resolve through it, so dropping vendor wholesale ships a plugin
        // that activates and then fatals. Everything else under vendor/ is
        // require-dev (PHPCS/WPCS, ~3,500 files) with no runtime code at all.
        // Running "composer install --no-dev" before the release is still the
        // right thing to do; these lines mean forgetting it cannot ship them.
        '!vendor/squizlabs/**',
        '!vendor/wp-coding-standards/**',
        '!vendor/phpcsstandards/**',
        '!vendor/dealerdirect/**',
        '!vendor/bin/**',
        '!README.md',
        '!.env',
        '!**/.DS_Store'
    ], {
        base: '..',       // 👈 keeps main plugin folder
        allowEmpty: true
    })
        .pipe(gulpZip('animation-addons-for-elementor.zip'))
        .pipe(gulp.dest(distPath));
});


gulp.task('release', gulp.series(
    'build',
    'zip'
));

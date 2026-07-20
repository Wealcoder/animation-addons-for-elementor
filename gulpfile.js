/**
 * Gulp Config.
 * @version 1.0.0
 */

//const app = require( './package.json' );
const gulp = require('gulp');
const babel = require('gulp-babel');
const eslint = require('gulp-eslint');
const prettify = require('gulp-js-prettify');
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
        .pipe(mode.development(prettify({"indent_with_tabs": true,})))
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

// Combined tasks.
gulp.task('buildJs', gulp.series('compile:js', 'minify:js', 'minify:atomic-js'));
gulp.task('buildCss', gulp.series('compile:scss', 'minify:css', 'compile:atomic-scss', 'minify:atomic-css'));

gulp.task('build', gulp.series('buildCss', 'buildJs'));

gulp.task('watch', () => new Promise((resolve, reject) => {
    try {
        gulp.watch('assets/src/js/**/*.js', {ignoreInitial: true}, gulp.series('buildJs'));
        gulp.watch('assets/src/scss/**/*.scss', {ignoreInitial: true}, gulp.series('buildCss'));
        gulp.watch('inc/AtomicWidgets/Widgets/**/*.scss', {ignoreInitial: true}, gulp.series('compile:atomic-scss', 'minify:atomic-css'));
        resolve();
    } catch (e) {
        reject(e);
    }
}));

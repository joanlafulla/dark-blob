'use strict';
const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');
const minify = require('gulp-minify');
const rename = require('gulp-rename');

function compileSass() {
    return gulp.src('scss/**/*.scss') // Path to your SCSS files
      .pipe(sass().on('error', sass.logError))
      .pipe(gulp.dest('css')); // Output directory for CSS files
  }

  function cleanCss() {
    return gulp.src('css/*.css')
      .pipe(cleanCSS({compatibility: 'ie8'}))
      .pipe(rename('my_styles_min.css'))
      .pipe(gulp.dest('css/minified'));
  }

  function minifyJS() {
    return gulp.src('js/custom.js')
    .pipe(minify({
        ext: {
            min: '.min.js'
        },
        noSource: true
    }))
    
    .pipe(gulp.dest('js/minified'));
  }

  gulp.task('sass', compileSass);
  gulp.task('cleanCSS', cleanCss);
  gulp.task('minifyjs', minifyJS);

  gulp.task('watch', function watchFunc() {
    gulp.watch('scss/**/*.scss', gulp.series('sass', 'cleanCSS'));
    gulp.watch('js/*.js', gulp.series('minifyjs'));
  });

  

 
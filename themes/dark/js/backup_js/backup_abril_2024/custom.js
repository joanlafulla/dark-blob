function randomIntFromInterval(min, max) { // min and max included 
    return Math.floor(Math.random() * (max - min + 1) + min)
  }
  
  const rndInt = randomIntFromInterval(1, 2)
  //console.log(rndInt)

$(document).ready(function(){

    let masking = $('.excerp_article_image_bg');
    masking.each(function(){
        if (rndInt == 1) {
            $(".excerp_article_image_bg").addClass("mask_1");
        } else if (rndInt == 2) {
            $(".excerp_article_image_bg").addClass("mask_2");
        } 
    });

    let colorSchema = $('.my_schema').text();
    //console.log(colorSchema);
    if (colorSchema === "dark") {
        $("body").addClass("dark");
    } else {
        $("body").addClass("light");
    }
    

    $("#open_searcher").click(function() {
        $("#searcher").css({"display": "block", "opacity": "100"});
        $("body").addClass("opened_searcher");
    });

    $("#close_searcher").click(function() {
        $("#searcher").css({"display": "none", "opacity": 0});
        $("body").removeClass("opened_searcher");
    });

    var video = $(".article_trailer_content").children("iframe").attr("src");

    $("#open_trailer").click(function() {
        if($(".article_trailer_content").children("iframe").attr('src') == '') { 
            $(".article_trailer_content").children("iframe").attr("src",video);
          }
        $("#trailer").css({"display": "flex", "align-items":"center", "justify-content":"center", "opacity": "100"});
        $("body").addClass("opened_searcher");
    });

    $("#close_trailer").click(function() {
        $(".article_trailer_content").children("iframe").attr("src","");
        $("#trailer").css({"display": "none", "opacity": 0});
        $("body").removeClass("opened_searcher");
    });

    let rating = $('.my_rating');
    let hype = $('.hype_rating_hide');
    let rating_users = $('.comment_rating_display');
    let platform = $('.article_platform');
    let rating_users_average = $('.article_users_rating_average');
    let buttons_grid = $('.article_grid_list .article_buttons .button');
    let randomWords = ['¿De qué va esto?', 'Quiero saber más', '¡Adelante!', 'Tiene buena pinta', 'Continuar leyendo'];

    buttons_grid.each(function(){
        $(this).text(randomWords[Math.floor( Math.random() * randomWords.length)]);
    });

    hype.each(function(){
        let myHype = $(this).text();
        switch (myHype) {
            case "1":
                $(this).next('.hype_rating').addClass("hype_frozen");
                break;
            case "2":
                $(this).next('.hype_rating').addClass("hype_warm");
                break;
            case "3":
                $(this).next('.hype_rating').addClass("hype_excited");
                break;
            case "4":
                $(this).next('.hype_rating').addClass("hype_mindBlown");
                break;
        }
    });
    
    rating.each(function(){
        let myRating = $(this).text();
        switch (myRating) {
            case "0":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/0_stars.svg" alt="0 estrellas" width="116" height="24" />');
                break;
            case "0.5":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/0_5_stars.svg" alt="0.5 estrellas" width="116" height="24" />');
                break;
            case "1":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/1_stars.svg" alt="1 estrella" width="116" height="24" />');
                break;  
            case "1.5":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/1_5_stars.svg" alt="1.5 estrellas" width="116" height="24" />');
                break;
            case "2":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/2_stars.svg" alt="2 estrellas" width="116" height="24" />');
                break;
            case "2.5":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/2_5_stars.svg" alt="2.5 estrellas" width="116" height="24" />');
                break;
            case "3":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/3_stars.svg" alt="3 estrellas" width="116" height="24" />');
                break;
            case "3.5":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/3_5_stars.svg" alt="3.5 estrellas" width="116" height="24" />');
                break;
            case "4":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/4_stars.svg" alt="4 estrellas" width="116" height="24" />');
                break;
            case "4.5":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/4_5_stars.svg" alt="4.5 estrellas" width="116" height="24" />');
                break;
            case "5":
                $(this).parent('.article_rating').prepend('<img src="../../../images/rating/5_stars.svg" alt="5 estrellas" width="116" height="24" />');
                break;
        }
    });

    rating_users_average.each(function(){
        let userRatingAverage = $(this).text();

        if(userRatingAverage==1.0) {
            $(".their_rating").text("1");  
        } else if(userRatingAverage==2.0) {
            $(".their_rating").text("2");  
        } else if(userRatingAverage==3.0) {
            $(".their_rating").text("3");  
        } else if(userRatingAverage==4.0) {
            $(".their_rating").text("4");  
        } else if(userRatingAverage==5.0) {
            $(".their_rating").text("5");  
        }

        if (userRatingAverage.indexOf("valoraciones") >= 0) {
            $('#rating_de_lectores').hide();
        } else if (userRatingAverage == 1) {
            $(this).prepend('<img src="../../../images/rating/1_stars_green.svg" alt="1 estrella" width="116" height="24" />');
        } else if (userRatingAverage >= 1 &&userRatingAverage <= 1.5) {
            $(this).prepend('<img src="../../../images/rating/1_5_stars_green.svg" alt="1.5 estrellas" width="116" height="24" />');
        } else if (userRatingAverage > 1.5 && userRatingAverage <= 2) {
            $(this).prepend('<img src="../../../images/rating/2_stars_green.svg" alt="2 estrella" width="116" height="24" />');
        } else if (userRatingAverage > 2 && userRatingAverage <= 2.5) {
            $(this).prepend('<img src="../../../images/rating/2_5_stars_green.svg" alt="2.5 estrella" width="116" height="24" />');
        } else if (userRatingAverage > 2.5 && userRatingAverage <= 3) {
            $(this).prepend('<img src="../../../images/rating/3_stars_green.svg" alt="2.5 estrella" width="116" height="24" />');
        } else if (userRatingAverage > 3 && userRatingAverage <= 3.5) {
            $(this).prepend('<img src="../../../images/rating/3_5_stars_green.svg" alt="3.5 estrellas" width="116" height="24" />');
        } else if (userRatingAverage > 3.5 && userRatingAverage <= 4) {
            $(this).prepend('<img src="../../../images/rating/4_stars_green.svg" alt="4 estrella" width="116" height="24" />');
        } else if (userRatingAverage > 4 && userRatingAverage <= 4.5) {
            $(this).prepend('<img src="../../../images/rating/4_5_stars_green.svg" alt="4.5 estrella" width="116" height="24" />');
        } else if (userRatingAverage > 4.5 && userRatingAverage <= 5) {
            $(this).prepend('<img src="../../../images/rating/5_stars_green.svg" alt="5 estrella" width="116" height="24" />');
        }
    });

    rating_users.each(function(){
        let userRating = $(this).text();
        switch (userRating) {
            case "0":
                $(this).text($(this).text().replace("0", "Sin valoración"));
                break;
            case "1":
                $(this).append('<img src="../../../images/rating/1_stars_white.svg" alt="1 estrella" width="86" height="18" />');
                break;  
            case "2":
                $(this).append('<img src="../../../images/rating/2_stars_white.svg" alt="2 estrellas" width="86" height="18" />');
                break;
            case "3":
                $(this).append('<img src="../../../images/rating/3_stars_white.svg" alt="3 estrellas" width="86" height="18" />');
                break;
            case "4":
                $(this).append('<img src="../../../images/rating/4_stars_white.svg" alt="4 estrellas" width="86" height="18" />');
                break;
            case "5":
                $(this).append('<img src="../../../images/rating/5_stars_white.svg" alt="5 estrellas" width="86" height="18" />');
                break;
        }
    });

    platform.each(function(){
        let platform_text = $(this).text();
        
        //console.log(platform_text);
        //console.log(colorSchema);

        switch (platform_text) {
            case "Netflix":
                $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=netflix"><img src="../../../images/plataformas/netflix.png" alt="Vista en Netflix" height="46" class="img_platform" /></a>');
                $(this).after('<div class="badge review">Reseña</div>');
                break;

            case "Filmin":
                $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=filmin"><img src="../../../images/plataformas/filmin.png" alt="Vista en Filmin" height="46" class="img_platform" /></a>');
                $(this).after('<div class="badge review">Reseña</div>');
                break;

            case "Disney":
                if (colorSchema === "dark") {
                    $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=disney"><img src="../../../images/plataformas/disney_dark.png" alt="Vista en Disney Plus" height="46" class="img_platform" /></a>');
                    $(this).after('<div class="badge review">Reseña</div>');
                    break; 
                } else {
                    $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=disney"><img src="../../../images/plataformas/disney.png" alt="Vista en Disney Plus" height="46" class="img_platform" /></a>');
                    $(this).after('<div class="badge review">Reseña</div>');
                    break; 
                }
                
            case "Hbo":
                if (colorSchema === "dark") {
                    $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=hbo"><img src="../../../images/plataformas/hbo_dark.png" alt="Vista en Hbo Max" height="32" class="img_platform" /></a>');
                    $(this).after('<div class="badge review">Reseña</div>');
                    break;
                } else {
                    $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=hbo"><img src="../../../images/plataformas/hbo.png" alt="Vista en Hbo Max" height="32" class="img_platform" /></a>');
                    $(this).after('<div class="badge review">Reseña</div>');
                    break;
                }

            case "Prime":
                if (colorSchema === "dark") {
                    $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=prime"><img src="../../../images/plataformas/prime_dark.png" alt="Vista en Prime Video" height="46" class="img_platform" /></a>');
                    $(this).after('<div class="badge review">Reseña</div>');
                    break;
                } else {
                    $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=prime"><img src="../../../images/plataformas/prime.png" alt="Vista en Prime Video" height="46" class="img_platform" /></a>');
                    $(this).after('<div class="badge review">Reseña</div>');
                    break;
                }
                

            case "Planet horror":
                if (colorSchema === "dark") {
                        $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=planet-horror"><img src="../../../images/plataformas/phorror_dark.png" alt="Vista en Planet Horror" height="46" class="img_platform" /></a>');
                        $(this).after('<div class="badge review">Reseña</div>');
                        break;
                } else {
                        $(this).after('<a href="https://darkblobcine.com/index.php?s=category&c=planet-horror"><img src="../../../images/plataformas/phorror.png" alt="Vista en Planet Horror" height="46" class="img_platform" /></a>');
                        $(this).after('<div class="badge review">Reseña</div>');
                        break;
                }

            case "Noticia":
                $(this).after('<div class="badge noticia">Noticia</div>');
                break;

            case "Top":
                $(this).after('<div class="badge top"><a href="https://darkblobcine.com/index.php?s=category&c=top">Top</a></div>');
                break;

            case "Trailer":
                $(this).after('<div class="badge top"><a href="https://darkblobcine.com/index.php?s=category&c=trailer">Trailer</a></div>');
                break;

            case "Especial":
                $(this).after('<div class="badge top"><a href="https://darkblobcine.com/index.php?s=category&c=especial">Espcecial</a></div>');
                break;
        }
    });

        $(".article_next a").each(function() {
            if ($(this).attr('href') == '') {
                $(this).parent().hide();
            }
        });

  });




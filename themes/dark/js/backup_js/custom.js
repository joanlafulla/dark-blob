const rndInt = randomIntFromInterval(1, 2);
const body = document.querySelector('body');
const masking = document.querySelector('.excerp_article_image_bg');
const colorScheme = document.querySelector('.my_schema');
const search = document.querySelector('#searcher');
const openSearch = document.querySelector('#open_searcher');
const closeSearch = document.querySelector('#close_searcher');
const hype = document.querySelectorAll('.hype_rating_hide');
const buttonsGrid = document.querySelectorAll('.article_grid_list .article_buttons .button');
const randomWords = ['¿De qué va esto?', 'Quiero saber más', '¡Adelante!', 'Tiene buena pinta', 'Continuar leyendo'];
const platform = document.querySelectorAll('.article_platform');
const rating = document.querySelectorAll('.my_rating');
const ratingUsersComment = document.querySelectorAll('.comment_rating_display');
const ratingUsersAverage = document.querySelector('.article_users_rating_average');
const articleNext = document.querySelectorAll('.article_next a');
const hypeSwipper = document.querySelectorAll('.swiper-slide_hype_number');
const allSliders = document.querySelectorAll('.swiper');


//Aplicamos un BG Mask al header detalle
function randomIntFromInterval(min, max) { 
    return Math.floor(Math.random() * (max - min + 1) + min)
}

if(masking) {
    if(rndInt === 1) {
        masking.classList.add('mask_1')
    } else {
        masking.classList.add('mask_2')
    }
}


//Aplicamos texto random a los botones listado
buttonsGrid.forEach( (button) => {
    button.firstChild.textContent = randomWords[Math.floor( Math.random() * randomWords.length)];
});

//Añadimos la class dark al body
if(colorScheme && colorScheme.textContent === "dark") {
    body.classList.add('dark');
}

//Gestión search
openSearch.addEventListener('click', (event) => {
    search.style.display = 'block';
    search.style.opacity = '100';
    body.classList.add('opened_searcher');
})

closeSearch.addEventListener('click', (envent) => {
    search.style.display = 'none';
    search.style.opacity = '0';
    body.classList.remove('opened_searcher');
})

//Hypemetter
hype.forEach( hypeBar => {
    let myHype = hypeBar.textContent;

    switch(myHype) {
        case "1":
            hypeBar.nextElementSibling.classList.add("hype_frozen");
            break;
        case "2":
            hypeBar.nextElementSibling.classList.add("hype_warm");
            break;
        case "3":
            hypeBar.nextElementSibling.classList.add("hype_excited");
            break;
        case "4":
            hypeBar.nextElementSibling.classList.add("hype_mindBlown");
            break;
    }
})

//Rating pelis listado
rating.forEach ( (myRating) => {

    let myRatingContainer = myRating.parentElement;
    //console.log(myRatingContainer.classList)
    let myRatingText = myRating.textContent;
    const ratingImage = document.createElement('img');

    function addRatingCard () {
        myRatingContainer.prepend(ratingImage);
        ratingImage.width = 116;
        ratingImage.height = 24;
        let ratingNumber = myRatingText.toString();
        let ratingNumberRemovePoint = ratingNumber.replace(".", "_");
        ratingImage.alt = `Hemos valorado la película con ${ratingNumber} estrellas`;
        if(myRatingContainer.classList.value != "article_rating") {
            ratingImage.src = `https://darkblobcine.com/images/rating/${ratingNumberRemovePoint}_stars_blue.svg`;
        } else {
            ratingImage.src = `https://darkblobcine.com/images/rating/${ratingNumberRemovePoint}_stars.svg`;
        }
        
    }
    addRatingCard();
})

//Rating comentario individual
ratingUsersComment.forEach( (ratingComment) => {
    let commentRatingText = ratingComment.textContent;
    let commentRatingImage = document.createElement('img')

    function addRatingComment () {
        if (commentRatingText === "0") {
        } else {
            ratingComment.append(commentRatingImage)
            commentRatingImage.width = 86;
            commentRatingImage.height = 18;
            commentRatingImage.src = `https://darkblobcine.com/images/rating/${commentRatingText}_stars_white.svg`;
        }
    }
    addRatingComment();
} )

//Average rating usuarios
function addRatingAverage() {
    const ratingImageAverage = document.createElement('img');
    let ratingUsersAverageNumber = ratingUsersAverage.innerText.toLowerCase().trim();
    if (ratingUsersAverageNumber != "sin valoraciones") {
        ratingUsersAverage.parentNode.classList.remove('rating_lectores');
        ratingPasarANumero = parseFloat(ratingUsersAverageNumber);
        ratingUsersAverage.prepend(ratingImageAverage);
        ratingImageAverage.width = 116;
        ratingImageAverage.height = 24;
        ratingImageAverage.alt = `La valoración media de los usuarios es de ${ratingUsersAverageNumber} estrellas`;
        
        if(ratingPasarANumero === 1) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/1_stars_green.svg`;
        } else if(ratingPasarANumero >=1 && ratingPasarANumero <=1.5) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/1_5_stars_green.svg`;
        } else if(ratingPasarANumero >1.5 && ratingPasarANumero <=2) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/2_stars_green.svg`;
        } else if(ratingPasarANumero >2 && ratingPasarANumero <=2.5) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/2_5_stars_green.svg`;
        } else if(ratingPasarANumero >2.5 && ratingPasarANumero <=3) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/3_stars_green.svg`;
        } else if(ratingPasarANumero >3 && ratingPasarANumero <=3.5) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/3_5_stars_green.svg`; 
        } else if(ratingPasarANumero >3.5 && ratingPasarANumero <=4) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/4_stars_green.svg`;
        } else if(ratingPasarANumero >4 && ratingPasarANumero <=4.5) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/4_5_stars_green.svg`;
        } else if(ratingPasarANumero >4.5 && ratingPasarANumero <=5) {
            ratingImageAverage.src = `https://darkblobcine.com/images/rating/5_stars_green.svg`;
        }
    }
}

if(ratingUsersAverage) {
    addRatingAverage();
}

//Añadir plataforma y badge a las pelis del listado
platform.forEach( (myPlatform) => {
    let myPlatformText = myPlatform.textContent;
    let textBadge = "";
    const platformBadge = document.createElement('div');
    const platformLink = document.createElement('a');
    const platformImage = document.createElement('img');

    function addLinkBadge(myurl, image, type) {
        if(myurl) {
            myPlatform.after(platformLink);
            platformLink.href=`https://darkblobcine.com/index.php?s=category&c=${myurl}`;
            platformLink.append(platformImage);
            if(colorScheme && colorScheme.textContent === "dark") {
                platformImage.src = `https://darkblobcine.com/images/plataformas/${image}_dark.png`;
            } else {
                platformImage.src = `https://darkblobcine.com/images/plataformas/${image}.png`;
            }
            platformImage.classList.add('img_platform');
            platformImage.height = 46;
            platformImage.alt = `Película vista en ${myurl}`
        }
        // Add badge
        if(type === "review") {
            platformBadge.classList.add('badge', 'review');
            let textBadge = "Reseña";
            platformBadge.innerText = textBadge;
        } else {
            platformBadge.classList.add('badge', 'noticia');
            let textBadge = "Noticia";
            platformBadge.innerText = textBadge;
        }
    }

    switch(myPlatformText) {
        case "Prime":
            addLinkBadge("prime", "prime", "review");
            myPlatform.after(platformBadge);
            break;
        case "Netflix":
            addLinkBadge("netflix", "netflix", "review");
            myPlatform.after(platformBadge);
            break;
        case "Disney":
            addLinkBadge("disney", "disney", "review");
            myPlatform.after(platformBadge);
            break;
        case "Hbo":
            addLinkBadge("hbo", "hbo", "review");
            myPlatform.after(platformBadge);
            break;
        case "Filmin":
            addLinkBadge("filmin", "filmin", "review");
            myPlatform.after(platformBadge);
            break;
        case "Planet horror":
            addLinkBadge("planet-horror", "phorror", "review");
            myPlatform.after(platformBadge);
            break;
        case "Noticia":
            addLinkBadge("", "", "noticia");
            myPlatform.after(platformBadge);
            break;
    }
})


//Esconder article next/previous
if(articleNext) {
    articleNext.forEach( (myArticleNext) => {
        if(myArticleNext.href == window.location.href) {
            myArticleNext.style.display = "none"
        } 
    })
}

//Swipper 1
const swiper1 = new Swiper('.swiper-1', {
    direction: 'horizontal',
    slidesPerView: 2.25,
    spaceBetween: 8,
    freeMode: true,
    keyboard: {
        enabled: true,
        onlyInViewport: false,
      },
      breakpoints: {
        // when window width is >= xpx
        1240: {
          slidesPerView: 6,
          slidesPerGroup: 6,
          spaceBetween: 16,
          freeMode: false
        },

        900: {
            slidesPerView: 4,
            slidesPerGroup: 4,
            spaceBetween: 8,
            freeMode: false
          },

        660: {
            slidesPerView: 3,
            slidesPerGroup: 3,
            spaceBetween: 8,
            freeMode: false
          },
      },
    // Navigation arrows
    navigation: {
        nextEl: '.arrow_right_1',
        
        prevEl: '.arrow_left_1',
      },

  });

  //Swipper 2
const swiper2 = new Swiper('.swiper-2', {
    direction: 'horizontal',
    slidesPerView: 2.25,
    spaceBetween: 8,
    freeMode: true,
    keyboard: {
        enabled: true,
        onlyInViewport: false,
      },
      breakpoints: {
        // when window width is >= xpx
        1240: {
          slidesPerView: 6,
          slidesPerGroup: 6,
          spaceBetween: 16,
          freeMode: false
        },

        900: {
            slidesPerView: 4,
            slidesPerGroup: 4,
            spaceBetween: 8,
            freeMode: false
          },

        660: {
            slidesPerView: 3,
            slidesPerGroup: 3,
            spaceBetween: 8,
            freeMode: false
          },
      },
    // Navigation arrows
    navigation: {
      nextEl: '.arrow_right_2',
      
      prevEl: '.arrow_left_2',
    },
  });

  

  
  //let arrowRight = document.querySelector('.swiper_navigation_custom div:nth-child(2)');

  function arrowsSlider(slider, numberClass){
    let arrowLeft = document.querySelector(`.arrow_left_${numberClass}`);
    let arrowRight = document.querySelector(`.arrow_right_${numberClass}`);
    
    slider.on('activeIndexChange', function() {
        if(slider.activeIndex != 0) {
            arrowLeft.style.opacity = "1";
            arrowLeft.style.pointerEvents = "auto";
        }
    });

    slider.on('reachBeginning', function() {
            arrowRight.style.opacity = "1";
            arrowRight.style.pointerEvents = "auto";
            arrowLeft.style.opacity = "0.3";
            arrowLeft.style.pointerEvents = "none";
    })

    slider.on('reachEnd', function() {
            arrowRight.style.opacity = "0.3";
            arrowRight.style.pointerEvents = "none";
            arrowLeft.style.opacity = "1";
            arrowLeft.style.pointerEvents = "auto";
    })
  }

  arrowsSlider(swiper1, "1");
  arrowsSlider(swiper2, "2");


  // Hype swiper
  hypeSwipper.forEach( (hype) => {
    let mySwipperContent = hype.parentElement;
    let mySwipperHype = hype.textContent;
    let mySwipperHypeImage = document.createElement('img');
    let mySwipperHypeText = document.createElement('span');
    mySwipperHypeImage.width = 24;
    mySwipperHypeImage.height = 24;

    function addSwipperHype() {
        switch(mySwipperHype) {
            case "1":
                mySwipperHypeImage.src = `https://darkblobcine.com/images/hype/frozen_swiper.svg`;
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "freezy!";
                mySwipperHypeText.style.color = "#85A5F8";
            break;
            case "2":
                mySwipperHypeImage.src = `https://darkblobcine.com/images/hype/warm_swiper.svg`;
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "warm!";
                mySwipperHypeText.style.color = "#65F8D4";
            break;
            case "3":
                mySwipperHypeImage.src = `https://darkblobcine.com/images/hype/excited_swiper.svg`;
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "hot!";
                mySwipperHypeText.style.color = "#FCA573";

                
            break;
            case "4":
                mySwipperHypeImage.src = `https://darkblobcine.com/images/hype/mind_blown_swiper.svg`;
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "booom!";
                mySwipperHypeText.style.color = "#E24344";
            break;
        }

    }

    addSwipperHype();
  })
       





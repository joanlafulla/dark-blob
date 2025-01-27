const rndInt = randomIntFromInterval(1, 2);
const body = document.querySelector('body');
const masking = document.querySelector('.excerp_article_image_bg');
const contextualNav = document.querySelector('.contextual_nav');
const colorScheme = document.querySelector('.my_schema');
const mywhishlist = document.querySelector('#wishlist');
const myWhishlistCounter = document.querySelectorAll('.mylist_counter');
const search = document.querySelector('#searcher');
const openSearch = document.querySelectorAll('#open_searcher');
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
const cards = document.querySelectorAll(".article_list");
const trans = document.querySelector(".transition");
const highlighted = document.querySelector(".highlighted");
const maskHighlighted = document.querySelector(".mask_highlighted");
const videoHighlighted = document.querySelectorAll(".video_highlighted");

console.log("hola github jaaaar");

let mypage = '';

function getmypage() {
    if (document.getElementById('wishlist') || (document.body.classList.contains('dark')) ) {
    mypage = "pageDark";
    return mypage;
} else {
    mypage = "pageLight";
    return mypage
}
}


document.addEventListener('DOMContentLoaded', getmypage);

// Show contextual menu
window.addEventListener('scroll', function(e) {
    const scrollPosition = this.window.scrollY;
    if (scrollPosition > 100) {
        contextualNav.classList.add('contextual_nav-visible');
    } else if(scrollPosition < 100 && contextualNav.classList.contains('contextual_nav-visible')) {
        contextualNav.classList.remove('contextual_nav-visible');
    }
});

function randomIntFromInterval(min, max) { 
    return Math.floor(Math.random() * (max - min + 1) + min)
}

//Aplicamos texto random a los botones listado
buttonsGrid.forEach( (button) => {
    button.firstChild.textContent = randomWords[Math.floor( Math.random() * randomWords.length)];
});

const pageAccessedByReload = (
    (window.performance.navigation && window.performance.navigation.type === 1) ||
      window.performance
        .getEntriesByType('navigation')
        .map((nav) => nav.type)
        .includes('reload')
  );

//Añadir un URL parametre al link que nos lleva a la lista
const urlList = document.querySelector('.item_nav_lista');
let urlListValue = randomIntFromInterval(1,1000);
urlList.href = urlList.href + "&q=" + urlListValue;

if(pageAccessedByReload && document.getElementById('wishlist')) {
    let urlListValue = randomIntFromInterval(1,1000);
    urlList.href = urlList.href + "&r=" + urlListValue;
    window.location.replace(urlList.href);
  }

//Añadimos la class dark al body
if(colorScheme && colorScheme.textContent.includes("dark")) {
    body.classList.add('dark');
}

//Gestión search
openSearch.forEach (open => {
    open.addEventListener('click', (event) => {
        search.style.display = 'block';
        search.style.opacity = '100';
        body.classList.add('opened_searcher');
    })
});

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
    let myRatingText = myRating.textContent;
    const ratingImage = document.createElement('img');

    function addRatingCard () {
        myRatingContainer.prepend(ratingImage);
        ratingImage.width = 116;
        ratingImage.height = 24;
        let ratingNumber = myRatingText.toString();
        let ratingNumberRemovePoint = ratingNumber.replace(".", "_");
        ratingImage.alt = `Hemos valorado la película con ${ratingNumber} estrellas`;
        if(myRatingContainer.classList.value != "article_rating" && myRatingContainer.parentElement.parentElement.classList.value != "excerp_article_body_recomendado") {
            ratingImage.src = `https://darkblobcine.com/images/rating/${ratingNumberRemovePoint}_stars_blue.svg`;
        } else {
            ratingImage.src = `https://darkblobcine.com/images/rating/${ratingNumberRemovePoint}_stars.svg`;
        }

        if (myRatingContainer.parentElement.parentElement.classList.value == "excerp_article_body_recomendado") {
            ratingImage.src = `https://darkblobcine.com/images/rating/${ratingNumberRemovePoint}_stars_blue.svg`;
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
    mypageschema = getmypage();
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
            if(mypageschema == "pageDark") {
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
        } else if(type==="noticia") {
            platformBadge.classList.add('badge', 'noticia');
            let textBadge = "Noticia";
            platformBadge.innerText = textBadge;
        } else if(type==="top") {
            platformBadge.classList.add('badge', 'top');
            let textBadge = "TOP 5";
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
        case "Cine":
            addLinkBadge("cine", "cine", "review");
            myPlatform.after(platformBadge);
            break;
        case "Preestreno":
            addLinkBadge("preestreno", "preestreno", "review");
            myPlatform.after(platformBadge);
            break;
        case "Noticia":
            addLinkBadge("", "", "noticia");
            myPlatform.after(platformBadge);
            break;
        case "Top":
            addLinkBadge("", "", "top");
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
if(allSliders.length > 0) {

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
                mySwipperHypeImage.alt = "Nivel de hype frío (frozen)";
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "freezy!";
                mySwipperHypeText.style.color = "#85A5F8";
            break;
            case "2":
                mySwipperHypeImage.src = `https://darkblobcine.com/images/hype/warm_swiper.svg`;
                mySwipperHypeImage.alt = "Nivel de hype templado (warm)";
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "warm!";
                mySwipperHypeText.style.color = "#65F8D4";
            break;
            case "3":
                mySwipperHypeImage.src = `https://darkblobcine.com/images/hype/excited_swiper.svg`;
                mySwipperHypeImage.alt = "Nivel de hype caliente (hot)";
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "hot!";
                mySwipperHypeText.style.color = "#FCA573";

                
            break;
            case "4":
                mySwipperHypeImage.src = `https://darkblobcine.com/images/hype/mind_blown_swiper.svg`;
                mySwipperHypeImage.alt = "Nivel de hype me explota la cabeza (boom)";
                mySwipperContent.prepend(mySwipperHypeImage);
                mySwipperContent.append(mySwipperHypeText);
                mySwipperHypeText.innerText = "booom!";
                mySwipperHypeText.style.color = "#E24344";
            break;
        }

    }

    addSwipperHype();
  })

} //Fin if allSliders

// Intersection observer para las cards

const observer = new IntersectionObserver(
    entries => {
        entries.forEach((entry) => {
            const target = entry.target;
            const targetHype = target.querySelector(".hype_rating");
            const targetVideoContenedor = target.querySelector(".videoHighlighted_contenedor")
            const targetVideo = target.querySelector(".video_highlighted");

            if(entry.isIntersecting) {
                if(targetHype) {
                    if(targetHype.classList.contains("hype_frozen")) {
                        targetHype.classList.add("hype_frozen-animation");
                    } else if(targetHype.classList.contains("hype_warm")) {
                        targetHype.classList.add("hype_warm-animation");
                    } else if(targetHype.classList.contains("hype_excited")) {
                        targetHype.classList.add("hype_excited-animation");
                    } else if(targetHype.classList.contains("hype_mindBlown")) {
                        targetHype.classList.add("hype_mindBlown-animation");
                    }
                }
                if(targetVideo) {
                    let timeVideo = targetVideo.duration;
                    let timeVideoFadeOut = timeVideo - 1;
                    let timeVideoStyle = getComputedStyle(targetVideoContenedor);
                    let gettimeVideoStyle = timeVideoStyle.getPropertyValue("--videoLenght")

                    targetVideoContenedor.style.setProperty("--videoLenght", timeVideo-2);
                    targetVideoContenedor.style.zIndex = "2";
                    targetVideoContenedor.style.opacity = "1";
                    targetVideo.play();
 
                    var videoFadeOut = (function() {
                        var executed = false;
                        return function() {
                            if (!executed) {
                                executed = true;
                                // do something
                                targetVideoContenedor.style.zIndex = "1";
                                 targetVideoContenedor.style.opacity = "0";
                            }
                        };
                    })();

                    targetVideo.addEventListener('timeupdate', (e) => {
                        if (targetVideo.currentTime > timeVideoFadeOut) { 
                            videoFadeOut();
                        }
                      });                  
        
                    targetVideo.addEventListener("ended", (event) => {
                        targetVideo.pause();
                        targetVideo.currentTime = 0;
                      });
                } 
            } 
        });
    },
    {
        threshold: 0.85
    }
);

cards.forEach( entry => {
    observer.observe(entry);
})

// Intersection observer para el recomendado

const observer_recommended = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            const intersecting = entry.isIntersecting;
            if(intersecting) {
                let highlightedBox = document.querySelector(".highlighted_content_contenedor");
                let highlightedImg = document.querySelector(".highlighted_image img");
                highlightedBox.classList.add("highlighted_content_contenedor-visible");
                highlightedImg.style.opacity = "1";
            } 
        }
    },
        {
            rootMargin: "0px 0px -40% 0px"
        }
    );
if(highlighted) {
    observer_recommended.observe(highlighted);
}


// Embed youtube videos
function labnolIframe(div) {
    var iframe = document.createElement('iframe');
    iframe.setAttribute('src', 'https://www.youtube.com/embed/' + div.dataset.id + '?autoplay=1');
    iframe.setAttribute('frameborder', '0');
    iframe.setAttribute('allowfullscreen', '1');
    iframe.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
    div.parentNode.replaceChild(iframe, div);
  }

  function initYouTubeVideos() {
    var playerElements = document.querySelectorAll('.youtube-player');

    for (var n = 0; n < playerElements.length; n++) {
      var videoId = playerElements[n].dataset.id;
      var div = document.createElement('div');
      div.setAttribute('data-id', videoId);
      var thumbNode = document.createElement('img');
      thumbNode.src = '//i.ytimg.com/vi/ID/hq720.jpg'.replace('ID', videoId);
      div.appendChild(thumbNode);
      var playButton = document.createElement('div');
      playButton.setAttribute('class', 'play');
      div.appendChild(playButton);
      div.onclick = function () {
        labnolIframe(this);
      };
      playerElements[n].appendChild(div);
    }
  }

document.addEventListener('DOMContentLoaded', initYouTubeVideos);


// Lottie list added animation
let lottieitems = document.querySelectorAll('.lottie_player');
const lottieURL = "https://darkblobcine.com/themes/dark/animations/addList.json";
let fromCookiesToLottie = getCookie('mymovies');

 if (typeof fromCookiesToLottie != "undefined") {
    fromCookiesToLottie = fromCookiesToLottie.split(",");
}
    
document.addEventListener('DOMContentLoaded', function() {
    let mylenght = lottieitems.length;
        for (var n = 0; n < (mylenght); n++) {
            lottieitems[n].load(lottieURL);
            if (typeof fromCookiesToLottie != "undefined") {
                fromCookiesToLottie.forEach( coockie => {
                    if(coockie == lottieitems[n].getAttribute('data-id')) {
                        lottieitems[n].setAttribute("data-state", "1");
                        lottieitems[n].play();
                    }
                });
            }               
        } 
});

function getCookie(cName) {
    const name = cName + "=";
    const cDecoded = decodeURIComponent(document.cookie);
    const cArr = cDecoded.split('; ');
    let res;
    cArr.forEach(val => {
      if (val.indexOf(name) === 0) res = val.substring(name.length);
    })
    return res
}

function getCoockieArrayMovies() {
    let cookieArrayMovies;
    if (document.cookie.indexOf('mymovies=') == -1) {
        return cookieArrayMovies = '';
    } else {
        var m = getCookie("mymovies");
        cookieArrayMovies = m.split(',');
        return cookieArrayMovies;
    }
};

let arrayMovies = getCoockieArrayMovies();


// Añadir el counter de películas guardadas
function addCounter () {
    
    //let arrayMoviesCounter = arrayMovies.length;
    let arrayMoviesCounter = arrayMovies.toString();
    if (arrayMoviesCounter.length > 0) {
        let arrayMoviesCounterArray = arrayMoviesCounter.split(",");
        let totalCounter = arrayMoviesCounterArray.length;
        if (totalCounter >= 0) {
            myWhishlistCounter.forEach( (counter) => {
            counter.style.display = "flex";
            counter.innerText = totalCounter;
            });
        };
    } else if (arrayMoviesCounter.length == '0') {
            myWhishlistCounter.forEach( (counter) => {
            counter.style.display = "none";
            counter.innerText = '';
        });
    }   
}

document.addEventListener('DOMContentLoaded', addCounter);


    function addList(button, dataId) {
        dataId = button.getAttribute("data-id");

        if (arrayMovies.length != 0) {
            arrayMovies = [...arrayMovies, dataId];
            arrayMovies = arrayMovies.toString();
            return arrayMovies;
        } else {
            arrayMovies= dataId;
            arrayMovies = arrayMovies.toString();
            return arrayMovies;
        }
    }

    function arrayRemove(arr, value) { 
        return arr.filter(function(ele){ 
            return ele != value; 
        });
    }

    function removeList(button, dataId) {
        arrayMovies = arrayMovies.filter(element => element !== dataId);
        arrayMovies = arrayMovies.toString();
        return arrayMovies;
    }

    lottieitems.forEach( (button, dataId) => {
        button.addEventListener('click', (event) => {
        let dataState = button.getAttribute("data-state");
        let dataId = button.getAttribute("data-id");
        
         if (dataState == 0) {
            button.setAttribute("data-state", "1");
            button.setDirection(1);
            button.play();
            addList(button, dataId);
            addCounter();
            document.cookie = 'mymovies=' + arrayMovies;' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
            arrayMovies = getCoockieArrayMovies();
         } else {
            button.setAttribute("data-state", "0");
            button.setDirection(-1);
            button.play();
            removeList(button, dataId);
            addCounter();
            if (arrayMovies.length != 0) {
                document.cookie = 'mymovies=' + arrayMovies;' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
            } else {
                document.cookie = 'mymovies= ; expires = Thu, 01 Jan 1970 00:00:00 GMT';
            }
            arrayMovies = getCoockieArrayMovies();
         }
    })
    });

//width del window menos scrollbar








    

  
  






       





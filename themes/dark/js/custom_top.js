const body = document.querySelector('body');
const contextualNav = document.querySelector('.contextual_nav');
const colorScheme = document.querySelector('.my_schema');
const search = document.querySelector('#searcher');
const openSearch = document.querySelectorAll('#open_searcher');
const closeSearch = document.querySelector('#close_searcher');
const openTopTrailer = document.querySelectorAll(".open_top_trailer");
const topTrailer = document.querySelector('.top_trailer');
const closeTopTrailer = document.querySelector('.closing_top_trailer');
const topYoutubePlayer = document.querySelector(".top-youtube-player");
const ratingUsersComment = document.querySelectorAll('.comment_rating_display');
const ratingUsersAverage = document.querySelector('.article_users_rating_average');
const articleNext = document.querySelectorAll('.article_next a');
const cards = document.querySelectorAll(".article_list");
const trans = document.querySelector(".transition");
const topContenedor = document.querySelectorAll(".top_contenedor");
const topLogo = document.querySelector("svg.top_logo");
const topGoBack = document.querySelector(".material-icons.top_goback");
const topLogoMobile = document.querySelector("svg.top_logo_mobile");
const topMain = document.querySelector("main");
const topHeader = document.querySelector("header");
const comments = document.querySelector(".top_comments_footer");

topMain.addEventListener ("scroll",() => {
    if (topMain.scrollTop > (window.innerHeight / 2) && topMain.scrollTop < (window.innerHeight - (window.innerHeight / 4))) {
        topLogo.style.fill = "#272934";
        topLogoMobile.style.fill = "#272934";
        topGoBack.style.color = "#272934";
    }
})

// Preload background images
function preload_image(im_url) {
    let img = new Image();
    img.src = im_url;
}

topContenedor.forEach ( (itemTop) => {
    preload_image("https://res.cloudinary.com/" +itemTop.dataset.background);
});

// Intersection observer para los top_contenedor


const ab = function(a, b, c) {
    window.addEventListener("resize", () => {
    const anchuraTop = window.innerWidth;
    
    if (anchuraTop >= 1280) {
        a = "calc(100vw / 3)"
    } else if (anchuraTop < 1280 && anchuraTop > 720) {
        a = "calc(100vw / 2)"
    } else {
        a = "calc(100vw - (100vw / 4))"
    }

    if (c === "left") {
        if (a === "calc(100vw / 3)") {
            b.style.left = "6rem";
            b.style.bottom = "6rem"; 
        } else if (a === "calc(100vw / 2)") {
            b.style.left = "3rem";
            b.style.bottom = "3rem";
        } else if (a === "calc(100vw - (100vw / 4))") {
            b.style.left = "1rem";
            b.style.bottom = "2rem";
        }
    } else {
        if (a === "calc(100vw / 3)") {
            b.style.right = "6rem";
            b.style.bottom = "6rem"; 
        } else if (a === "calc(100vw / 2)") {
            b.style.right = "3rem";
            b.style.bottom = "3rem";
        } else if (a === "calc(100vw - (100vw / 4))") {
            b.style.right = "1rem";
            b.style.bottom = "2rem";
        } 
    }
});
}

const observer = new IntersectionObserver(
    entries => {
        entries.forEach((entry) => {
            const target = entry.target;
            if(entry.isIntersecting) {
                if(target) {
                    const child = target.children.item(1);
                    let bottomInicial = window.getComputedStyle(child).getPropertyValue('--width_top_item');
                    topContentPosition = target.dataset.position;
                    topColor = target.dataset.color;
                    target.firstElementChild.style.animation = "bigHero 10s ease-out 1 forwards";
                    
                    if (topContentPosition === "left") {
                        child.style.visibility = "visible";
                        child.style.opacity = "1"

                       if (bottomInicial === "calc(100vw / 3)") {
                            child.style.left = "6rem";
                            child.style.bottom = "6rem"; 
                        } else if (bottomInicial === "calc(100vw / 2)") {
                            child.style.left = "3rem";
                            child.style.bottom = "3rem";
                        } else if (bottomInicial === "calc(100vw - (100vw / 4))") {
                            child.style.left = "1rem";
                            child.style.bottom = "2rem";
                        }   
                    } else {
                        child.style.visibility = "visible";
                        child.style.opacity = "1"
                        
                        if (bottomInicial === "calc(100vw / 3)") {
                            child.style.right = "6rem";
                            child.style.bottom = "6rem"; 
                        } else if (bottomInicial === "calc(100vw / 2)") {
                            child.style.right = "3rem";
                            child.style.bottom = "3rem";
                        } else if (bottomInicial === "calc(100vw - (100vw / 4))") {
                            child.style.right = "1rem";
                            child.style.bottom = "2rem";
                        }
                    }

                    if (topColor === "black") {
                        topHeader.style.opacity = "1";
                        topLogo.style.fill = "#F8F3E5";
                        topLogoMobile.style.fill = "#F8F3E5";
                        topGoBack.style.color = "#F8F3E5";
                    } else {
                        topHeader.style.opacity = "1";
                        topLogo.style.fill = "#272934";
                        topLogoMobile.style.fill = "#272934";
                        topGoBack.style.color = "#272934";
                    }

                    ab(bottomInicial, child, topContentPosition);
                }
            } 
        });
    },
    {
        threshold: 0.85
    }
);

topContenedor.forEach( entry => {
    observer.observe(entry);
})

const observerComments = new IntersectionObserver(
    entries => {
        entries.forEach((entry) => {
            const target = entry.target;
            if(entry.isIntersecting) {
                if(target) {
                    topHeader.style.opacity = "0";
                }
            }
        })
    },
    {
        threshold: 0.1
    }
);
observerComments.observe(comments);


//Gestión search
openSearch.forEach (open => {
    open.addEventListener('click', (event) => {
        search.style.display = 'block';
        search.style.opacity = '1';
        body.classList.add('opened_searcher');
    })
});

closeSearch.addEventListener('click', (envent) => {
    search.style.display = 'none';
    search.style.opacity = '0';
    body.classList.remove('opened_searcher');
})

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

  function removeAllChildNodes(parent) {
    while (parent.firstChild) {
        parent.removeChild(parent.firstChild);
    }
}

//document.addEventListener('DOMContentLoaded', initYouTubeVideos); */

//Gestión top trailer
openTopTrailer.forEach (openTrailer => {
    openTrailer.addEventListener('click', (event) => {
        const trailer = openTrailer.dataset.trailer;
        topYoutubePlayer.dataset.id = trailer;
        topTrailer.style.visibility ='visible';
        topTrailer.style.opacity = '1';
        body.classList.add('opened_top_trailer');
        initYouTubeVideos();
    })
});

closeTopTrailer.addEventListener('click', (envent) => {
    topTrailer.style.visibility = 'hidden';
    topTrailer.style.opacity = '0';
    body.classList.remove('opened_top_trailer');
    removeAllChildNodes(topYoutubePlayer);
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


//Esconder article next/previous
if(articleNext) {
    articleNext.forEach( (myArticleNext) => {
        if(myArticleNext.href == window.location.href) {
            myArticleNext.style.display = "none"
        } 
    })
}







    

  
  






       





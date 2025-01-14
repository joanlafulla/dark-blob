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
const articleNext = document.querySelectorAll('.article_next a')



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
    let myRatingText = myRating.textContent;
    const ratingImage = document.createElement('img');

    function addRating () {
        myRatingContainer.prepend(ratingImage);
        ratingImage.width = 116;
        ratingImage.height = 24;
        let ratingNumber = myRatingText.toString();
        let ratingNumberRemovePoint = ratingNumber.replace(".", "_");
        ratingImage.alt = `Hemos valorado la película con ${ratingNumber} estrellas`;
        ratingImage.src = `https://darkblobcine.com/images/rating/${ratingNumberRemovePoint}_stars.svg`;
    }
    addRating();
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


       





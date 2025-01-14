// Lottie list added animation
let lottieitems = document.querySelectorAll('.lottie_player');
let fromCookiesToLottie = getCookie('mymovies');

if (typeof fromCookiesToLottie != "undefined") {
    fromCookiesToLottie = fromCookiesToLottie.split(",");
    //console.log("El contenido de cookies (fromCookiesToLottie -- getCookie): ");
    //console.log(fromCookiesToLottie);
}
    
document.addEventListener('DOMContentLoaded', function() {
    let mylenght = lottieitems.length;
    for (var n = 0; n < (mylenght); n++) {
        if (typeof fromCookiesToLottie != "undefined") {
            fromCookiesToLottie.forEach( coockie => {
                //console.log("la cookie: " + coockie);
                //console.log("lottieitems getattribute ID: " + lottieitems[n].getAttribute('data-id'));
                if(coockie == lottieitems[n].getAttribute('data-id')) {
                    lottieitems[n].parentNode.classList.add('item_list_active');
                    //console.log("AÑADO CLASE DE CSS");
                }
            });
        }  
    } 
    let mylenght2 = (lottieitems.length) + 1;
    for (var i = 1; i < mylenght2; i++) {
        LottieInteractivity.create({
            player: "#addmovie" + i,
            mode:"cursor",
            actions: [
            {
                type: "toggle",
                forceFlag: false,
            }
            ]
        });
    }

});



//Con esta función obtengo el valor de la cookie 'mymovies'
function getCookie(cName) {
    const name = cName + "=";
    const cDecoded = decodeURIComponent(document.cookie); //to be careful
    const cArr = cDecoded.split('; ');
    let res;
    cArr.forEach(val => {
      if (val.indexOf(name) === 0) res = val.substring(name.length);
    })
    //console.log("a ver el res: ");
    //console.log(res);
    //console.log(typeof res);
    return res
}

function getCoockieArrayMovies() {
    let cookieArrayMovies;
    if (document.cookie.indexOf('mymovies=') == -1) {
        console.log("al cargar NO existe la cookie");
        return cookieArrayMovies = '';
    } else {
        var m = getCookie("mymovies");
        cookieArrayMovies = m.split(',');
        console.log("COOKIEARRAY MOVIES PASADO A ARRAY DESDE GETCOOKIEARRAYMOVIES: ")
        console.log(cookieArrayMovies);
        return cookieArrayMovies;
    }
};

let arrayMovies = getCoockieArrayMovies();
console.log("EMPEZAMOS CON ESTE ARRAYMOVIES: ");
console.log(arrayMovies)

    function addList(button, dataId) {
        button.setAttribute("data-state", "1");
        dataId = button.getAttribute("data-id"); // Devuelve el ID de la película

        if (arrayMovies.length != 0) {
            //arrayMovies.unshift(dataId);
            arrayMovies = [...arrayMovies, dataId];
            console.log("arraMovies ANTES de convertirlo en string: ");
            console.log(arrayMovies);
            arrayMovies = arrayMovies.toString();
            console.log("AÑADO EL ARRAYMOVIES: ");
            console.log(arrayMovies);
            console.log("Primera letra de ArrayMovies: " + Array.from(arrayMovies)[0]);
            return arrayMovies;
            //document.cookie = 'mymovies=' + arrayMovies;' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
        } else {
            console.log("El arrayMovies está vacío");
            console.log("voy a añadir un valor a arrayMovies");
             //Añado la película al arrayMovies
            arrayMovies= dataId;
            arrayMovies = arrayMovies.toString();
            console.log("AÑADO EL ARRAYMOVIES AL ARRAY VACÍO: ");
            console.log(arrayMovies);
            return arrayMovies;
            //document.cookie = 'mymovies='+ arrayMovies; + ' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
        }
    }

    function arrayRemove(arr, value) { 
        return arr.filter(function(ele){ 
            return ele != value; 
        });
    }

    function removeList(button, dataId) {
        console.log("ArrayMovies ANTES de eliminar: ");
        console.log(arrayMovies);
        /* arrayMovies.forEach(function(entry) {
            console.log(entry);
          }); */
        button.setAttribute("data-state", "0");
        arrayMovies = arrayMovies.filter(element => element !== dataId);
        console.log("ArrayMovies DESPUES de eliminar: ");
        console.log(arrayMovies);
        arrayMovies = arrayMovies.toString();
        console.log("ArrayMovies al pasarlo a string: ");
        console.log(arrayMovies);
        //document.cookie = 'mymovies=' + arrayMovies;' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
        return arrayMovies;
    }

    lottieitems.forEach( (button, dataId) => {
        button.addEventListener('click', (event) => {
        let dataState = button.getAttribute("data-state");
        let dataId = button.getAttribute("data-id");
        //console.log("Mi atributo: " + dataId + " Mi estado: " + dataState);
        
         if (dataState == 0) {
            addList(button, dataId);
            document.cookie = 'mymovies=' + arrayMovies;' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
            arrayMovies = getCoockieArrayMovies();
         } else {
            removeList(button, dataId);
            if (arrayMovies.length != 0) {
                document.cookie = 'mymovies=' + arrayMovies;' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
            } else {
                //destroy cookie
                document.cookie = 'mymovies= ; expires = Thu, 01 Jan 1970 00:00:00 GMT';
            }
            arrayMovies = getCoockieArrayMovies();
         }
    })
    });

    const itemssaved = document.querySelectorAll('.item_list_saved');

    itemssaved.forEach( (button, dataId) => {
        button.addEventListener('click', (event) => {
            button.parentElement.classList.remove('item_list_active');
            let dataId = button.nextElementSibling.getAttribute("data-id");
            removeList(button, dataId);
            console.log("Arraymovies desde ITEMSAVED: ");
            console.log(arrayMovies);
            arrayMovies = arrayMovies.toString();
            console.log("ArrayMovies al pasarlo a string desde ITEMSAVED: ");
            console.log(arrayMovies);
            if (arrayMovies.length != 0) {
                document.cookie = 'mymovies=' + arrayMovies;' expires=Wed, 1 Jan 2070 13:47:11 UTC; path=/';
                arrayMovies = getCoockieArrayMovies();
            } else {
                //destroy cookie
                document.cookie = 'mymovies= ; expires = Thu, 01 Jan 1970 00:00:00 GMT';
            }
        })
    });
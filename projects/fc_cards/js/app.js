const accesMapa = document.querySelector('.acces-mapa');
const area = document.querySelector('.acces-mapa > img');
const btVerMapa = document.querySelector('.acces-mapa-ver');
const btTrayecto = document.querySelector('.acces-mapa-trayecto');
const btDraw = document.querySelector('.acces-mapa-draw');
const mapPoligon = document.querySelector('.mapa-hibrida-poligon');
const prices = ["250.000", "325.700", "410.000", "234.000", "287.000", "290.500", "345.000", "320.000", "470.000", "278.900", "312.700"];
const openMenu = document.querySelector('.open-menu');
const menuOpened = document.querySelector('.menu-opened');
const checkboxRedLines = document.getElementById('redlines');
const redLines = document.querySelectorAll('.resolution');
const moreInfo = document.querySelectorAll('.more-info a');

let poi;

window.addEventListener('scroll', (e) => {
    //console.log(scrollY);
    if(scrollY > 86) {
        accesMapa.setAttribute("style", "height: 68px");
        btVerMapa.setAttribute("style", "width: 276px");
        area.setAttribute("style", "opacity: 0");
        btTrayecto.setAttribute("style", "opacity: 0");
        btDraw.setAttribute("style", "opacity: 0");
    } else if (scrollY < 86) {
        accesMapa.removeAttribute("style");
        area.removeAttribute("style");
        btTrayecto.removeAttribute("style");
        btDraw.removeAttribute("style");
        btVerMapa.removeAttribute("style");
    }
})

function numeroaleatorio(min, max) { 
    return Math.floor(Math.random() * (max - min + 1) + min)
}



function addPoi() {
    let left = numeroaleatorio(2,99);
    let top = numeroaleatorio(2,99);
    poi = document.createElement("div");
    poi.classList.add('poi');
    poi.style.top = `${top}%`;
    poi.style.left = `${left}%`;
    mapPoligon.appendChild(poi);
  }

  function addPrice() {
    let randomPrice = numeroaleatorio(1,10);
    let left = numeroaleatorio(2,99);
    let top = numeroaleatorio(2,99);
    poiPrice = document.createElement("div");
    poiPrice.classList.add('poi-price');
    poiPrice.style.top = `${top}%`;
    poiPrice.style.left = `${left}%`;
    poiPrice.textContent = prices[randomPrice];
    console.log(`El poi prices es: ${poiPrice}`);
    mapPoligon.appendChild(poiPrice);
  }


if(typeof mapPoligon !== 'undefined' && mapPoligon !== null) {
    for(x=0; x<=300; x++) {
      addPoi();
    }
  
    for(x=0; x<=30; x++) {
      addPrice();
    }
  }


  openMenu.addEventListener('click', (e) => {
    menuOpened.classList.toggle('menu-opened_active');
  });

  checkboxRedLines.addEventListener('change', (e) => {
    if(checkboxRedLines.checked) {
      redLines.forEach((line) => {
        line.style.display = 'block';
      });
    } else {
      redLines.forEach((line) => {
        line.style.display = 'none';
      });
    }
  });

  moreInfo.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      console.log("more info button clicked")
      link.parentElement.classList.toggle('more-info_active');
    });
  }
  );


    